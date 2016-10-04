<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Borjan Tchakaloff <borjan@tchakaloff.fr>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Kamil Domanski <kdomanski@kdemail.net>
 * @author Klaas Freitag <freitag@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Markus Goetz <markus@woboq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author RealRancor <Fisch.666@gmx.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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
use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OC\Installer;
use OC\OCSClient;
use OC\Repair;
use OCP\App\ManagerEvent;

/**
 * This class manages the apps. It allows them to register and integrate in the
 * ownCloud ecosystem. Furthermore, this class is responsible for installing,
 * upgrading and removing apps.
 */
class OC_App {
	static private $appVersion = [];
	static private $adminForms = array();
	static private $personalForms = array();
	static private $appInfo = array();
	static private $appTypes = array();
	static private $loadedApps = array();
	static private $altLogin = array();
	static private $alreadyRegistered = [];
	const officialApp = 200;

	/**
	 * clean the appId
	 *
	 * @param string|boolean $app AppId that needs to be cleaned
	 * @return string
	 */
	public static function cleanAppId($app) {
		return str_replace(array('\0', '/', '\\', '..'), '', $app);
	}

	/**
	 * Check if an app is loaded
	 *
	 * @param string $app
	 * @return bool
	 */
	public static function isAppLoaded($app) {
		return in_array($app, self::$loadedApps, true);
	}

	/**
	 * loads all apps
	 *
	 * @param string[] | string | null $types
	 * @return bool
	 *
	 * This function walks through the ownCloud directory and loads all apps
	 * it can find. A directory contains an app if the file /appinfo/info.xml
	 * exists.
	 *
	 * if $types is set, only apps of those types will be loaded
	 */
	public static function loadApps($types = null) {
		if (\OC::$server->getSystemConfig()->getValue('maintenance', false)) {
			return false;
		}
		// Load the enabled apps here
		$apps = self::getEnabledApps();

		// Add each apps' folder as allowed class path
		foreach($apps as $app) {
			$path = self::getAppPath($app);
			if($path !== false) {
				self::registerAutoloading($app, $path);
			}
		}

		// prevent app.php from printing output
		ob_start();
		foreach ($apps as $app) {
			if ((is_null($types) or self::isType($app, $types)) && !in_array($app, self::$loadedApps)) {
				self::loadApp($app);
			}
		}
		ob_end_clean();

		return true;
	}

	/**
	 * load a single app
	 *
	 * @param string $app
	 * @param bool $checkUpgrade whether an upgrade check should be done
	 * @throws \OC\NeedsUpdateException
	 */
	public static function loadApp($app, $checkUpgrade = true) {
		self::$loadedApps[] = $app;
		$appPath = self::getAppPath($app);
		if($appPath === false) {
			return;
		}

		// in case someone calls loadApp() directly
		self::registerAutoloading($app, $appPath);

		if (is_file($appPath . '/appinfo/app.php')) {
			\OC::$server->getEventLogger()->start('load_app_' . $app, 'Load app: ' . $app);
			if ($checkUpgrade and self::shouldUpgrade($app)) {
				throw new \OC\NeedsUpdateException();
			}
			self::requireAppFile($app);
			if (self::isType($app, array('authentication'))) {
				// since authentication apps affect the "is app enabled for group" check,
				// the enabled apps cache needs to be cleared to make sure that the
				// next time getEnableApps() is called it will also include apps that were
				// enabled for groups
				self::$enabledAppsCache = array();
			}
			\OC::$server->getEventLogger()->end('load_app_' . $app);
		}
	}

	/**
	 * @internal
	 * @param string $app
	 * @param string $path
	 */
	public static function registerAutoloading($app, $path) {
		$key = $app . '-' . $path;
		if(isset(self::$alreadyRegistered[$key])) {
			return;
		}
		self::$alreadyRegistered[$key] = true;
		// Register on PSR-4 composer autoloader
		$appNamespace = \OC\AppFramework\App::buildAppNamespace($app);
		\OC::$composerAutoloader->addPsr4($appNamespace . '\\', $path . '/lib/', true);
		if (defined('PHPUNIT_RUN') || defined('CLI_TEST_RUN')) {
			\OC::$composerAutoloader->addPsr4($appNamespace . '\\Tests\\', $path . '/tests/', true);
		}

		// Register on legacy autoloader
		\OC::$loader->addValidRoot($path);
	}

	/**
	 * Load app.php from the given app
	 *
	 * @param string $app app name
	 */
	private static function requireAppFile($app) {
		try {
			// encapsulated here to avoid variable scope conflicts
			require_once $app . '/appinfo/app.php';
		} catch (Error $ex) {
			\OC::$server->getLogger()->logException($ex);
			$blacklist = \OC::$server->getAppManager()->getAlwaysEnabledApps();
			if (!in_array($app, $blacklist)) {
				self::disable($app);
			}
		}
	}

	/**
	 * check if an app is of a specific type
	 *
	 * @param string $app
	 * @param string|array $types
	 * @return bool
	 */
	public static function isType($app, $types) {
		if (is_string($types)) {
			$types = array($types);
		}
		$appTypes = self::getAppTypes($app);
		foreach ($types as $type) {
			if (array_search($type, $appTypes) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * get the types of an app
	 *
	 * @param string $app
	 * @return array
	 */
	private static function getAppTypes($app) {
		//load the cache
		if (count(self::$appTypes) == 0) {
			self::$appTypes = \OC::$server->getAppConfig()->getValues(false, 'types');
		}

		if (isset(self::$appTypes[$app])) {
			return explode(',', self::$appTypes[$app]);
		} else {
			return array();
		}
	}

	/**
	 * read app types from info.xml and cache them in the database
	 */
	public static function setAppTypes($app) {
		$appData = self::getAppInfo($app);
		if(!is_array($appData)) {
			return;
		}

		if (isset($appData['types'])) {
			$appTypes = implode(',', $appData['types']);
		} else {
			$appTypes = '';
		}

		\OC::$server->getAppConfig()->setValue($app, 'types', $appTypes);
	}

	/**
	 * check if app is shipped
	 *
	 * @param string $appId the id of the app to check
	 * @return bool
	 *
	 * Check if an app that is installed is a shipped app or installed from the appstore.
	 */
	public static function isShipped($appId) {
		return \OC::$server->getAppManager()->isShipped($appId);
	}

	/**
	 * get all enabled apps
	 */
	protected static $enabledAppsCache = array();

	/**
	 * Returns apps enabled for the current user.
	 *
	 * @param bool $forceRefresh whether to refresh the cache
	 * @param bool $all whether to return apps for all users, not only the
	 * currently logged in one
	 * @return string[]
	 */
	public static function getEnabledApps($forceRefresh = false, $all = false) {
		if (!\OC::$server->getSystemConfig()->getValue('installed', false)) {
			return array();
		}
		// in incognito mode or when logged out, $user will be false,
		// which is also the case during an upgrade
		$appManager = \OC::$server->getAppManager();
		if ($all) {
			$user = null;
		} else {
			$user = \OC::$server->getUserSession()->getUser();
		}

		if (is_null($user)) {
			$apps = $appManager->getInstalledApps();
		} else {
			$apps = $appManager->getEnabledAppsForUser($user);
		}
		$apps = array_filter($apps, function ($app) {
			return $app !== 'files';//we add this manually
		});
		sort($apps);
		array_unshift($apps, 'files');
		return $apps;
	}

	/**
	 * checks whether or not an app is enabled
	 *
	 * @param string $app app
	 * @return bool
	 *
	 * This function checks whether or not an app is enabled.
	 */
	public static function isEnabled($app) {
		return \OC::$server->getAppManager()->isEnabledForUser($app);
	}

	/**
	 * enables an app
	 *
	 * @param mixed $app app
	 * @param array $groups (optional) when set, only these groups will have access to the app
	 * @throws \Exception
	 * @return void
	 *
	 * This function set an app as enabled in appconfig.
	 */
	public static function enable($app, $groups = null) {
		self::$enabledAppsCache = array(); // flush
		if (!Installer::isInstalled($app)) {
			$app = self::installApp($app);
		}

		$appManager = \OC::$server->getAppManager();
		if (!is_null($groups)) {
			$groupManager = \OC::$server->getGroupManager();
			$groupsList = [];
			foreach ($groups as $group) {
				$groupItem = $groupManager->get($group);
				if ($groupItem instanceof \OCP\IGroup) {
					$groupsList[] = $groupManager->get($group);
				}
			}
			$appManager->enableAppForGroups($app, $groupsList);
		} else {
			$appManager->enableApp($app);
		}

		$info = self::getAppInfo($app);
		if(isset($info['settings']) && is_array($info['settings'])) {
			$appPath = self::getAppPath($app);
			self::registerAutoloading($app, $appPath);
			\OC::$server->getSettingsManager()->setupSettings($info['settings']);
		}
	}

	/**
	 * @param string $app
	 * @return int
	 */
	private static function downloadApp($app) {
		$ocsClient = new OCSClient(
			\OC::$server->getHTTPClientService(),
			\OC::$server->getConfig(),
			\OC::$server->getLogger()
		);
		$appData = $ocsClient->getApplication($app, \OCP\Util::getVersion());
		$download = $ocsClient->getApplicationDownload($app, \OCP\Util::getVersion());
		if(isset($download['downloadlink']) and $download['downloadlink']!='') {
			// Replace spaces in download link without encoding entire URL
			$download['downloadlink'] = str_replace(' ', '%20', $download['downloadlink']);
			$info = array('source' => 'http', 'href' => $download['downloadlink'], 'appdata' => $appData);
			$app = Installer::installApp($info);
		}
		return $app;
	}

	/**
	 * @param string $app
	 * @return bool
	 */
	public static function removeApp($app) {
		if (self::isShipped($app)) {
			return false;
		}

		return Installer::removeApp($app);
	}

	/**
	 * This function set an app as disabled in appconfig.
	 *
	 * @param string $app app
	 * @throws Exception
	 */
	public static function disable($app) {
		// Convert OCS ID to regular application identifier
		if(self::getInternalAppIdByOcs($app) !== false) {
			$app = self::getInternalAppIdByOcs($app);
		}

		// flush
		self::$enabledAppsCache = array();

		// run uninstall steps
		$appData = OC_App::getAppInfo($app);
		if (!is_null($appData)) {
			OC_App::executeRepairSteps($app, $appData['repair-steps']['uninstall']);
		}

		// emit disable hook - needed anymore ?
		\OC_Hook::emit('OC_App', 'pre_disable', array('app' => $app));

		// finally disable it
		$appManager = \OC::$server->getAppManager();
		$appManager->disableApp($app);
	}

	/**
	 * Returns the Settings Navigation
	 *
	 * @return string[]
	 *
	 * This function returns an array containing all settings pages added. The
	 * entries are sorted by the key 'order' ascending.
	 */
	public static function getSettingsNavigation() {
		$l = \OC::$server->getL10N('lib');
		$urlGenerator = \OC::$server->getURLGenerator();

		$settings = array();
		// by default, settings only contain the help menu
		if (OC_Util::getEditionString() === '' &&
			\OC::$server->getSystemConfig()->getValue('knowledgebaseenabled', true) == true
		) {
			$settings = array(
				array(
					"id" => "help",
					"order" => 1000,
					"href" => $urlGenerator->linkToRoute('settings_help'),
					"name" => $l->t("Help"),
					"icon" => $urlGenerator->imagePath("settings", "help.svg")
				)
			);
		}

		// if the user is logged-in
		if (OC_User::isLoggedIn()) {
			// personal menu
			$settings[] = array(
				"id" => "personal",
				"order" => 1,
				"href" => $urlGenerator->linkToRoute('settings_personal'),
				"name" => $l->t("Personal"),
				"icon" => $urlGenerator->imagePath("settings", "personal.svg")
			);

			//SubAdmins are also allowed to access user management
			$userObject = \OC::$server->getUserSession()->getUser();
			$isSubAdmin = false;
			if($userObject !== null) {
				$isSubAdmin = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($userObject);
			}
			if ($isSubAdmin) {
				// admin users menu
				$settings[] = array(
					"id" => "core_users",
					"order" => 2,
					"href" => $urlGenerator->linkToRoute('settings_users'),
					"name" => $l->t("Users"),
					"icon" => $urlGenerator->imagePath("settings", "users.svg")
				);
			}

			// if the user is an admin
			if (OC_User::isAdminUser(OC_User::getUser())) {
				// admin settings
				$settings[] = array(
					"id" => "admin",
					"order" => 1000,
					"href" => $urlGenerator->linkToRoute('settings.AdminSettings.index'),
					"name" => $l->t("Admin"),
					"icon" => $urlGenerator->imagePath("settings", "admin.svg")
				);
			}
		}

		$navigation = self::proceedNavigation($settings);
		return $navigation;
	}

	// This is private as well. It simply works, so don't ask for more details
	private static function proceedNavigation($list) {
		$activeApp = OC::$server->getNavigationManager()->getActiveEntry();
		foreach ($list as &$navEntry) {
			if ($navEntry['id'] == $activeApp) {
				$navEntry['active'] = true;
			} else {
				$navEntry['active'] = false;
			}
		}
		unset($navEntry);

		usort($list, function($a, $b) {
			if (isset($a['order']) && isset($b['order'])) {
				return ($a['order'] < $b['order']) ? -1 : 1;
			} else if (isset($a['order']) || isset($b['order'])) {
				return isset($a['order']) ? -1 : 1;
			} else {
				return ($a['name'] < $b['name']) ? -1 : 1;
			}
		});

		return $list;
	}

	/**
	 * Get the path where to install apps
	 *
	 * @return string|false
	 */
	public static function getInstallPath() {
		if (\OC::$server->getSystemConfig()->getValue('appstoreenabled', true) == false) {
			return false;
		}

		foreach (OC::$APPSROOTS as $dir) {
			if (isset($dir['writable']) && $dir['writable'] === true) {
				return $dir['path'];
			}
		}

		\OCP\Util::writeLog('core', 'No application directories are marked as writable.', \OCP\Util::ERROR);
		return null;
	}


	/**
	 * search for an app in all app-directories
	 *
	 * @param string $appId
	 * @return false|string
	 */
	protected static function findAppInDirectories($appId) {
		$sanitizedAppId = self::cleanAppId($appId);
		if($sanitizedAppId !== $appId) {
			return false;
		}
		static $app_dir = array();

		if (isset($app_dir[$appId])) {
			return $app_dir[$appId];
		}

		$possibleApps = array();
		foreach (OC::$APPSROOTS as $dir) {
			if (file_exists($dir['path'] . '/' . $appId)) {
				$possibleApps[] = $dir;
			}
		}

		if (empty($possibleApps)) {
			return false;
		} elseif (count($possibleApps) === 1) {
			$dir = array_shift($possibleApps);
			$app_dir[$appId] = $dir;
			return $dir;
		} else {
			$versionToLoad = array();
			foreach ($possibleApps as $possibleApp) {
				$version = self::getAppVersionByPath($possibleApp['path']);
				if (empty($versionToLoad) || version_compare($version, $versionToLoad['version'], '>')) {
					$versionToLoad = array(
						'dir' => $possibleApp,
						'version' => $version,
					);
				}
			}
			$app_dir[$appId] = $versionToLoad['dir'];
			return $versionToLoad['dir'];
			//TODO - write test
		}
	}

	/**
	 * Get the directory for the given app.
	 * If the app is defined in multiple directories, the first one is taken. (false if not found)
	 *
	 * @param string $appId
	 * @return string|false
	 */
	public static function getAppPath($appId) {
		if ($appId === null || trim($appId) === '') {
			return false;
		}

		if (($dir = self::findAppInDirectories($appId)) != false) {
			return $dir['path'] . '/' . $appId;
		}
		return false;
	}


	/**
	 * check if an app's directory is writable
	 *
	 * @param string $appId
	 * @return bool
	 */
	public static function isAppDirWritable($appId) {
		$path = self::getAppPath($appId);
		return ($path !== false) ? is_writable($path) : false;
	}

	/**
	 * Get the path for the given app on the access
	 * If the app is defined in multiple directories, the first one is taken. (false if not found)
	 *
	 * @param string $appId
	 * @return string|false
	 */
	public static function getAppWebPath($appId) {
		if (($dir = self::findAppInDirectories($appId)) != false) {
			return OC::$WEBROOT . $dir['url'] . '/' . $appId;
		}
		return false;
	}

	/**
	 * get the last version of the app from appinfo/info.xml
	 *
	 * @param string $appId
	 * @return string
	 */
	public static function getAppVersion($appId) {
		if (!isset(self::$appVersion[$appId])) {
			$file = self::getAppPath($appId);
			self::$appVersion[$appId] = ($file !== false) ? self::getAppVersionByPath($file) : '0';
		}
		return self::$appVersion[$appId];
	}

	/**
	 * get app's version based on it's path
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getAppVersionByPath($path) {
		$infoFile = $path . '/appinfo/info.xml';
		$appData = self::getAppInfo($infoFile, true);
		return isset($appData['version']) ? $appData['version'] : '';
	}


	/**
	 * Read all app metadata from the info.xml file
	 *
	 * @param string $appId id of the app or the path of the info.xml file
	 * @param bool $path
	 * @param string $lang
	 * @return array|null
	 * @note all data is read from info.xml, not just pre-defined fields
	 */
	public static function getAppInfo($appId, $path = false, $lang = null) {
		if ($path) {
			$file = $appId;
		} else {
			if ($lang === null && isset(self::$appInfo[$appId])) {
				return self::$appInfo[$appId];
			}
			$appPath = self::getAppPath($appId);
			if($appPath === false) {
				return null;
			}
			$file = $appPath . '/appinfo/info.xml';
		}

		$parser = new \OC\App\InfoParser(\OC::$server->getURLGenerator());
		$data = $parser->parse($file);

		if (is_array($data)) {
			$data = OC_App::parseAppInfo($data, $lang);
		}
		if(isset($data['ocsid'])) {
			$storedId = \OC::$server->getConfig()->getAppValue($appId, 'ocsid');
			if($storedId !== '' && $storedId !== $data['ocsid']) {
				$data['ocsid'] = $storedId;
			}
		}

		if ($lang === null) {
			self::$appInfo[$appId] = $data;
		}

		return $data;
	}

	/**
	 * Returns the navigation
	 *
	 * @return array
	 *
	 * This function returns an array containing all entries added. The
	 * entries are sorted by the key 'order' ascending. Additional to the keys
	 * given for each app the following keys exist:
	 *   - active: boolean, signals if the user is on this navigation entry
	 */
	public static function getNavigation() {
		$entries = OC::$server->getNavigationManager()->getAll();
		$navigation = self::proceedNavigation($entries);
		return $navigation;
	}

	/**
	 * get the id of loaded app
	 *
	 * @return string
	 */
	public static function getCurrentApp() {
		$request = \OC::$server->getRequest();
		$script = substr($request->getScriptName(), strlen(OC::$WEBROOT) + 1);
		$topFolder = substr($script, 0, strpos($script, '/'));
		if (empty($topFolder)) {
			$path_info = $request->getPathInfo();
			if ($path_info) {
				$topFolder = substr($path_info, 1, strpos($path_info, '/', 1) - 1);
			}
		}
		if ($topFolder == 'apps') {
			$length = strlen($topFolder);
			return substr($script, $length + 1, strpos($script, '/', $length + 1) - $length - 1);
		} else {
			return $topFolder;
		}
	}

	/**
	 * @param string $type
	 * @return array
	 */
	public static function getForms($type) {
		$forms = array();
		switch ($type) {
			case 'admin':
				$source = self::$adminForms;
				break;
			case 'personal':
				$source = self::$personalForms;
				break;
			default:
				return array();
		}
		foreach ($source as $form) {
			$forms[] = include $form;
		}
		return $forms;
	}

	/**
	 * register an admin form to be shown
	 *
	 * @param string $app
	 * @param string $page
	 */
	public static function registerAdmin($app, $page) {
		self::$adminForms[] = $app . '/' . $page . '.php';
	}

	/**
	 * register a personal form to be shown
	 * @param string $app
	 * @param string $page
	 */
	public static function registerPersonal($app, $page) {
		self::$personalForms[] = $app . '/' . $page . '.php';
	}

	/**
	 * @param array $entry
	 */
	public static function registerLogIn(array $entry) {
		self::$altLogin[] = $entry;
	}

	/**
	 * @return array
	 */
	public static function getAlternativeLogIns() {
		return self::$altLogin;
	}

	/**
	 * get a list of all apps in the apps folder
	 *
	 * @return array an array of app names (string IDs)
	 * @todo: change the name of this method to getInstalledApps, which is more accurate
	 */
	public static function getAllApps() {

		$apps = array();

		foreach (OC::$APPSROOTS as $apps_dir) {
			if (!is_readable($apps_dir['path'])) {
				\OCP\Util::writeLog('core', 'unable to read app folder : ' . $apps_dir['path'], \OCP\Util::WARN);
				continue;
			}
			$dh = opendir($apps_dir['path']);

			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {

					if ($file[0] != '.' and is_dir($apps_dir['path'] . '/' . $file) and is_file($apps_dir['path'] . '/' . $file . '/appinfo/info.xml')) {

						$apps[] = $file;
					}
				}
			}
		}

		return $apps;
	}

	/**
	 * List all apps, this is used in apps.php
	 *
	 * @param bool $onlyLocal
	 * @param bool $includeUpdateInfo Should we check whether there is an update
	 *                                in the app store?
	 * @param OCSClient $ocsClient
	 * @return array
	 */
	public static function listAllApps($onlyLocal = false,
									   $includeUpdateInfo = true,
									   OCSClient $ocsClient) {
		$installedApps = OC_App::getAllApps();

		//TODO which apps do we want to blacklist and how do we integrate
		// blacklisting with the multi apps folder feature?

		//we don't want to show configuration for these
		$blacklist = \OC::$server->getAppManager()->getAlwaysEnabledApps();
		$appList = array();
		$langCode = \OC::$server->getL10N('core')->getLanguageCode();

		foreach ($installedApps as $app) {
			if (array_search($app, $blacklist) === false) {

				$info = OC_App::getAppInfo($app, false, $langCode);
				if (!is_array($info)) {
					\OCP\Util::writeLog('core', 'Could not read app info file for app "' . $app . '"', \OCP\Util::ERROR);
					continue;
				}

				if (!isset($info['name'])) {
					\OCP\Util::writeLog('core', 'App id "' . $app . '" has no name in appinfo', \OCP\Util::ERROR);
					continue;
				}

				$enabled = \OC::$server->getAppConfig()->getValue($app, 'enabled', 'no');
				$info['groups'] = null;
				if ($enabled === 'yes') {
					$active = true;
				} else if ($enabled === 'no') {
					$active = false;
				} else {
					$active = true;
					$info['groups'] = $enabled;
				}

				$info['active'] = $active;

				if (self::isShipped($app)) {
					$info['internal'] = true;
					$info['level'] = self::officialApp;
					$info['removable'] = false;
				} else {
					$info['internal'] = false;
					$info['removable'] = true;
				}

				$info['update'] = ($includeUpdateInfo) ? Installer::isUpdateAvailable($app) : null;

				$appPath = self::getAppPath($app);
				if($appPath !== false) {
					$appIcon = $appPath . '/img/' . $app . '.svg';
					if (file_exists($appIcon)) {
						$info['preview'] = \OC::$server->getURLGenerator()->imagePath($app, $app . '.svg');
						$info['previewAsIcon'] = true;
					} else {
						$appIcon = $appPath . '/img/app.svg';
						if (file_exists($appIcon)) {
							$info['preview'] = \OC::$server->getURLGenerator()->imagePath($app, 'app.svg');
							$info['previewAsIcon'] = true;
						}
					}
				}
				$info['version'] = OC_App::getAppVersion($app);
				$appList[] = $info;
			}
		}
		if ($onlyLocal) {
			$remoteApps = [];
		} else {
			$remoteApps = OC_App::getAppstoreApps('approved', null, $ocsClient);
		}
		if ($remoteApps) {
			// Remove duplicates
			foreach ($appList as $app) {
				foreach ($remoteApps AS $key => $remote) {
					if ($app['name'] === $remote['name'] ||
						(isset($app['ocsid']) &&
							$app['ocsid'] === $remote['id'])
					) {
						unset($remoteApps[$key]);
					}
				}
			}
			$combinedApps = array_merge($appList, $remoteApps);
		} else {
			$combinedApps = $appList;
		}

		return $combinedApps;
	}

	/**
	 * Returns the internal app ID or false
	 * @param string $ocsID
	 * @return string|false
	 */
	public static function getInternalAppIdByOcs($ocsID) {
		if(is_numeric($ocsID)) {
			$idArray = \OC::$server->getAppConfig()->getValues(false, 'ocsid');
			if(array_search($ocsID, $idArray)) {
				return array_search($ocsID, $idArray);
			}
		}
		return false;
	}

	/**
	 * Get a list of all apps on the appstore
	 * @param string $filter
	 * @param string|null $category
	 * @param OCSClient $ocsClient
	 * @return array|bool  multi-dimensional array of apps.
	 *                     Keys: id, name, type, typename, personid, license, detailpage, preview, changed, description
	 */
	public static function getAppstoreApps($filter = 'approved',
										   $category = null,
										   OCSClient $ocsClient) {
		$categories = [$category];

		if (is_null($category)) {
			$categoryNames = $ocsClient->getCategories(\OCP\Util::getVersion());
			if (is_array($categoryNames)) {
				// Check that categories of apps were retrieved correctly
				if (!$categories = array_keys($categoryNames)) {
					return false;
				}
			} else {
				return false;
			}
		}

		$page = 0;
		$remoteApps = $ocsClient->getApplications($categories, $page, $filter, \OCP\Util::getVersion());
		$apps = [];
		$i = 0;
		$l = \OC::$server->getL10N('core');
		foreach ($remoteApps as $app) {
			$potentialCleanId = self::getInternalAppIdByOcs($app['id']);
			// enhance app info (for example the description)
			$apps[$i] = OC_App::parseAppInfo($app);
			$apps[$i]['author'] = $app['personid'];
			$apps[$i]['ocs_id'] = $app['id'];
			$apps[$i]['internal'] = 0;
			$apps[$i]['active'] = ($potentialCleanId !== false) ? self::isEnabled($potentialCleanId) : false;
			$apps[$i]['update'] = false;
			$apps[$i]['groups'] = false;
			$apps[$i]['score'] = $app['score'];
			$apps[$i]['removable'] = false;
			if ($app['label'] == 'recommended') {
				$apps[$i]['internallabel'] = (string)$l->t('Recommended');
				$apps[$i]['internalclass'] = 'recommendedapp';
			}

			// Apps from the appstore are always assumed to be compatible with the
			// the current release as the initial filtering is done on the appstore
			$apps[$i]['dependencies']['owncloud']['@attributes']['min-version'] = implode('.', \OCP\Util::getVersion());
			$apps[$i]['dependencies']['owncloud']['@attributes']['max-version'] = implode('.', \OCP\Util::getVersion());

			$i++;
		}



		if (empty($apps)) {
			return false;
		} else {
			return $apps;
		}
	}

	public static function shouldUpgrade($app) {
		$versions = self::getAppVersions();
		$currentVersion = OC_App::getAppVersion($app);
		if ($currentVersion && isset($versions[$app])) {
			$installedVersion = $versions[$app];
			if (!version_compare($currentVersion, $installedVersion, '=')) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adjust the number of version parts of $version1 to match
	 * the number of version parts of $version2.
	 *
	 * @param string $version1 version to adjust
	 * @param string $version2 version to take the number of parts from
	 * @return string shortened $version1
	 */
	private static function adjustVersionParts($version1, $version2) {
		$version1 = explode('.', $version1);
		$version2 = explode('.', $version2);
		// reduce $version1 to match the number of parts in $version2
		while (count($version1) > count($version2)) {
			array_pop($version1);
		}
		// if $version1 does not have enough parts, add some
		while (count($version1) < count($version2)) {
			$version1[] = '0';
		}
		return implode('.', $version1);
	}

	/**
	 * Check whether the current ownCloud version matches the given
	 * application's version requirements.
	 *
	 * The comparison is made based on the number of parts that the
	 * app info version has. For example for ownCloud 6.0.3 if the
	 * app info version is expecting version 6.0, the comparison is
	 * made on the first two parts of the ownCloud version.
	 * This means that it's possible to specify "requiremin" => 6
	 * and "requiremax" => 6 and it will still match ownCloud 6.0.3.
	 *
	 * @param string $ocVersion ownCloud version to check against
	 * @param array $appInfo app info (from xml)
	 *
	 * @return boolean true if compatible, otherwise false
	 */
	public static function isAppCompatible($ocVersion, $appInfo) {
		$requireMin = '';
		$requireMax = '';
		if (isset($appInfo['dependencies']['owncloud']['@attributes']['min-version'])) {
			$requireMin = $appInfo['dependencies']['owncloud']['@attributes']['min-version'];
		} else if (isset($appInfo['requiremin'])) {
			$requireMin = $appInfo['requiremin'];
		} else if (isset($appInfo['require'])) {
			$requireMin = $appInfo['require'];
		}

		if (isset($appInfo['dependencies']['owncloud']['@attributes']['max-version'])) {
			$requireMax = $appInfo['dependencies']['owncloud']['@attributes']['max-version'];
		} else if (isset($appInfo['requiremax'])) {
			$requireMax = $appInfo['requiremax'];
		}

		if (is_array($ocVersion)) {
			$ocVersion = implode('.', $ocVersion);
		}

		if (!empty($requireMin)
			&& version_compare(self::adjustVersionParts($ocVersion, $requireMin), $requireMin, '<')
		) {

			return false;
		}

		if (!empty($requireMax)
			&& version_compare(self::adjustVersionParts($ocVersion, $requireMax), $requireMax, '>')
		) {
			return false;
		}

		return true;
	}

	/**
	 * get the installed version of all apps
	 */
	public static function getAppVersions() {
		static $versions;

		if(!$versions) {
			$appConfig = \OC::$server->getAppConfig();
			$versions = $appConfig->getValues(false, 'installed_version');
		}
		return $versions;
	}

	/**
	 * @param string $app
	 * @return bool
	 * @throws Exception if app is not compatible with this version of ownCloud
	 * @throws Exception if no app-name was specified
	 */
	public static function installApp($app) {
		$appName = $app; // $app will be overwritten, preserve name for error logging
		$l = \OC::$server->getL10N('core');
		$config = \OC::$server->getConfig();
		$ocsClient = new OCSClient(
			\OC::$server->getHTTPClientService(),
			$config,
			\OC::$server->getLogger()
		);
		$appData = $ocsClient->getApplication($app, \OCP\Util::getVersion());

		// check if app is a shipped app or not. OCS apps have an integer as id, shipped apps use a string
		if (!is_numeric($app)) {
			$shippedVersion = self::getAppVersion($app);
			if ($appData && version_compare($shippedVersion, $appData['version'], '<')) {
				$app = self::downloadApp($app);
			} else {
				$app = Installer::installShippedApp($app);
			}
		} else {
			// Maybe the app is already installed - compare the version in this
			// case and use the local already installed one.
			// FIXME: This is a horrible hack. I feel sad. The god of code cleanness may forgive me.
			$internalAppId = self::getInternalAppIdByOcs($app);
			if($internalAppId !== false) {
				if($appData && version_compare(\OC_App::getAppVersion($internalAppId), $appData['version'], '<')) {
					$app = self::downloadApp($app);
				} else {
					self::enable($internalAppId);
					$app = $internalAppId;
				}
			} else {
				$app = self::downloadApp($app);
			}
		}

		if ($app !== false) {
			// check if the app is compatible with this version of ownCloud
			$info = self::getAppInfo($app);
			if(!is_array($info)) {
				throw new \Exception(
					$l->t('App "%s" cannot be installed because appinfo file cannot be read.',
						[$info['name']]
					)
				);
			}

			$version = \OCP\Util::getVersion();
			if (!self::isAppCompatible($version, $info)) {
				throw new \Exception(
					$l->t('App "%s" cannot be installed because it is not compatible with this version of the server.',
						array($info['name'])
					)
				);
			}

			// check for required dependencies
			$dependencyAnalyzer = new DependencyAnalyzer(new Platform($config), $l);
			$missing = $dependencyAnalyzer->analyze($info);
			if (!empty($missing)) {
				$missingMsg = join(PHP_EOL, $missing);
				throw new \Exception(
					$l->t('App "%s" cannot be installed because the following dependencies are not fulfilled: %s',
						array($info['name'], $missingMsg)
					)
				);
			}

			$config->setAppValue($app, 'enabled', 'yes');
			if (isset($appData['id'])) {
				$config->setAppValue($app, 'ocsid', $appData['id']);
			}

			if(isset($info['settings']) && is_array($info['settings'])) {
				$appPath = self::getAppPath($app);
				self::registerAutoloading($app, $appPath);
				\OC::$server->getSettingsManager()->setupSettings($info['settings']);
			}

			\OC_Hook::emit('OC_App', 'post_enable', array('app' => $app));
		} else {
			if(empty($appName) ) {
				throw new \Exception($l->t("No app name specified"));
			} else {
				throw new \Exception($l->t("App '%s' could not be installed!", $appName));
			}
		}

		return $app;
	}

	/**
	 * update the database for the app and call the update script
	 *
	 * @param string $appId
	 * @return bool
	 */
	public static function updateApp($appId) {
		$appPath = self::getAppPath($appId);
		if($appPath === false) {
			return false;
		}
		$appData = self::getAppInfo($appId);
		self::executeRepairSteps($appId, $appData['repair-steps']['pre-migration']);
		if (file_exists($appPath . '/appinfo/database.xml')) {
			OC_DB::updateDbFromStructure($appPath . '/appinfo/database.xml');
		}
		self::executeRepairSteps($appId, $appData['repair-steps']['post-migration']);
		self::setupLiveMigrations($appId, $appData['repair-steps']['live-migration']);
		unset(self::$appVersion[$appId]);
		// run upgrade code
		if (file_exists($appPath . '/appinfo/update.php')) {
			self::loadApp($appId, false);
			include $appPath . '/appinfo/update.php';
		}
		self::setupBackgroundJobs($appData['background-jobs']);
		if(isset($appData['settings']) && is_array($appData['settings'])) {
			$appPath = self::getAppPath($appId);
			self::registerAutoloading($appId, $appPath);
			\OC::$server->getSettingsManager()->setupSettings($appData['settings']);
		}

		//set remote/public handlers
		if (array_key_exists('ocsid', $appData)) {
			\OC::$server->getConfig()->setAppValue($appId, 'ocsid', $appData['ocsid']);
		} elseif(\OC::$server->getConfig()->getAppValue($appId, 'ocsid', null) !== null) {
			\OC::$server->getConfig()->deleteAppValue($appId, 'ocsid');
		}
		foreach ($appData['remote'] as $name => $path) {
			\OC::$server->getConfig()->setAppValue('core', 'remote_' . $name, $appId . '/' . $path);
		}
		foreach ($appData['public'] as $name => $path) {
			\OC::$server->getConfig()->setAppValue('core', 'public_' . $name, $appId . '/' . $path);
		}

		self::setAppTypes($appId);

		$version = \OC_App::getAppVersion($appId);
		\OC::$server->getAppConfig()->setValue($appId, 'installed_version', $version);

		\OC::$server->getEventDispatcher()->dispatch(ManagerEvent::EVENT_APP_UPDATE, new ManagerEvent(
			ManagerEvent::EVENT_APP_UPDATE, $appId
		));

		return true;
	}

	/**
	 * @param string $appId
	 * @param string[] $steps
	 * @throws \OC\NeedsUpdateException
	 */
	public static function executeRepairSteps($appId, array $steps) {
		if (empty($steps)) {
			return;
		}
		// load the app
		self::loadApp($appId, false);

		$dispatcher = OC::$server->getEventDispatcher();

		// load the steps
		$r = new Repair([], $dispatcher);
		foreach ($steps as $step) {
			try {
				$r->addStep($step);
			} catch (Exception $ex) {
				$r->emit('\OC\Repair', 'error', [$ex->getMessage()]);
				\OC::$server->getLogger()->logException($ex);
			}
		}
		// run the steps
		$r->run();
	}

	public static function setupBackgroundJobs(array $jobs) {
		$queue = \OC::$server->getJobList();
		foreach ($jobs as $job) {
			$queue->add($job);
		}
	}

	/**
	 * @param string $appId
	 * @param string[] $steps
	 */
	private static function setupLiveMigrations($appId, array $steps) {
		$queue = \OC::$server->getJobList();
		foreach ($steps as $step) {
			$queue->add('OC\Migration\BackgroundRepair', [
				'app' => $appId,
				'step' => $step]);
		}
	}

	/**
	 * @param string $appId
	 * @return \OC\Files\View|false
	 */
	public static function getStorage($appId) {
		if (OC_App::isEnabled($appId)) { //sanity check
			if (OC_User::isLoggedIn()) {
				$view = new \OC\Files\View('/' . OC_User::getUser());
				if (!$view->file_exists($appId)) {
					$view->mkdir($appId);
				}
				return new \OC\Files\View('/' . OC_User::getUser() . '/' . $appId);
			} else {
				\OCP\Util::writeLog('core', 'Can\'t get app storage, app ' . $appId . ', user not logged in', \OCP\Util::ERROR);
				return false;
			}
		} else {
			\OCP\Util::writeLog('core', 'Can\'t get app storage, app ' . $appId . ' not enabled', \OCP\Util::ERROR);
			return false;
		}
	}

	protected static function findBestL10NOption($options, $lang) {
		$fallback = $similarLangFallback = $englishFallback = false;

		$lang = strtolower($lang);
		$similarLang = $lang;
		if (strpos($similarLang, '_')) {
			// For "de_DE" we want to find "de" and the other way around
			$similarLang = substr($lang, 0, strpos($lang, '_'));
		}

		foreach ($options as $option) {
			if (is_array($option)) {
				if ($fallback === false) {
					$fallback = $option['@value'];
				}

				if (!isset($option['@attributes']['lang'])) {
					continue;
				}

				$attributeLang = strtolower($option['@attributes']['lang']);
				if ($attributeLang === $lang) {
					return $option['@value'];
				}

				if ($attributeLang === $similarLang) {
					$similarLangFallback = $option['@value'];
				} else if (strpos($attributeLang, $similarLang . '_') === 0) {
					if ($similarLangFallback === false) {
						$similarLangFallback =  $option['@value'];
					}
				}
			} else {
				$englishFallback = $option;
			}
		}

		if ($similarLangFallback !== false) {
			return $similarLangFallback;
		} else if ($englishFallback !== false) {
			return $englishFallback;
		}
		return (string) $fallback;
	}

	/**
	 * parses the app data array and enhanced the 'description' value
	 *
	 * @param array $data the app data
	 * @param string $lang
	 * @return array improved app data
	 */
	public static function parseAppInfo(array $data, $lang = null) {

		if ($lang && isset($data['name']) && is_array($data['name'])) {
			$data['name'] = self::findBestL10NOption($data['name'], $lang);
		}
		if ($lang && isset($data['summary']) && is_array($data['summary'])) {
			$data['summary'] = self::findBestL10NOption($data['summary'], $lang);
		}
		if ($lang && isset($data['description']) && is_array($data['description'])) {
			$data['description'] = self::findBestL10NOption($data['description'], $lang);
		}

		// just modify the description if it is available
		// otherwise this will create a $data element with an empty 'description'
		if (isset($data['description'])) {
			if (is_string($data['description'])) {
				// sometimes the description contains line breaks and they are then also
				// shown in this way in the app management which isn't wanted as HTML
				// manages line breaks itself

				// first of all we split on empty lines
				$paragraphs = preg_split("!\n[[:space:]]*\n!mu", $data['description']);

				$result = [];
				foreach ($paragraphs as $value) {
					// replace multiple whitespace (tabs, space, newlines) inside a paragraph
					// with a single space - also trims whitespace
					$result[] = trim(preg_replace('![[:space:]]+!mu', ' ', $value));
				}

				// join the single paragraphs with a empty line in between
				$data['description'] = implode("\n\n", $result);

			} else {
				$data['description'] = '';
			}
		}

		return $data;
	}
}
