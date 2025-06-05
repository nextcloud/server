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
use OC_App;
use OCP\App\IAppManager;
use OCP\Files;
use OCP\HintException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\Migration\IOutput;
use OCP\Server;
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
		$app = \OC_App::findAppInDirectories($appId);
		if ($app === false) {
			throw new \Exception('App not found in any app directory');
		}

		$basedir = $app['path'] . '/' . $appId;

		if (is_file($basedir . '/appinfo/database.xml')) {
			throw new \Exception('The appinfo/database.xml file is not longer supported. Used in ' . $appId);
		}

		$l = \OCP\Util::getL10N('core');
		$info = \OCP\Server::get(IAppManager::class)->getAppInfoByPath($basedir . '/appinfo/info.xml', $l->getLanguageCode());

		if (!is_array($info)) {
			throw new \Exception(
				$l->t('App "%s" cannot be installed because appinfo file cannot be read.',
					[$appId]
				)
			);
		}

		$ignoreMaxApps = $this->config->getSystemValue('app_install_overwrite', []);
		$ignoreMax = $forceEnable || in_array($appId, $ignoreMaxApps, true);

		$version = implode('.', \OCP\Util::getVersion());
		if (!\OC_App::isAppCompatible($version, $info, $ignoreMax)) {
			throw new \Exception(
				// TODO $l
				$l->t('App "%s" cannot be installed because it is not compatible with this version of the server.',
					[$info['name']]
				)
			);
		}

		// check for required dependencies
		\OC_App::checkAppDependencies($this->config, $l, $info, $ignoreMax);
		/** @var Coordinator $coordinator */
		$coordinator = \OC::$server->get(Coordinator::class);
		$coordinator->runLazyRegistration($appId);
		\OC_App::registerAutoloading($appId, $basedir);

		$previousVersion = $this->config->getAppValue($info['id'], 'installed_version', false);
		if ($previousVersion) {
			OC_App::executeRepairSteps($appId, $info['repair-steps']['pre-migration']);
		}

		//install the database
		$ms = new MigrationService($info['id'], \OCP\Server::get(Connection::class));
		$ms->migrate('latest', !$previousVersion);

		if ($previousVersion) {
			OC_App::executeRepairSteps($appId, $info['repair-steps']['post-migration']);
		}

		\OC_App::setupBackgroundJobs($info['background-jobs']);

		//run appinfo/install.php
		self::includeAppScript($basedir . '/appinfo/install.php');

		OC_App::executeRepairSteps($appId, $info['repair-steps']['install']);

		$config = \OCP\Server::get(IConfig::class);
		//set the installed version
		$config->setAppValue($info['id'], 'installed_version', \OCP\Server::get(IAppManager::class)->getAppVersion($info['id'], false));
		$config->setAppValue($info['id'], 'enabled', 'no');

		//set remote/public handlers
		foreach ($info['remote'] as $name => $path) {
			$config->setAppValue('core', 'remote_' . $name, $info['id'] . '/' . $path);
		}
		foreach ($info['public'] as $name => $path) {
			$config->setAppValue('core', 'public_' . $name, $info['id'] . '/' . $path);
		}

		OC_App::setAppTypes($info['id']);

		return $info['id'];
	}

	/**
	 * Updates the specified app from the appstore
	 *
	 * @param bool $allowUnstable Allow unstable releases
	 */
	public function updateAppstoreApp(string $appId, bool $allowUnstable = false): bool {
		if ($this->isUpdateAvailable($appId, $allowUnstable)) {
			try {
				$this->downloadApp($appId, $allowUnstable);
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), [
					'exception' => $e,
				]);
				return false;
			}
			return OC_App::updateApp($appId);
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
					$currentVersion = \OCP\Server::get(IAppManager::class)->getAppVersion($appId, true);
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

					$baseDir = OC_App::getInstallPath() . '/' . $appId;
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
			$installPath = OC_App::getInstallPath();
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
				$currentVersion = \OCP\Server::get(IAppManager::class)->getAppVersion($appId, true);

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
		$app = \OC_App::findAppInDirectories($appId);
		if ($app === false) {
			return false;
		}
		$basedir = $app['path'] . '/' . $appId;
		return file_exists($basedir . '/.git/');
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
			if (\OCP\Server::get(IAppManager::class)->isShipped($appId)) {
				return false;
			}
			$appDir = OC_App::getInstallPath() . '/' . $appId;
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
			$app = new OC_App();
			$app->enable($appId);
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
	public static function installShippedApps(bool $softErrors = false, ?IOutput $output = null): array {
		if ($output instanceof IOutput) {
			$output->debug('Installing shipped apps');
		}
		$appManager = \OCP\Server::get(IAppManager::class);
		$config = \OCP\Server::get(IConfig::class);
		$errors = [];
		foreach (\OC::$APPSROOTS as $app_dir) {
			if ($dir = opendir($app_dir['path'])) {
				while (false !== ($filename = readdir($dir))) {
					if ($filename[0] !== '.' and is_dir($app_dir['path'] . "/$filename")) {
						if (file_exists($app_dir['path'] . "/$filename/appinfo/info.xml")) {
							if ($config->getAppValue($filename, 'installed_version', null) === null) {
								$enabled = $appManager->isDefaultEnabled($filename);
								if (($enabled || in_array($filename, $appManager->getAlwaysEnabledApps()))
									  && $config->getAppValue($filename, 'enabled') !== 'no') {
									if ($softErrors) {
										try {
											Installer::installShippedApp($filename, $output);
										} catch (HintException $e) {
											if ($e->getPrevious() instanceof TableExistsException) {
												$errors[$filename] = $e;
												continue;
											}
											throw $e;
										}
									} else {
										Installer::installShippedApp($filename, $output);
									}
									$config->setAppValue($filename, 'enabled', 'yes');
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

	/**
	 * install an app already placed in the app folder
	 */
	public static function installShippedApp(string $app, ?IOutput $output = null): string|false {
		if ($output instanceof IOutput) {
			$output->debug('Installing ' . $app);
		}

		$appManager = \OCP\Server::get(IAppManager::class);
		$config = \OCP\Server::get(IConfig::class);

		$appPath = $appManager->getAppPath($app);
		\OC_App::registerAutoloading($app, $appPath);

		$ms = new MigrationService($app, \OCP\Server::get(Connection::class));
		if ($output instanceof IOutput) {
			$ms->setOutput($output);
		}
		$previousVersion = $config->getAppValue($app, 'installed_version', false);
		$ms->migrate('latest', !$previousVersion);

		//run appinfo/install.php
		self::includeAppScript("$appPath/appinfo/install.php");

		$info = \OCP\Server::get(IAppManager::class)->getAppInfo($app);
		if (is_null($info)) {
			return false;
		}
		if ($output instanceof IOutput) {
			$output->debug('Registering tasks of ' . $app);
		}
		\OC_App::setupBackgroundJobs($info['background-jobs']);

		OC_App::executeRepairSteps($app, $info['repair-steps']['install']);

		$config->setAppValue($app, 'installed_version', \OCP\Server::get(IAppManager::class)->getAppVersion($app));
		if (array_key_exists('ocsid', $info)) {
			$config->setAppValue($app, 'ocsid', $info['ocsid']);
		}

		//set remote/public handlers
		foreach ($info['remote'] as $name => $path) {
			$config->setAppValue('core', 'remote_' . $name, $app . '/' . $path);
		}
		foreach ($info['public'] as $name => $path) {
			$config->setAppValue('core', 'public_' . $name, $app . '/' . $path);
		}

		OC_App::setAppTypes($info['id']);

		return $info['id'];
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
