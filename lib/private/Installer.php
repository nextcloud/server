<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christian Weiske <cweiske@cweiske.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Kamil Domanski <kdomanski@kdemail.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author root <root@oc.(none)>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

use Doctrine\DBAL\Exception\TableExistsException;
use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\CodeChecker\CodeChecker;
use OC\App\CodeChecker\EmptyCheck;
use OC\App\CodeChecker\PrivateCheck;
use OC\Archive\Archive;
use OC\Archive\TAR;
use OC_App;
use OC_DB;
use OC_Helper;
use OCP\Http\Client\IClientService;
use OCP\ILogger;
use OCP\ITempManager;
use phpseclib\File\X509;

/**
 * This class provides the functionality needed to install, update and remove apps
 */
class Installer {
	/** @var AppFetcher */
	private $appFetcher;
	/** @var IClientService */
	private $clientService;
	/** @var ITempManager */
	private $tempManager;
	/** @var ILogger */
	private $logger;

	/**
	 * @param AppFetcher $appFetcher
	 * @param IClientService $clientService
	 * @param ITempManager $tempManager
	 * @param ILogger $logger
	 */
	public function __construct(AppFetcher $appFetcher,
								IClientService $clientService,
								ITempManager $tempManager,
								ILogger $logger) {
		$this->appFetcher = $appFetcher;
		$this->clientService = $clientService;
		$this->tempManager = $tempManager;
		$this->logger = $logger;
	}

	/**
	 * Installs an app that is located in one of the app folders already
	 *
	 * @param string $appId App to install
	 * @throws \Exception
	 * @return integer
	 */
	public function installApp($appId) {
		$app = \OC_App::findAppInDirectories($appId);
		if($app === false) {
			throw new \Exception('App not found in any app directory');
		}

		$basedir = $app['path'].'/'.$appId;
		$info = OC_App::getAppInfo($basedir.'/appinfo/info.xml', true);

		//install the database
		if(is_file($basedir.'/appinfo/database.xml')) {
			if (\OC::$server->getAppConfig()->getValue($info['id'], 'installed_version') === null) {
				OC_DB::createDbFromStructure($basedir.'/appinfo/database.xml');
			} else {
				OC_DB::updateDbFromStructure($basedir.'/appinfo/database.xml');
			}
		}

		\OC_App::setupBackgroundJobs($info['background-jobs']);

		//run appinfo/install.php
		if((!isset($data['noinstall']) or $data['noinstall']==false)) {
			self::includeAppScript($basedir . '/appinfo/install.php');
		}

		$appData = OC_App::getAppInfo($appId);
		OC_App::executeRepairSteps($appId, $appData['repair-steps']['install']);

		//set the installed version
		\OC::$server->getConfig()->setAppValue($info['id'], 'installed_version', OC_App::getAppVersion($info['id'], false));
		\OC::$server->getConfig()->setAppValue($info['id'], 'enabled', 'no');

		//set remote/public handlers
		foreach($info['remote'] as $name=>$path) {
			\OC::$server->getConfig()->setAppValue('core', 'remote_'.$name, $info['id'].'/'.$path);
		}
		foreach($info['public'] as $name=>$path) {
			\OC::$server->getConfig()->setAppValue('core', 'public_'.$name, $info['id'].'/'.$path);
		}

		OC_App::setAppTypes($info['id']);

		return $info['id'];
	}

	/**
	 * @brief checks whether or not an app is installed
	 * @param string $app app
	 * @returns bool
	 *
	 * Checks whether or not an app is installed, i.e. registered in apps table.
	 */
	public static function isInstalled( $app ) {
		return (\OC::$server->getConfig()->getAppValue($app, "installed_version", null) !== null);
	}

	/**
	 * Updates the specified app from the appstore
	 *
	 * @param string $appId
	 * @return bool
	 */
	public function updateAppstoreApp($appId) {
		if(self::isUpdateAvailable($appId, $this->appFetcher)) {
			try {
				$this->downloadApp($appId);
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), ['app' => 'core']);
				return false;
			}
			return OC_App::updateApp($appId);
		}

		return false;
	}

	/**
	 * Downloads an app and puts it into the app directory
	 *
	 * @param string $appId
	 *
	 * @throws \Exception If the installation was not successful
	 */
	public function downloadApp($appId) {
		$appId = strtolower($appId);

		$apps = $this->appFetcher->get();
		foreach($apps as $app) {
			if($app['id'] === $appId) {
				// Load the certificate
				$certificate = new X509();
				$certificate->loadCA(file_get_contents(__DIR__ . '/../../resources/codesigning/root.crt'));
				$loadedCertificate = $certificate->loadX509($app['certificate']);

				// Verify if the certificate has been revoked
				$crl = new X509();
				$crl->loadCA(file_get_contents(__DIR__ . '/../../resources/codesigning/root.crt'));
				$crl->loadCRL(file_get_contents(__DIR__ . '/../../resources/codesigning/root.crl'));
				if($crl->validateSignature() !== true) {
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
				if($certificate->validateSignature() !== true) {
					throw new \Exception(
						sprintf(
							'App with id %s has a certificate not issued by a trusted Code Signing Authority',
							$appId
						)
					);
				}

				// Verify if the certificate is issued for the requested app id
				$certInfo = openssl_x509_parse($app['certificate']);
				if(!isset($certInfo['subject']['CN'])) {
					throw new \Exception(
						sprintf(
							'App with id %s has a cert with no CN',
							$appId
						)
					);
				}
				if($certInfo['subject']['CN'] !== $appId) {
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
				$client = $this->clientService->newClient();
				$client->get($app['releases'][0]['download'], ['save_to' => $tempFile]);

				// Check if the signature actually matches the downloaded content
				$certificate = openssl_get_publickey($app['certificate']);
				$verified = (bool)openssl_verify(file_get_contents($tempFile), base64_decode($app['releases'][0]['signature']), $certificate, OPENSSL_ALGO_SHA512);
				openssl_free_key($certificate);

				if($verified === true) {
					// Seems to match, let's proceed
					$extractDir = $this->tempManager->getTemporaryFolder();
					$archive = new TAR($tempFile);

					if($archive) {
						$archive->extract($extractDir);
						$allFiles = scandir($extractDir);
						$folders = array_diff($allFiles, ['.', '..']);
						$folders = array_values($folders);

						if(count($folders) > 1) {
							throw new \Exception(
								sprintf(
									'Extracted app %s has more than 1 folder',
									$appId
								)
							);
						}

						// Check if appinfo/info.xml has the same app ID as well
						$loadEntities = libxml_disable_entity_loader(false);
						$xml = simplexml_load_file($extractDir . '/' . $folders[0] . '/appinfo/info.xml');
						libxml_disable_entity_loader($loadEntities);
						if((string)$xml->id !== $appId) {
							throw new \Exception(
								sprintf(
									'App for id %s has a wrong app ID in info.xml: %s',
									$appId,
									(string)$xml->id
								)
							);
						}

						// Check if the version is lower than before
						$currentVersion = OC_App::getAppVersion($appId);
						$newVersion = (string)$xml->version;
						if(version_compare($currentVersion, $newVersion) === 1) {
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
						OC_Helper::rmdirr($baseDir);
						// Move to app folder
						if(@mkdir($baseDir)) {
							$extractDir .= '/' . $folders[0];
							OC_Helper::copyr($extractDir, $baseDir);
						}
						OC_Helper::copyr($extractDir, $baseDir);
						OC_Helper::rmdirr($extractDir);
						return;
					} else {
						throw new \Exception(
							sprintf(
								'Could not extract app with ID %s to %s',
								$appId,
								$extractDir
							)
						);
					}
				} else {
					// Signature does not match
					throw new \Exception(
						sprintf(
							'App with id %s has invalid signature',
							$appId
						)
					);
				}
			}
		}

		throw new \Exception(
			sprintf(
				'Could not download app %s',
				$appId
			)
		);
	}

	/**
	 * Check if an update for the app is available
	 *
	 * @param string $appId
	 * @param AppFetcher $appFetcher
	 * @return string|false false or the version number of the update
	 */
	public static function isUpdateAvailable($appId,
									  AppFetcher $appFetcher) {
		static $isInstanceReadyForUpdates = null;

		if ($isInstanceReadyForUpdates === null) {
			$installPath = OC_App::getInstallPath();
			if ($installPath === false || $installPath === null) {
				$isInstanceReadyForUpdates = false;
			} else {
				$isInstanceReadyForUpdates = true;
			}
		}

		if ($isInstanceReadyForUpdates === false) {
			return false;
		}

		$apps = $appFetcher->get();
		foreach($apps as $app) {
			if($app['id'] === $appId) {
				$currentVersion = OC_App::getAppVersion($appId);
				$newestVersion = $app['releases'][0]['version'];
				if (version_compare($newestVersion, $currentVersion, '>')) {
					return $newestVersion;
				} else {
					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Check if app is already downloaded
	 * @param string $name name of the application to remove
	 * @return boolean
	 *
	 * The function will check if the app is already downloaded in the apps repository
	 */
	public function isDownloaded($name) {
		foreach(\OC::$APPSROOTS as $dir) {
			$dirToTest  = $dir['path'];
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
	 * @param string $appId ID of the application to remove
	 * @return boolean
	 *
	 *
	 * This function works as follows
	 *   -# call uninstall repair steps
	 *   -# removing the files
	 *
	 * The function will not delete preferences, tables and the configuration,
	 * this has to be done by the function oc_app_uninstall().
	 */
	public function removeApp($appId) {
		if($this->isDownloaded( $appId )) {
			$appDir = OC_App::getInstallPath() . '/' . $appId;
			OC_Helper::rmdirr($appDir);
			return true;
		}else{
			\OCP\Util::writeLog('core', 'can\'t remove app '.$appId.'. It is not installed.', \OCP\Util::ERROR);

			return false;
		}

	}

	/**
	 * Installs shipped apps
	 *
	 * This function installs all apps found in the 'apps' directory that should be enabled by default;
	 * @param bool $softErrors When updating we ignore errors and simply log them, better to have a
	 *                         working ownCloud at the end instead of an aborted update.
	 * @return array Array of error messages (appid => Exception)
	 */
	public static function installShippedApps($softErrors = false) {
		$errors = [];
		foreach(\OC::$APPSROOTS as $app_dir) {
			if($dir = opendir( $app_dir['path'] )) {
				while( false !== ( $filename = readdir( $dir ))) {
					if( substr( $filename, 0, 1 ) != '.' and is_dir($app_dir['path']."/$filename") ) {
						if( file_exists( $app_dir['path']."/$filename/appinfo/info.xml" )) {
							if(!Installer::isInstalled($filename)) {
								$info=OC_App::getAppInfo($filename);
								$enabled = isset($info['default_enable']);
								if (($enabled || in_array($filename, \OC::$server->getAppManager()->getAlwaysEnabledApps()))
									  && \OC::$server->getConfig()->getAppValue($filename, 'enabled') !== 'no') {
									if ($softErrors) {
										try {
											Installer::installShippedApp($filename);
										} catch (HintException $e) {
											if ($e->getPrevious() instanceof TableExistsException) {
												$errors[$filename] = $e;
												continue;
											}
											throw $e;
										}
									} else {
										Installer::installShippedApp($filename);
									}
									\OC::$server->getConfig()->setAppValue($filename, 'enabled', 'yes');
								}
							}
						}
					}
				}
				closedir( $dir );
			}
		}

		return $errors;
	}

	/**
	 * install an app already placed in the app folder
	 * @param string $app id of the app to install
	 * @return integer
	 */
	public static function installShippedApp($app) {
		//install the database
		$appPath = OC_App::getAppPath($app);
		if(is_file("$appPath/appinfo/database.xml")) {
			try {
				OC_DB::createDbFromStructure("$appPath/appinfo/database.xml");
			} catch (TableExistsException $e) {
				throw new HintException(
					'Failed to enable app ' . $app,
					'Please ask for help via one of our <a href="https://nextcloud.com/support/" target="_blank" rel="noreferrer">support channels</a>.',
					0, $e
				);
			}
		}

		//run appinfo/install.php
		\OC_App::registerAutoloading($app, $appPath);
		self::includeAppScript("$appPath/appinfo/install.php");

		$info = OC_App::getAppInfo($app);
		if (is_null($info)) {
			return false;
		}
		\OC_App::setupBackgroundJobs($info['background-jobs']);

		OC_App::executeRepairSteps($app, $info['repair-steps']['install']);

		$config = \OC::$server->getConfig();

		$config->setAppValue($app, 'installed_version', OC_App::getAppVersion($app));
		if (array_key_exists('ocsid', $info)) {
			$config->setAppValue($app, 'ocsid', $info['ocsid']);
		}

		//set remote/public handlers
		foreach($info['remote'] as $name=>$path) {
			$config->setAppValue('core', 'remote_'.$name, $app.'/'.$path);
		}
		foreach($info['public'] as $name=>$path) {
			$config->setAppValue('core', 'public_'.$name, $app.'/'.$path);
		}

		OC_App::setAppTypes($info['id']);

		if(isset($info['settings']) && is_array($info['settings'])) {
			// requires that autoloading was registered for the app,
			// as happens before running the install.php some lines above
			\OC::$server->getSettingsManager()->setupSettings($info['settings']);
		}

		return $info['id'];
	}

	/**
	 * check the code of an app with some static code checks
	 * @param string $folder the folder of the app to check
	 * @return boolean true for app is o.k. and false for app is not o.k.
	 */
	public static function checkCode($folder) {
		// is the code checker enabled?
		if(!\OC::$server->getConfig()->getSystemValue('appcodechecker', false)) {
			return true;
		}

		$codeChecker = new CodeChecker(new PrivateCheck(new EmptyCheck()));
		$errors = $codeChecker->analyseFolder(basename($folder), $folder);

		return empty($errors);
	}

	/**
	 * @param string $script
	 */
	private static function includeAppScript($script) {
		if ( file_exists($script) ){
			include $script;
		}
	}
}
