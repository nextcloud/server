<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use Doctrine\DBAL\Exception\TableExistsException;
use OC\App\AppStore\AppNotFoundException;
use OC\App\AppStore\Bundles\Bundle;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Archive\TAR;
use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\Files\FilenameValidator;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\Files;
use OCP\HintException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\L10N\IFactory;
use OCP\Migration\IOutput;
use OCP\Server;
use OCP\Util;
use phpseclib\File\X509;
use Psr\Log\LoggerInterface;

/**
 * This class provides the functionality needed to install, update and remove apps
 */
class Installer {
	private ?bool $isInstanceReadyForUpdates = null;
	private ?array $apps = null;

	public function __construct(
		private AppFetcher $appFetcher,
		private IClientService $clientService,
		private ITempManager $tempManager,
		private LoggerInterface $logger,
		private IConfig $config,
		private IAppManager $appManager,
		private IFactory $l10nFactory,
		private bool $isCLI,
	) {
	}

	/**
	 * Installs an app that is located in one of the app folders already
	 *
	 * @param string $appId App to install
	 * @param bool $forceEnable
	 * @throws \Exception
	 * @return string app ID
	 */
	public function installApp(string $appId, bool $forceEnable = false): string {
		$appPath = $this->appManager->getAppPath($appId, true);

		$l = $this->l10nFactory->get('core');
		$info = $this->appManager->getAppInfoByPath($appPath . '/appinfo/info.xml', $l->getLanguageCode());

		if (!is_array($info) || $info['id'] !== $appId) {
			throw new \Exception(
				$l->t('App "%s" cannot be installed because appinfo file cannot be read.',
					[$appId]
				)
			);
		}

		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		$ignoreMax = $forceEnable || in_array($appId, $ignoreMaxApps, true);

		$version = implode('.', Util::getVersion());
		if (!$this->appManager->isAppCompatible($version, $info, $ignoreMax)) {
			throw new \Exception(
				$l->t('App "%s" cannot be installed because it is not compatible with this version of the server.',
					[$info['name']]
				)
			);
		}

		// check for required dependencies
		\OC_App::checkAppDependencies($this->config, $l, $info, $ignoreMax);
		$coordinator = Server::get(Coordinator::class);
		$coordinator->runLazyRegistration($appId);

		return $this->installAppLastSteps($appPath, $info, null, 'no');
	}

	/**
	 * Updates the specified app from the appstore
	 *
	 * @param bool $allowUnstable Allow unstable releases
	 */
	public function updateAppstoreApp(string $appId, bool $allowUnstable = false): bool {
		if ($this->isUpdateAvailable($appId, $allowUnstable) !== false) {
			try {
				$this->downloadApp($appId, $allowUnstable);
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), [
					'exception' => $e,
				]);
				return false;
			}
			return $this->appManager->upgradeApp($appId);
		}

		return false;
	}

	/**
	 * Split the certificate file in individual certs
	 *
	 * @param string $cert
	 * @return string[]
	 */
	private function splitCerts(string $cert): array {
		preg_match_all('([\-]{3,}[\S\ ]+?[\-]{3,}[\S\s]+?[\-]{3,}[\S\ ]+?[\-]{3,})', $cert, $matches);

		return $matches[0];
	}

	/**
	 * Get the path where to install apps
	 *
	 * @throws \RuntimeException if an app folder is marked as writable but is missing permissions
	 */
	public function getInstallPath(): ?string {
		foreach (\OC::$APPSROOTS as $dir) {
			if (isset($dir['writable']) && $dir['writable'] === true) {
				// Check if there is a writable install folder.
				if (!is_writable($dir['path'])
					|| !is_readable($dir['path'])
				) {
					throw new \RuntimeException(
						'Cannot write into "apps" directory. This can usually be fixed by giving the web server write access to the apps directory or disabling the App Store in the config file.'
					);
				}
				return $dir['path'];
			}
		}
		return null;
	}

	/**
	 * Downloads an app and puts it into the app directory
	 *
	 * @param string $appId
	 * @param bool [$allowUnstable]
	 *
	 * @throws AppNotFoundException If the app is not found on the appstore
	 * @throws \Exception If the installation was not successful
	 */
	public function downloadApp(string $appId, bool $allowUnstable = false): void {
		$appId = strtolower($appId);

		$installPath = $this->getInstallPath();
		if ($installPath === null) {
			throw new \Exception('No application directories are marked as writable.');
		}

		$apps = $this->appFetcher->get($allowUnstable);
		foreach ($apps as $app) {
			if ($app['id'] === $appId) {
				// Load the certificate
				$certificate = new X509();
				$rootCrt = file_get_contents(__DIR__ . '/../../resources/codesigning/root.crt');
				$rootCrts = $this->splitCerts($rootCrt);
				foreach ($rootCrts as $rootCrt) {
					$certificate->loadCA($rootCrt);
				}
				$loadedCertificate = $certificate->loadX509($app['certificate']);

				// Verify if the certificate has been revoked
				$crl = new X509();
				foreach ($rootCrts as $rootCrt) {
					$crl->loadCA($rootCrt);
				}
				$crl->loadCRL(file_get_contents(__DIR__ . '/../../resources/codesigning/root.crl'));
				if ($crl->validateSignature() !== true) {
					throw new \Exception('Could not validate CRL signature');
				}
				$csn = $loadedCertificate['tbsCertificate']['serialNumber']->toString();
				$revoked = $crl->getRevoked($csn);
				if ($revoked !== false) {
					throw new \Exception(
						sprintf(
							'Certificate "%s" has been revoked',
							$csn
						)
					);
				}

				// Verify if the certificate has been issued by the Nextcloud Code Authority CA
				if ($certificate->validateSignature() !== true) {
					throw new \Exception(
						sprintf(
							'App with id %s has a certificate not issued by a trusted Code Signing Authority',
							$appId
						)
					);
				}

				// Verify if the certificate is issued for the requested app id
				$certInfo = openssl_x509_parse($app['certificate']);
				if (!isset($certInfo['subject']['CN'])) {
					throw new \Exception(
						sprintf(
							'App with id %s has a cert with no CN',
							$appId
						)
					);
				}
				if ($certInfo['subject']['CN'] !== $appId) {
					throw new \Exception(
						sprintf(
							'App with id %s has a cert issued to %s',
							$appId,
							$certInfo['subject']['CN']
						)
					);
				}

				// Download the release
				$tempFile = $this->tempManager->getTemporaryFile('.tar.gz');
				if ($tempFile === false) {
					throw new \RuntimeException('Could not create temporary file for downloading app archive.');
				}

				$timeout = $this->isCLI ? 0 : 120;
				$client = $this->clientService->newClient();
				$client->get($app['releases'][0]['download'], ['sink' => $tempFile, 'timeout' => $timeout]);

				// Check if the signature actually matches the downloaded content
				$certificate = openssl_get_publickey($app['certificate']);
				$verified = openssl_verify(file_get_contents($tempFile), base64_decode($app['releases'][0]['signature']), $certificate, OPENSSL_ALGO_SHA512) === 1;

				if ($verified === true) {
					// Seems to match, let's proceed
					$extractDir = $this->tempManager->getTemporaryFolder();
					if ($extractDir === false) {
						throw new \RuntimeException('Could not create temporary directory for unpacking app.');
					}

					$archive = new TAR($tempFile);
					if (!$archive->extract($extractDir)) {
						$errorMessage = 'Could not extract app ' . $appId;

						$archiveError = $archive->getError();
						if ($archiveError instanceof \PEAR_Error) {
							$errorMessage .= ': ' . $archiveError->getMessage();
						}

						throw new \Exception($errorMessage);
					}
					$allFiles = scandir($extractDir);
					$folders = array_diff($allFiles, ['.', '..']);
					$folders = array_values($folders);

					if (count($folders) < 1) {
						throw new \Exception(
							sprintf(
								'Extracted app %s has no folders',
								$appId
							)
						);
					}

					if (count($folders) > 1) {
						throw new \Exception(
							sprintf(
								'Extracted app %s has more than 1 folder',
								$appId
							)
						);
					}

					// Check if appinfo/info.xml has the same app ID as well
					$xml = simplexml_load_string(file_get_contents($extractDir . '/' . $folders[0] . '/appinfo/info.xml'));

					if ($xml === false) {
						throw new \Exception(
							sprintf(
								'Failed to load info.xml for app id %s',
								$appId,
							)
						);
					}

					if ((string)$xml->id !== $appId) {
						throw new \Exception(
							sprintf(
								'App for id %s has a wrong app ID in info.xml: %s',
								$appId,
								(string)$xml->id
							)
						);
					}

					// Check if the version is lower than before
					$currentVersion = $this->appManager->getAppVersion($appId, true);
					$newVersion = (string)$xml->version;
					if (version_compare($currentVersion, $newVersion) === 1) {
						throw new \Exception(
							sprintf(
								'App for id %s has version %s and tried to update to lower version %s',
								$appId,
								$currentVersion,
								$newVersion
							)
						);
					}

					$baseDir = $installPath . '/' . $appId;
					// Remove old app with the ID if existent
					Files::rmdirr($baseDir);
					// Move to app folder
					if (@mkdir($baseDir)) {
						$extractDir .= '/' . $folders[0];
					}
					// otherwise we just copy the outer directory
					$this->copyRecursive($extractDir, $baseDir);
					Files::rmdirr($extractDir);
					if (function_exists('opcache_reset')) {
						opcache_reset();
					}
					return;
				}
				// Signature does not match
				throw new \Exception(
					sprintf(
						'App with id %s has invalid signature',
						$appId
					)
				);
			}
		}

		throw new AppNotFoundException(
			sprintf(
				'Could not download app %s, it was not found on the appstore',
				$appId
			)
		);
	}

	/**
	 * Check if an update for the app is available
	 *
	 * @param string $appId
	 * @param bool $allowUnstable
	 * @return string|false false or the version number of the update
	 */
	public function isUpdateAvailable($appId, $allowUnstable = false): string|false {
		if ($this->isInstanceReadyForUpdates === null) {
			$installPath = $this->getInstallPath();
			if ($installPath === null) {
				$this->isInstanceReadyForUpdates = false;
			} else {
				$this->isInstanceReadyForUpdates = true;
			}
		}

		if ($this->isInstanceReadyForUpdates === false) {
			return false;
		}

		if ($this->isInstalledFromGit($appId) === true) {
			return false;
		}

		if ($this->apps === null) {
			$this->apps = $this->appFetcher->get($allowUnstable);
		}

		foreach ($this->apps as $app) {
			if ($app['id'] === $appId) {
				$currentVersion = $this->appManager->getAppVersion($appId, true);

				if (!isset($app['releases'][0]['version'])) {
					return false;
				}
				$newestVersion = $app['releases'][0]['version'];
				if ($currentVersion !== '0' && version_compare($newestVersion, $currentVersion, '>')) {
					return $newestVersion;
				} else {
					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Check if app has been installed from git
	 *
	 * The function will check if the path contains a .git folder
	 */
	private function isInstalledFromGit(string $appId): bool {
		try {
			$appPath = $this->appManager->getAppPath($appId);
			return file_exists($appPath . '/.git/');
		} catch (AppPathNotFoundException) {
			return false;
		}
	}

	/**
	 * Check if app is already downloaded
	 *
	 * The function will check if the app is already downloaded in the apps repository
	 */
	public function isDownloaded(string $name): bool {
		foreach (\OC::$APPSROOTS as $dir) {
			$dirToTest = $dir['path'];
			$dirToTest .= '/';
			$dirToTest .= $name;
			$dirToTest .= '/';

			if (is_dir($dirToTest)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Removes an app
	 *
	 * This function works as follows
	 *   -# call uninstall repair steps
	 *   -# removing the files
	 *
	 * The function will not delete preferences, tables and the configuration,
	 * this has to be done by the function oc_app_uninstall().
	 */
	public function removeApp(string $appId): bool {
		if ($this->isDownloaded($appId)) {
			if ($this->appManager->isShipped($appId)) {
				return false;
			}

			$installPath = $this->getInstallPath();
			if ($installPath === null) {
				$this->logger->error('No application directories are marked as writable.', ['app' => 'core']);
				return false;
			}
			$appDir = $installPath . '/' . $appId;
			Files::rmdirr($appDir);
			return true;
		} else {
			$this->logger->error('can\'t remove app ' . $appId . '. It is not installed.');

			return false;
		}
	}

	/**
	 * Installs the app within the bundle and marks the bundle as installed
	 *
	 * @throws \Exception If app could not get installed
	 */
	public function installAppBundle(Bundle $bundle): void {
		$appIds = $bundle->getAppIdentifiers();
		foreach ($appIds as $appId) {
			if (!$this->isDownloaded($appId)) {
				$this->downloadApp($appId);
			}
			$this->installApp($appId);
			$this->appManager->enableApp($appId);
		}
		$bundles = json_decode($this->config->getAppValue('core', 'installed.bundles', json_encode([])), true);
		$bundles[] = $bundle->getIdentifier();
		$this->config->setAppValue('core', 'installed.bundles', json_encode($bundles));
	}

	/**
	 * Installs shipped apps
	 *
	 * This function installs all apps found in the 'apps' directory that should be enabled by default;
	 * @param bool $softErrors When updating we ignore errors and simply log them, better to have a
	 *                         working ownCloud at the end instead of an aborted update.
	 * @return array Array of error messages (appid => Exception)
	 */
	public function installShippedApps(bool $softErrors = false, ?IOutput $output = null): array {
		if ($output instanceof IOutput) {
			$output->debug('Installing shipped apps');
		}
		$errors = [];
		foreach (\OC::$APPSROOTS as $app_dir) {
			if ($dir = opendir($app_dir['path'])) {
				while (false !== ($filename = readdir($dir))) {
					if ($filename[0] !== '.' && is_dir($app_dir['path'] . "/$filename")) {
						if (file_exists($app_dir['path'] . "/$filename/appinfo/info.xml")) {
							if ($this->config->getAppValue($filename, 'installed_version') === '') {
								$enabled = $this->appManager->isDefaultEnabled($filename);
								if (($enabled || in_array($filename, $this->appManager->getAlwaysEnabledApps()))
									  && $this->config->getAppValue($filename, 'enabled') !== 'no') {
									if ($softErrors) {
										try {
											$this->installShippedApp($filename, $output);
										} catch (HintException $e) {
											if ($e->getPrevious() instanceof TableExistsException) {
												$errors[$filename] = $e;
												continue;
											}
											throw $e;
										}
									} else {
										$this->installShippedApp($filename, $output);
									}
									$this->config->setAppValue($filename, 'enabled', 'yes');
								}
							}
						}
					}
				}
				closedir($dir);
			}
		}

		return $errors;
	}

	private function installAppLastSteps(string $appPath, array $info, ?IOutput $output = null, string $enabled = 'no'): string {
		\OC_App::registerAutoloading($info['id'], $appPath);

		$previousVersion = $this->config->getAppValue($info['id'], 'installed_version', '');
		$ms = new MigrationService($info['id'], Server::get(Connection::class));
		if ($output instanceof IOutput) {
			$ms->setOutput($output);
		}
		if ($previousVersion !== '') {
			\OC_App::executeRepairSteps($info['id'], $info['repair-steps']['pre-migration']);
		}

		$ms->migrate('latest', $previousVersion === '');

		if ($previousVersion !== '') {
			\OC_App::executeRepairSteps($info['id'], $info['repair-steps']['post-migration']);
		}

		if ($output instanceof IOutput) {
			$output->debug('Registering tasks of ' . $info['id']);
		}

		// Setup background jobs
		$queue = Server::get(IJobList::class);
		foreach ($info['background-jobs'] as $job) {
			$queue->add($job);
		}

		// Run deprecated appinfo/install.php if any
		$appInstallScriptPath = $appPath . '/appinfo/install.php';
		if (file_exists($appInstallScriptPath)) {
			$this->logger->warning('Using an appinfo/install.php file is deprecated. Application "{app}" still uses one.', [
				'app' => $info['id'],
			]);
			self::includeAppScript($appInstallScriptPath);
		}

		\OC_App::executeRepairSteps($info['id'], $info['repair-steps']['install']);

		// Set the installed version
		$this->config->setAppValue($info['id'], 'installed_version', $this->appManager->getAppVersion($info['id'], false));
		$this->config->setAppValue($info['id'], 'enabled', $enabled);

		// Set remote/public handlers
		foreach ($info['remote'] as $name => $path) {
			$this->config->setAppValue('core', 'remote_' . $name, $info['id'] . '/' . $path);
		}
		foreach ($info['public'] as $name => $path) {
			$this->config->setAppValue('core', 'public_' . $name, $info['id'] . '/' . $path);
		}

		\OC_App::setAppTypes($info['id']);

		return $info['id'];
	}

	/**
	 * install an app already placed in the app folder
	 */
	public function installShippedApp(string $app, ?IOutput $output = null): string|false {
		if ($output instanceof IOutput) {
			$output->debug('Installing ' . $app);
		}
		$info = $this->appManager->getAppInfo($app);
		if (is_null($info) || $info['id'] !== $app) {
			return false;
		}

		$appPath = $this->appManager->getAppPath($app);

		return $this->installAppLastSteps($appPath, $info, $output, 'yes');
	}

	private static function includeAppScript(string $script): void {
		if (file_exists($script)) {
			include $script;
		}
	}

	/**
	 * Recursive copying of local folders.
	 *
	 * @param string $src source folder
	 * @param string $dest target folder
	 */
	private function copyRecursive(string $src, string $dest): void {
		if (!file_exists($src)) {
			return;
		}

		if (is_dir($src)) {
			if (!is_dir($dest)) {
				mkdir($dest);
			}
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != '.' && $file != '..') {
					$this->copyRecursive("$src/$file", "$dest/$file");
				}
			}
		} else {
			$validator = Server::get(FilenameValidator::class);
			if (!$validator->isForbidden($src)) {
				copy($src, $dest);
			}
		}
	}
}
