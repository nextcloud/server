<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christian Weiske <cweiske@cweiske.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Kamil Domanski <kdomanski@kdemail.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author root <root@oc.(none)>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OC\App\CodeChecker\CodeChecker;
use OC\App\CodeChecker\EmptyCheck;
use OC\App\CodeChecker\PrivateCheck;
use OC_App;
use OC_DB;
use OC_Helper;

/**
 * This class provides the functionality needed to install, update and remove plugins/apps
 */
class Installer {

	/**
	 *
	 * This function installs an app. All information needed are passed in the
	 * associative array $data.
	 * The following keys are required:
	 *   - source: string, can be "path" or "http"
	 *
	 * One of the following keys is required:
	 *   - path: path to the file containing the app
	 *   - href: link to the downloadable file containing the app
	 *
	 * The following keys are optional:
	 *   - pretend: boolean, if set true the system won't do anything
	 *   - noinstall: boolean, if true appinfo/install.php won't be loaded
	 *   - inactive: boolean, if set true the appconfig/app.sample.php won't be
	 *     renamed
	 *
	 * This function works as follows
	 *   -# fetching the file
	 *   -# unzipping it
	 *   -# check the code
	 *   -# installing the database at appinfo/database.xml
	 *   -# including appinfo/install.php
	 *   -# setting the installed version
	 *
	 * It is the task of oc_app_install to create the tables and do whatever is
	 * needed to get the app working.
	 *
	 * Installs an app
	 * @param array $data with all information
	 * @throws \Exception
	 * @return integer
	 */
	public static function installApp( $data = array()) {
		$l = \OC::$server->getL10N('lib');

		list($extractDir, $path) = self::downloadApp($data);

		$info = self::checkAppsIntegrity($data, $extractDir, $path);
		$appId = OC_App::cleanAppId($info['id']);
		$basedir = OC_App::getInstallPath().'/'.$appId;
		//check if the destination directory already exists
		if(is_dir($basedir)) {
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http') {
				unlink($path);
			}
			throw new \Exception($l->t("App directory already exists"));
		}

		if(!empty($data['pretent'])) {
			return false;
		}

		//copy the app to the correct place
		if(@!mkdir($basedir)) {
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http') {
				unlink($path);
			}
			throw new \Exception($l->t("Can't create app folder. Please fix permissions. %s", array($basedir)));
		}

		$extractDir .= '/' . $info['id'];
		if(!file_exists($extractDir)) {
			OC_Helper::rmdirr($basedir);
			throw new \Exception($l->t("Archive does not contain a directory named %s", $info['id']));
		}
		OC_Helper::copyr($extractDir, $basedir);

		//remove temporary files
		OC_Helper::rmdirr($extractDir);

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
		\OC::$server->getConfig()->setAppValue($info['id'], 'installed_version', OC_App::getAppVersion($info['id']));
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
	public static function 	isInstalled( $app ) {
		return (\OC::$server->getConfig()->getAppValue($app, "installed_version", null) !== null);
	}

	/**
	 * @brief Update an application
	 * @param array $info
	 * @param bool $isShipped
	 * @throws \Exception
	 * @return bool
	 *
	 * This function could work like described below, but currently it disables and then
	 * enables the app again. This does result in an updated app.
	 *
	 *
	 * This function installs an app. All information needed are passed in the
	 * associative array $info.
	 * The following keys are required:
	 *   - source: string, can be "path" or "http"
	 *
	 * One of the following keys is required:
	 *   - path: path to the file containing the app
	 *   - href: link to the downloadable file containing the app
	 *
	 * The following keys are optional:
	 *   - pretend: boolean, if set true the system won't do anything
	 *   - noupgrade: boolean, if true appinfo/upgrade.php won't be loaded
	 *
	 * This function works as follows
	 *   -# fetching the file
	 *   -# removing the old files
	 *   -# unzipping new file
	 *   -# including appinfo/upgrade.php
	 *   -# setting the installed version
	 *
	 * upgrade.php can determine the current installed version of the app using
	 * "\OC::$server->getAppConfig()->getValue($appid, 'installed_version')"
	 */
	public static function updateApp($info=array(), $isShipped=false) {
		list($extractDir, $path) = self::downloadApp($info);
		$info = self::checkAppsIntegrity($info, $extractDir, $path, $isShipped);

		$currentDir = OC_App::getAppPath($info['id']);
		$basedir  = OC_App::getInstallPath();
		$basedir .= '/';
		$basedir .= $info['id'];

		if($currentDir !== false && is_writable($currentDir)) {
			$basedir = $currentDir;
		}
		if(is_dir($basedir)) {
			OC_Helper::rmdirr($basedir);
		}

		$appInExtractDir = $extractDir;
		if (substr($extractDir, -1) !== '/') {
			$appInExtractDir .= '/';
		}

		$appInExtractDir .= $info['id'];
		OC_Helper::copyr($appInExtractDir, $basedir);
		OC_Helper::rmdirr($extractDir);

		return OC_App::updateApp($info['id']);
	}

	/**
	 * update an app by it's id
	 *
	 * @param integer $ocsId
	 * @return bool
	 * @throws \Exception
	 */
	public static function updateAppByOCSId($ocsId) {
		$ocsClient = new OCSClient(
			\OC::$server->getHTTPClientService(),
			\OC::$server->getConfig(),
			\OC::$server->getLogger()
		);
		$appData = $ocsClient->getApplication($ocsId, \OCP\Util::getVersion());
		$download = $ocsClient->getApplicationDownload($ocsId, \OCP\Util::getVersion());

		if (isset($download['downloadlink']) && trim($download['downloadlink']) !== '') {
			$download['downloadlink'] = str_replace(' ', '%20', $download['downloadlink']);
			$info = array(
				'source' => 'http',
				'href' => $download['downloadlink'],
				'appdata' => $appData
			);
		} else {
			throw new \Exception('Could not fetch app info!');
		}

		return self::updateApp($info);
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws \Exception
	 */
	public static function downloadApp($data = array()) {
		$l = \OC::$server->getL10N('lib');

		if(!isset($data['source'])) {
			throw new \Exception($l->t("No source specified when installing app"));
		}

		//download the file if necessary
		if($data['source']=='http') {
			$pathInfo = pathinfo($data['href']);
			$extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
			$path = \OC::$server->getTempManager()->getTemporaryFile($extension);
			if(!isset($data['href'])) {
				throw new \Exception($l->t("No href specified when installing app from http"));
			}
			$client = \OC::$server->getHTTPClientService()->newClient();
			$client->get($data['href'], ['save_to' => $path]);
		} else {
			if(!isset($data['path'])) {
				throw new \Exception($l->t("No path specified when installing app from local file"));
			}
			$path=$data['path'];
		}

		//detect the archive type
		$mime = \OC::$server->getMimeTypeDetector()->detect($path);
		if ($mime !=='application/zip' && $mime !== 'application/x-gzip' && $mime !== 'application/x-bzip2') {
			throw new \Exception($l->t("Archives of type %s are not supported", array($mime)));
		}

		//extract the archive in a temporary folder
		$extractDir = \OC::$server->getTempManager()->getTemporaryFolder();
		OC_Helper::rmdirr($extractDir);
		mkdir($extractDir);
		if($archive=\OC\Archive\Archive::open($path)) {
			$archive->extract($extractDir);
		} else {
			OC_Helper::rmdirr($extractDir);
			if($data['source']=='http') {
				unlink($path);
			}
			throw new \Exception($l->t("Failed to open archive when installing app"));
		}

		return array(
			$extractDir,
			$path
		);
	}

	/**
	 * check an app's integrity
	 * @param array $data
	 * @param string $extractDir
	 * @param string $path
	 * @param bool $isShipped
	 * @return array
	 * @throws \Exception
	 */
	public static function checkAppsIntegrity($data, $extractDir, $path, $isShipped = false) {
		$l = \OC::$server->getL10N('lib');
		//load the info.xml file of the app
		if(!is_file($extractDir.'/appinfo/info.xml')) {
			//try to find it in a subdir
			$dh=opendir($extractDir);
			if(is_resource($dh)) {
				while (($folder = readdir($dh)) !== false) {
					if($folder[0]!='.' and is_dir($extractDir.'/'.$folder)) {
						if(is_file($extractDir.'/'.$folder.'/appinfo/info.xml')) {
							$extractDir.='/'.$folder;
						}
					}
				}
			}
		}
		if(!is_file($extractDir.'/appinfo/info.xml')) {
			OC_Helper::rmdirr($extractDir);
			if($data['source'] === 'http') {
				unlink($path);
			}
			throw new \Exception($l->t("App does not provide an info.xml file"));
		}

		$info = OC_App::getAppInfo($extractDir.'/appinfo/info.xml', true);
		if(!is_array($info)) {
			throw new \Exception($l->t('App cannot be installed because appinfo file cannot be read.'));
		}

		// We can't trust the parsed info.xml file as it may have been tampered
		// with by an attacker and thus we need to use the local data to check
		// whether the application needs to be signed.
		$appId = OC_App::cleanAppId($data['appdata']['id']);
		$appBelongingToId = OC_App::getInternalAppIdByOcs($appId);
		if(is_string($appBelongingToId)) {
			$previouslySigned = \OC::$server->getConfig()->getAppValue($appBelongingToId, 'signed', 'false');
		} else {
			$appBelongingToId = $info['id'];
			$previouslySigned = 'false';
		}
		if (file_exists($extractDir . '/appinfo/signature.json') || $previouslySigned === 'true') {
			\OC::$server->getConfig()->setAppValue($appBelongingToId, 'signed', 'true');
			$integrityResult = \OC::$server->getIntegrityCodeChecker()->verifyAppSignature(
					$appBelongingToId,
					$extractDir
			);
			if($integrityResult !== []) {
				$e = new \Exception(
						$l->t(
								'Signature could not get checked. Please contact the app developer and check your admin screen.'
						)
				);
				throw $e;
			}
		}

		// check the code for not allowed calls
		if(!$isShipped && !Installer::checkCode($extractDir)) {
			OC_Helper::rmdirr($extractDir);
			throw new \Exception($l->t("App can't be installed because of not allowed code in the App"));
		}

		// check if the app is compatible with this version of ownCloud
		if(!OC_App::isAppCompatible(\OCP\Util::getVersion(), $info)) {
			OC_Helper::rmdirr($extractDir);
			throw new \Exception($l->t("App can't be installed because it is not compatible with this version of ownCloud"));
		}

		// check if shipped tag is set which is only allowed for apps that are shipped with ownCloud
		if(!$isShipped && isset($info['shipped']) && ($info['shipped']=='true')) {
			OC_Helper::rmdirr($extractDir);
			throw new \Exception($l->t("App can't be installed because it contains the <shipped>true</shipped> tag which is not allowed for non shipped apps"));
		}

		// check if the ocs version is the same as the version in info.xml/version
		$version = trim($info['version']);

		if(isset($data['appdata']['version']) && $version<>trim($data['appdata']['version'])) {
			OC_Helper::rmdirr($extractDir);
			throw new \Exception($l->t("App can't be installed because the version in info.xml is not the same as the version reported from the app store"));
		}

		return $info;
	}

	/**
	 * Check if an update for the app is available
	 * @param string $app
	 * @return string|false false or the version number of the update
	 *
	 * The function will check if an update for a version is available
	 */
	public static function isUpdateAvailable( $app ) {
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

		$ocsid=\OC::$server->getAppConfig()->getValue( $app, 'ocsid', '');

		if($ocsid<>'') {
			$ocsClient = new OCSClient(
				\OC::$server->getHTTPClientService(),
				\OC::$server->getConfig(),
				\OC::$server->getLogger()
			);
			$ocsdata = $ocsClient->getApplication($ocsid, \OCP\Util::getVersion());
			$ocsversion= (string) $ocsdata['version'];
			$currentversion=OC_App::getAppVersion($app);
			if (version_compare($ocsversion, $currentversion, '>')) {
				return($ocsversion);
			}else{
				return false;
			}

		}else{
			return false;
		}

	}

	/**
	 * Check if app is already downloaded
	 * @param string $name name of the application to remove
	 * @return boolean
	 *
	 * The function will check if the app is already downloaded in the apps repository
	 */
	public static function isDownloaded( $name ) {
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
	 * @param string $name name of the application to remove
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
	public static function removeApp($appId) {

		if(Installer::isDownloaded( $appId )) {
			$appDir=OC_App::getInstallPath() . '/' . $appId;
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
										} catch (\Doctrine\DBAL\Exception\TableExistsException $e) {
											$errors[$filename] = $e;
											continue;
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
			OC_DB::createDbFromStructure("$appPath/appinfo/database.xml");
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
		$errors = $codeChecker->analyseFolder($folder);

		return empty($errors);
	}

	/**
	 * @param $basedir
	 */
	private static function includeAppScript($script) {
		if ( file_exists($script) ){
			include $script;
		}
	}
}
