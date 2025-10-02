<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Process\Process;

class Patches implements PluginInterface, EventSubscriberInterface {

  /**
   * @var Composer $composer
   */
  protected $composer;
  /**
   * @var IOInterface $io
   */
  protected $io;
  /**
   * @var EventDispatcher $eventDispatcher
   */
  protected $eventDispatcher;
  /**
   * @var ProcessExecutor $executor
   */
  protected $executor;
  /**
   * @var array $patches
   */
  protected $patches;

  /**
   * @var array $installedPatches
   */
  protected $installedPatches;

  /**
   * Apply plugin modifications to composer
   *
   * @param Composer    $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->eventDispatcher = $composer->getEventDispatcher();
    $this->executor = new ProcessExecutor($this->io);
    $this->patches = array();
    $this->installedPatches = array();
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::PRE_INSTALL_CMD => array('checkPatches'),
      ScriptEvents::PRE_UPDATE_CMD => array('checkPatches'),
      PackageEvents::PRE_PACKAGE_INSTALL => array('gatherPatches'),
      PackageEvents::PRE_PACKAGE_UPDATE => array('gatherPatches'),
      // The following is a higher weight for compatibility with
      // https://github.com/AydinHassan/magento-core-composer-installer and more generally for compatibility with
      // every Composer plugin which deploys downloaded packages to other locations.
      // In such cases you want that those plugins deploy patched files so they have to run after
      // the "composer-patches" plugin.
      // @see: https://github.com/cweagans/composer-patches/pull/153
      PackageEvents::POST_PACKAGE_INSTALL => array('postInstall', 10),
      PackageEvents::POST_PACKAGE_UPDATE => array('postInstall', 10),
    );
  }

  /**
   * Before running composer install,
   * @param Event $event
   */
  public function checkPatches(Event $event) {
    if (!$this->isPatchingEnabled()) {
      return;
    }

    try {
      $repositoryManager = $this->composer->getRepositoryManager();
      $localRepository = $repositoryManager->getLocalRepository();
      $installationManager = $this->composer->getInstallationManager();
      $packages = $localRepository->getPackages();

      $extra = $this->composer->getPackage()->getExtra();
      $patches_ignore = isset($extra['patches-ignore']) ? $extra['patches-ignore'] : array();

      $tmp_patches = $this->grabPatches();
      foreach ($packages as $package) {
        $extra = $package->getExtra();
        if (isset($extra['patches'])) {
          if (isset($patches_ignore[$package->getName()])) {
            foreach ($patches_ignore[$package->getName()] as $package_name => $patches) {
              if (isset($extra['patches'][$package_name])) {
                $extra['patches'][$package_name] = array_diff($extra['patches'][$package_name], $patches);
              }
            }
          }
          $this->installedPatches[$package->getName()] = $extra['patches'];
        }
        $patches = isset($extra['patches']) ? $extra['patches'] : array();
        $tmp_patches = $this->arrayMergeRecursiveDistinct($tmp_patches, $patches);
      }

      if ($tmp_patches == FALSE) {
        $this->io->write('<info>No patches supplied.</info>');
        return;
      }

      // Remove packages for which the patch set has changed.
      $promises = array();
      foreach ($packages as $package) {
        if (!($package instanceof AliasPackage)) {
          $package_name = $package->getName();
          $extra = $package->getExtra();
          $has_patches = isset($tmp_patches[$package_name]);
          $has_applied_patches = isset($extra['patches_applied']) && count($extra['patches_applied']) > 0;
          if (($has_patches && !$has_applied_patches)
            || (!$has_patches && $has_applied_patches)
            || ($has_patches && $has_applied_patches && $tmp_patches[$package_name] !== $extra['patches_applied'])) {
            $uninstallOperation = new UninstallOperation($package, 'Removing package so it can be re-installed and re-patched.');
            $this->io->write('<info>Removing package ' . $package_name . ' so that it can be re-installed and re-patched.</info>');
            $promises[] = $installationManager->uninstall($localRepository, $uninstallOperation);
          }
        }
      }
      $promises = array_filter($promises);
      if ($promises) {
        $this->composer->getLoop()->wait($promises);
      }
    }
    // If the Locker isn't available, then we don't need to do this.
    // It's the first time packages have been installed.
    catch (\LogicException $e) {
      return;
    }
  }

  /**
   * Gather patches from dependencies and store them for later use.
   *
   * @param PackageEvent $event
   */
  public function gatherPatches(PackageEvent $event) {
    // If we've already done this, then don't do it again.
    if (isset($this->patches['_patchesGathered'])) {
      $this->io->write('<info>Patches already gathered. Skipping</info>', TRUE, IOInterface::VERBOSE);
      return;
    }
    // If patching has been disabled, bail out here.
    elseif (!$this->isPatchingEnabled()) {
      $this->io->write('<info>Patching is disabled. Skipping.</info>', TRUE, IOInterface::VERBOSE);
      return;
    }

    $this->patches = $this->grabPatches();
    if (empty($this->patches)) {
      $this->io->write('<info>No patches supplied.</info>');
    }

    $extra = $this->composer->getPackage()->getExtra();
    $patches_ignore = isset($extra['patches-ignore']) ? $extra['patches-ignore'] : array();

    // Now add all the patches from dependencies that will be installed.
    $operations = $event->getOperations();
    $this->io->write('<info>Gathering patches for dependencies. This might take a minute.</info>');
    foreach ($operations as $operation) {
      if ($operation instanceof InstallOperation || $operation instanceof UpdateOperation) {
        $package = $this->getPackageFromOperation($operation);
        $extra = $package->getExtra();
        if (isset($extra['patches'])) {
          if (isset($patches_ignore[$package->getName()])) {
            foreach ($patches_ignore[$package->getName()] as $package_name => $patches) {
              if (isset($extra['patches'][$package_name])) {
                $extra['patches'][$package_name] = array_diff($extra['patches'][$package_name], $patches);
              }
            }
          }
          $this->patches = $this->arrayMergeRecursiveDistinct($this->patches, $extra['patches']);
        }
        // Unset installed patches for this package
        if(isset($this->installedPatches[$package->getName()])) {
          unset($this->installedPatches[$package->getName()]);
        }
      }
    }

    // Merge installed patches from dependencies that did not receive an update.
    foreach ($this->installedPatches as $patches) {
      $this->patches = $this->arrayMergeRecursiveDistinct($this->patches, $patches);
    }

    // If we're in verbose mode, list the projects we're going to patch.
    if ($this->io->isVerbose()) {
      foreach ($this->patches as $package => $patches) {
        $number = count($patches);
        $this->io->write('<info>Found ' . $number . ' patches for ' . $package . '.</info>');
      }
    }

    // Make sure we don't gather patches again. Extra keys in $this->patches
    // won't hurt anything, so we'll just stash it there.
    $this->patches['_patchesGathered'] = TRUE;
  }

  /**
   * Get the patches from root composer or external file
   * @return Patches
   * @throws \Exception
   */
  public function grabPatches() {
      // First, try to get the patches from the root composer.json.
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches'])) {
      $this->io->write('<info>Gathering patches for root package.</info>');
      $patches = $extra['patches'];
      return $patches;
    }
    // If it's not specified there, look for a patches-file definition.
    elseif (isset($extra['patches-file'])) {
      $this->io->write('<info>Gathering patches from patch file.</info>');
      $patches = file_get_contents($extra['patches-file']);
      $patches = json_decode($patches, TRUE);
      $error = json_last_error();
      if ($error != 0) {
        switch ($error) {
          case JSON_ERROR_DEPTH:
            $msg = ' - Maximum stack depth exceeded';
            break;
          case JSON_ERROR_STATE_MISMATCH:
            $msg =  ' - Underflow or the modes mismatch';
            break;
          case JSON_ERROR_CTRL_CHAR:
            $msg = ' - Unexpected control character found';
            break;
          case JSON_ERROR_SYNTAX:
            $msg =  ' - Syntax error, malformed JSON';
            break;
          case JSON_ERROR_UTF8:
            $msg =  ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
          default:
            $msg =  ' - Unknown error';
            break;
          }
          throw new \Exception('There was an error in the supplied patches file:' . $msg);
        }
      if (isset($patches['patches'])) {
        $patches = $patches['patches'];
        return $patches;
      }
      elseif(!$patches) {
        throw new \Exception('There was an error in the supplied patch file');
      }
    }
    else {
      return array();
    }
  }

  /**
   * @param PackageEvent $event
   * @throws \Exception
   */
  public function postInstall(PackageEvent $event) {

    // Check if we should exit in failure.
    $extra = $this->composer->getPackage()->getExtra();
    $exitOnFailure = getenv('COMPOSER_EXIT_ON_PATCH_FAILURE') || !empty($extra['composer-exit-on-patch-failure']);
    $skipReporting = getenv('COMPOSER_PATCHES_SKIP_REPORTING') || !empty($extra['composer-patches-skip-reporting']);

    // Get the package object for the current operation.
    $operation = $event->getOperation();
    /** @var PackageInterface $package */
    $package = $this->getPackageFromOperation($operation);
    $package_name = $package->getName();

    if (!isset($this->patches[$package_name])) {
      if ($this->io->isVerbose()) {
        $this->io->write('<info>No patches found for ' . $package_name . '.</info>');
      }
      return;
    }
    $this->io->write('  - Applying patches for <info>' . $package_name . '</info>');

    // Get the install path from the package object.
    $manager = $event->getComposer()->getInstallationManager();
    $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);

    // Set up a downloader.
    $downloader = new RemoteFilesystem($this->io, $this->composer->getConfig());

    // Track applied patches in the package info in installed.json
    $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
    $localPackage = $localRepository->findPackage($package_name, $package->getVersion());
    $extra = $localPackage->getExtra();
    $extra['patches_applied'] = array();

    foreach ($this->patches[$package_name] as $description => $url) {
      $this->io->write('    <info>' . $url . '</info> (<comment>' . $description. '</comment>)');
      try {
        $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $url, $description));
        $this->getAndApplyPatch($downloader, $install_path, $url, $package);
        $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $url, $description));
        $extra['patches_applied'][$description] = $url;
      }
      catch (\Exception $e) {
        $this->io->write('   <error>Could not apply patch! Skipping. The error was: ' . $e->getMessage() . '</error>');
        if ($exitOnFailure) {
          throw new \Exception("Cannot apply patch $description ($url)!");
        }
      }
    }
    $localPackage->setExtra($extra);

    $this->io->write('');

    if (true !== $skipReporting) {
      $this->writePatchReport($this->patches[$package_name], $install_path);
    }
  }

  /**
   * Get a Package object from an OperationInterface object.
   *
   * @param OperationInterface $operation
   * @return PackageInterface
   * @throws \Exception
   */
  protected function getPackageFromOperation(OperationInterface $operation) {
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    else {
      throw new \Exception('Unknown operation: ' . get_class($operation));
    }

    return $package;
  }

  /**
   * Apply a patch on code in the specified directory.
   *
   * @param RemoteFilesystem $downloader
   * @param $install_path
   * @param $patch_url
   * @param PackageInterface $package
   * @throws \Exception
   */
  protected function getAndApplyPatch(RemoteFilesystem $downloader, $install_path, $patch_url, PackageInterface $package) {

    // Local patch file.
    if (file_exists($patch_url)) {
      $filename = realpath($patch_url);
    }
    else {
      // Generate random (but not cryptographically so) filename.
      $filename = uniqid(sys_get_temp_dir().'/') . ".patch";

      // Download file from remote filesystem to this location.
      $hostname = parse_url($patch_url, PHP_URL_HOST);

      try {
        $downloader->copy($hostname, $patch_url, $filename, false);
      } catch (\Exception $e) {
        // In case of an exception, retry once as the download might
        // have failed due to intermittent network issues.
        $downloader->copy($hostname, $patch_url, $filename, false);
      }
    }

    // The order here is intentional. p1 is most likely to apply with git apply.
    // p0 is next likely. p2 is extremely unlikely, but for some special cases,
    // it might be useful. p4 is useful for Magento 2 patches
    $patch_levels = array('-p1', '-p0', '-p2', '-p4');

    // Check for specified patch level for this package.
    $extra = $this->composer->getPackage()->getExtra();
    if (!empty($extra['patchLevel'][$package->getName()])){
      $patch_levels = array($extra['patchLevel'][$package->getName()]);
    }
    // Attempt to apply with git apply.
    $patched = $this->applyPatchWithGit($install_path, $patch_levels, $filename);

    // In some rare cases, git will fail to apply a patch, fallback to using
    // the 'patch' command.
    if (!$patched) {
      foreach ($patch_levels as $patch_level) {
        // --no-backup-if-mismatch here is a hack that fixes some
        // differences between how patch works on windows and unix.
        if ($patched = $this->executeCommand("patch %s --no-backup-if-mismatch -d %s < %s", $patch_level, $install_path, $filename)) {
          break;
        }
      }
    }

    // Clean up the temporary patch file.
    if (isset($hostname)) {
      unlink($filename);
    }
    // If the patch *still* isn't applied, then give up and throw an Exception.
    // Otherwise, let the user know it worked.
    if (!$patched) {
      throw new \Exception("Cannot apply patch $patch_url");
    }
  }

  /**
   * Checks if the root package enables patching.
   *
   * @return bool
   *   Whether patching is enabled. Defaults to TRUE.
   */
  protected function isPatchingEnabled() {
    $extra = $this->composer->getPackage()->getExtra();

    if (empty($extra['patches']) && empty($extra['patches-ignore']) && !isset($extra['patches-file'])) {
      // The root package has no patches of its own, so only allow patching if
      // it has specifically opted in.
      return isset($extra['enable-patching']) ? $extra['enable-patching'] : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Writes a patch report to the target directory.
   *
   * @param array $patches
   * @param string $directory
   */
  protected function writePatchReport($patches, $directory) {
    $output = "This file was automatically generated by Composer Patches (https://github.com/cweagans/composer-patches)\n";
    $output .= "Patches applied to this directory:\n\n";
    foreach ($patches as $description => $url) {
      $output .= $description . "\n";
      $output .= 'Source: ' . $url . "\n\n\n";
    }
    file_put_contents($directory . "/PATCHES.txt", $output);
  }

  /**
   * Executes a shell command with escaping.
   *
   * @param string $cmd
   * @return bool
   */
  protected function executeCommand($cmd) {
    // Shell-escape all arguments except the command.
    $args = func_get_args();
    foreach ($args as $index => $arg) {
      if ($index !== 0) {
        $args[$index] = escapeshellarg($arg);
      }
    }

    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    $output = '';
    if ($this->io->isVerbose()) {
      $this->io->write('<comment>' . $command . '</comment>');
      $io = $this->io;
      $output = function ($type, $data) use ($io) {
        if ($type == Process::ERR) {
          $io->write('<error>' . $data . '</error>');
        }
        else {
          $io->write('<comment>' . $data . '</comment>');
        }
      };
    }
    return ($this->executor->execute($command, $output) == 0);
  }

  /**
   * Recursively merge arrays without changing data types of values.
   *
   * Does not change the data types of the values in the arrays. Matching keys'
   * values in the second array overwrite those in the first array, as is the
   * case with array_merge.
   *
   * @param array $array1
   *   The first array.
   * @param array $array2
   *   The second array.
   * @return array
   *   The merged array.
   *
   * @see http://php.net/manual/en/function.array-merge-recursive.php#92195
   */
  protected function arrayMergeRecursiveDistinct(array $array1, array $array2) {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
      }
      else {
        $merged[$key] = $value;
      }
    }

    return $merged;
  }

  /**
   * Attempts to apply a patch with git apply.
   *
   * @param $install_path
   * @param $patch_levels
   * @param $filename
   *
   * @return bool
   *   TRUE if patch was applied, FALSE otherwise.
   */
  protected function applyPatchWithGit($install_path, $patch_levels, $filename) {
    // Do not use git apply unless the install path is itself a git repo
    // @see https://stackoverflow.com/a/27283285
    if (!is_dir($install_path . '/.git')) {
      return FALSE;
    }

    $patched = FALSE;
    foreach ($patch_levels as $patch_level) {
      if ($this->io->isVerbose()) {
        $comment = 'Testing ability to patch with git apply.';
        $comment .= ' This command may produce errors that can be safely ignored.';
        $this->io->write('<comment>' . $comment . '</comment>');
      }
      $checked = $this->executeCommand('git -C %s apply --check -v %s %s', $install_path, $patch_level, $filename);
      $output = $this->executor->getErrorOutput();
      if (substr($output, 0, 7) == 'Skipped') {
        // Git will indicate success but silently skip patches in some scenarios.
        //
        // @see https://github.com/cweagans/composer-patches/pull/165
        $checked = FALSE;
      }
      if ($checked) {
        // Apply the first successful style.
        $patched = $this->executeCommand('git -C %s apply %s %s', $install_path, $patch_level, $filename);
        break;
      }
    }
    return $patched;
  }

  /**
   * Indicates if a package has been patched.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to check.
   *
   * @return bool
   *   TRUE if the package has been patched.
   */
  public static function isPackagePatched(PackageInterface $package) {
    return array_key_exists('patches_applied', $package->getExtra());
  }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

}
