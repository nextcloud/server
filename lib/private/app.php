<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek <frank@owncloud.org>
 *
 * @author Jakob Sack
 * @copyright 2012 Jakob Sack <mail@jakobsack.de>
 *
 * @author Georg Ehrke
 * @copyright 2014 Georg Ehrke <georg@ownCloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use OC\App\DependencyAnalyzer;
use OC\App\Platform;

/**
 * This class manages the apps. It allows them to register and integrate in the
 * ownCloud ecosystem. Furthermore, this class is responsible for installing,
 * upgrading and removing apps.
 */
class OC_App {
	static private $settingsForms = array();
	static private $adminForms = array();
	static private $personalForms = array();
	static private $appInfo = array();
	static private $appTypes = array();
	static private $loadedApps = array();
	static private $checkedApps = array();
	static private $altLogin = array();

	/**
	 * clean the appId
	 * @param string|boolean $app AppId that needs to be cleaned
	 * @return string
	 */
	public static function cleanAppId($app) {
		return str_replace(array('\0', '/', '\\', '..'), '', $app);
	}

	/**
	 * loads all apps
	 * @param array $types
	 * @return bool
	 *
	 * This function walks through the ownCloud directory and loads all apps
	 * it can find. A directory contains an app if the file /appinfo/app.php
	 * exists.
	 *
	 * if $types is set, only apps of those types will be loaded
	 */
	public static function loadApps($types = null) {
		if (OC_Config::getValue('maintenance', false)) {
			return false;
		}
		// Load the enabled apps here
		$apps = self::getEnabledApps();
		// prevent app.php from printing output
		ob_start();
		foreach ($apps as $app) {
			if ((is_null($types) or self::isType($app, $types)) && !in_array($app, self::$loadedApps)) {
				self::$loadedApps[] = $app;
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
		if (is_file(self::getAppPath($app) . '/appinfo/app.php')) {
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
	 * Load app.php from the given app
	 *
	 * @param string $app app name
	 */
	private static function requireAppFile($app) {
		// encapsulated here to avoid variable scope conflicts
		require_once $app . '/appinfo/app.php';
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
			self::$appTypes = OC_Appconfig::getValues(false, 'types');
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

		if (isset($appData['types'])) {
			$appTypes = implode(',', $appData['types']);
		} else {
			$appTypes = '';
		}

		OC_Appconfig::setValue($app, 'types', $appTypes);
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
		$info = self::getAppInfo($appId);
		if (isset($info['shipped']) && $info['shipped'] == 'true') {
			return true;
		} else {
			return false;
		}
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
	 * @return array
	 */
	public static function getEnabledApps($forceRefresh = false, $all = false) {
		if (!OC_Config::getValue('installed', false)) {
			return array();
		}
		// in incognito mode or when logged out, $user will be false,
		// which is also the case during an upgrade
		$user = null;
		if (!$all) {
			$user = \OC_User::getUser();
		}
		if (is_string($user) && !$forceRefresh && !empty(self::$enabledAppsCache)) {
			return self::$enabledAppsCache;
		}
		$apps = array();
		$appConfig = \OC::$server->getAppConfig();
		$appStatus = $appConfig->getValues(false, 'enabled');
		foreach ($appStatus as $app => $enabled) {
			if ($app === 'files') {
				continue;
			}
			if ($enabled === 'yes') {
				$apps[] = $app;
			} else if ($enabled !== 'no') {
				$groups = json_decode($enabled);
				if (is_array($groups)) {
					if (is_string($user)) {
						foreach ($groups as $group) {
							if (\OC_Group::inGroup($user, $group)) {
								$apps[] = $app;
								break;
							}
						}
					} else {
						// global, consider app as enabled
						$apps[] = $app;
					}
				}
			}
		}
		sort($apps);
		array_unshift($apps, 'files');
		// Only cache the app list, when the user is logged in.
		// Otherwise we cache the list with disabled apps, although
		// the apps are enabled for the user after he logged in.
		if ($user) {
			self::$enabledAppsCache = $apps;
		}
		return $apps;
	}

	/**
	 * checks whether or not an app is enabled
	 * @param string $app app
	 * @return bool
	 *
	 * This function checks whether or not an app is enabled.
	 */
	public static function isEnabled($app) {
		if ('files' == $app) {
			return true;
		}
		$enabledApps = self::getEnabledApps();
		return in_array($app, $enabledApps);
	}

	/**
	 * enables an app
	 * @param mixed $app app
	 * @param array $groups (optional) when set, only these groups will have access to the app
	 * @throws \Exception
	 * @return void
	 *
	 * This function set an app as enabled in appconfig.
	 */
	public static function enable($app, $groups = null) {
		self::$enabledAppsCache = array(); // flush

		if (!OC_Installer::isInstalled($app)) {
			$app = self::installApp($app);
		}

		if (!is_null($groups)) {
			OC_Appconfig::setValue($app, 'enabled', json_encode($groups));
		}else{
			OC_Appconfig::setValue($app, 'enabled', 'yes');
		}
	}

	/**
	 * @param string $app
	 * @return int
	 */
	public static function downloadApp($app) {
		$appData=OC_OCSClient::getApplication($app);
		$download=OC_OCSClient::getApplicationDownload($app, 1);
		if(isset($download['downloadlink']) and $download['downloadlink']!='') {
			// Replace spaces in download link without encoding entire URL
			$download['downloadlink'] = str_replace(' ', '%20', $download['downloadlink']);
			$info = array('source'=>'http', 'href'=>$download['downloadlink'], 'appdata'=>$appData);
			$app=OC_Installer::installApp($info);
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

		return OC_Installer::removeApp($app);
	}

	/**
	 * This function set an app as disabled in appconfig.
	 * @param string $app app
	 */
	public static function disable($app) {
		if($app === 'files') {
			throw new \Exception("files can't be disabled.");
		}
		self::$enabledAppsCache = array(); // flush
		// check if app is a shipped app or not. if not delete
		\OC_Hook::emit('OC_App', 'pre_disable', array('app' => $app));

		// Convert OCS ID to regular application identifier
		if(self::getInternalAppIdByOcs($app) !== false) {
			$app = self::getInternalAppIdByOcs($app);
		}

		OC_Appconfig::setValue($app, 'enabled', 'no' );
	}

	/**
	 * adds an entry to the navigation
	 * @param array $data array containing the data
	 * @return bool
	 *
	 * This function adds a new entry to the navigation visible to users. $data
	 * is an associative array.
	 * The following keys are required:
	 *   - id: unique id for this entry ('addressbook_index')
	 *   - href: link to the page
	 *   - name: Human readable name ('Addressbook')
	 *
	 * The following keys are optional:
	 *   - icon: path to the icon of the app
	 *   - order: integer, that influences the position of your application in
	 *     the navigation. Lower values come first.
	 */
	public static function addNavigationEntry($data) {
		OC::$server->getNavigationManager()->add($data);
		return true;
	}

	/**
	 * marks a navigation entry as active
	 * @param string $id id of the entry
	 * @return bool
	 *
	 * This function sets a navigation entry as active and removes the 'active'
	 * property from all other entries. The templates can use this for
	 * highlighting the current position of the user.
	 */
	public static function setActiveNavigationEntry($id) {
		OC::$server->getNavigationManager()->setActiveEntry($id);
		return true;
	}

	/**
	 * Get the navigation entries for the $app
	 * @param string $app app
	 * @return array an array of the $data added with addNavigationEntry
	 *
	 * Warning: destroys the existing entries
	 */
	public static function getAppNavigationEntries($app) {
		if (is_file(self::getAppPath($app) . '/appinfo/app.php')) {
			OC::$server->getNavigationManager()->clear();
			require $app . '/appinfo/app.php';
			return OC::$server->getNavigationManager()->getAll();
		}
		return array();
	}

	/**
	 * gets the active Menu entry
	 * @return string id or empty string
	 *
	 * This function returns the id of the active navigation entry (set by
	 * setActiveNavigationEntry
	 */
	public static function getActiveNavigationEntry() {
		return OC::$server->getNavigationManager()->getActiveEntry();
	}

	/**
	 * Returns the Settings Navigation
	 * @return string
	 *
	 * This function returns an array containing all settings pages added. The
	 * entries are sorted by the key 'order' ascending.
	 */
	public static function getSettingsNavigation() {
		$l = \OC::$server->getL10N('lib');

		$settings = array();
		// by default, settings only contain the help menu
		if (OC_Util::getEditionString() === '' &&
			OC_Config::getValue('knowledgebaseenabled', true) == true
		) {
			$settings = array(
				array(
					"id" => "help",
					"order" => 1000,
					"href" => OC_Helper::linkToRoute("settings_help"),
					"name" => $l->t("Help"),
					"icon" => OC_Helper::imagePath("settings", "help.svg")
				)
			);
		}

		// if the user is logged-in
		if (OC_User::isLoggedIn()) {
			// personal menu
			$settings[] = array(
				"id" => "personal",
				"order" => 1,
				"href" => OC_Helper::linkToRoute("settings_personal"),
				"name" => $l->t("Personal"),
				"icon" => OC_Helper::imagePath("settings", "personal.svg")
			);

			// if there are some settings forms
			if (!empty(self::$settingsForms)) {
				// settings menu
				$settings[] = array(
					"id" => "settings",
					"order" => 1000,
					"href" => OC_Helper::linkToRoute("settings_settings"),
					"name" => $l->t("Settings"),
					"icon" => OC_Helper::imagePath("settings", "settings.svg")
				);
			}

			//SubAdmins are also allowed to access user management
			if (OC_SubAdmin::isSubAdmin(OC_User::getUser())) {
				// admin users menu
				$settings[] = array(
					"id" => "core_users",
					"order" => 2,
					"href" => OC_Helper::linkToRoute("settings_users"),
					"name" => $l->t("Users"),
					"icon" => OC_Helper::imagePath("settings", "users.svg")
				);
			}


			// if the user is an admin
			if (OC_User::isAdminUser(OC_User::getUser())) {
				// admin settings
				$settings[] = array(
					"id" => "admin",
					"order" => 1000,
					"href" => OC_Helper::linkToRoute("settings_admin"),
					"name" => $l->t("Admin"),
					"icon" => OC_Helper::imagePath("settings", "admin.svg")
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

		usort($list, create_function('$a, $b', 'if( $a["order"] == $b["order"] ) {return 0;}elseif( $a["order"] < $b["order"] ) {return -1;}else{return 1;}'));

		return $list;
	}

	/**
	 * Get the path where to install apps
	 *
	 * @return string
	 */
	public static function getInstallPath() {
		if (OC_Config::getValue('appstoreenabled', true) == false) {
			return false;
		}

		foreach (OC::$APPSROOTS as $dir) {
			if (isset($dir['writable']) && $dir['writable'] === true) {
				return $dir['path'];
			}
		}

		OC_Log::write('core', 'No application directories are marked as writable.', OC_Log::ERROR);
		return null;
	}


	/**
	 * search for an app in all app-directories
	 * @param $appId
	 * @return mixed (bool|string)
	 */
	protected static function findAppInDirectories($appId) {
		static $app_dir = array();

		if (isset($app_dir[$appId])) {
			return $app_dir[$appId];
		}

		$possibleApps = array();
		foreach(OC::$APPSROOTS as $dir) {
			if(file_exists($dir['path'] . '/' . $appId)) {
				$possibleApps[] = $dir;
			}
		}

		if (empty($possibleApps)) {
			return false;
		} elseif(count($possibleApps) === 1) {
			$dir = array_shift($possibleApps);
			$app_dir[$appId] = $dir;
			return $dir;
		} else {
			$versionToLoad = array();
			foreach($possibleApps as $possibleApp) {
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
	 * get the last version of the app, either from appinfo/version or from appinfo/info.xml
	 *
	 * @param string $appId
	 * @return string
	 */
	public static function getAppVersion($appId) {
		$file = self::getAppPath($appId);
		return ($file !== false) ? self::getAppVersionByPath($file) : '0';
	}

	/**
	 * get app's version based on it's path
	 * @param string $path
	 * @return string
	 */
	public static function getAppVersionByPath($path) {
		$versionFile = $path . '/appinfo/version';
		$infoFile = $path . '/appinfo/info.xml';
		if(is_file($versionFile)) {
			return trim(file_get_contents($versionFile));
		}else{
			$appData = self::getAppInfo($infoFile, true);
			return isset($appData['version']) ? $appData['version'] : '';
		}
	}


	/**
	 * Read all app metadata from the info.xml file
	 *
	 * @param string $appId id of the app or the path of the info.xml file
	 * @param boolean $path (optional)
	 * @return array|null
	 * @note all data is read from info.xml, not just pre-defined fields
	 */
	public static function getAppInfo($appId, $path = false) {
		if ($path) {
			$file = $appId;
		} else {
			if (isset(self::$appInfo[$appId])) {
				return self::$appInfo[$appId];
			}
			$file = self::getAppPath($appId) . '/appinfo/info.xml';
		}

		$parser = new \OC\App\InfoParser(\OC::$server->getHTTPHelper(), \OC::$server->getURLGenerator());
		$data = $parser->parse($file);

		if(is_array($data)) {
			$data = OC_App::parseAppInfo($data);
		}

		self::$appInfo[$appId] = $data;

		return $data;
	}

	/**
	 * Returns the navigation
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
		$script = substr(OC_Request::scriptName(), strlen(OC::$WEBROOT) + 1);
		$topFolder = substr($script, 0, strpos($script, '/'));
		if (empty($topFolder)) {
			$path_info = OC_Request::getPathInfo();
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
	 * get the forms for either settings, admin or personal
	 */
	public static function getForms($type) {
		$forms = array();
		switch ($type) {
			case 'settings':
				$source = self::$settingsForms;
				break;
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
	 * register a settings form to be shown
	 */
	public static function registerSettings($app, $page) {
		self::$settingsForms[] = $app . '/' . $page . '.php';
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
	 */
	public static function registerPersonal($app, $page) {
		self::$personalForms[] = $app . '/' . $page . '.php';
	}

	public static function registerLogIn($entry) {
		self::$altLogin[] = $entry;
	}

	public static function getAlternativeLogIns() {
		return self::$altLogin;
	}

	/**
	 * get a list of all apps in the apps folder
	 * @return array an array of app names (string IDs)
	 * @todo: change the name of this method to getInstalledApps, which is more accurate
	 */
	public static function getAllApps() {

		$apps = array();

		foreach (OC::$APPSROOTS as $apps_dir) {
			if (!is_readable($apps_dir['path'])) {
				OC_Log::write('core', 'unable to read app folder : ' . $apps_dir['path'], OC_Log::WARN);
				continue;
			}
			$dh = opendir($apps_dir['path']);

			if (is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {

					if ($file[0] != '.' and is_file($apps_dir['path'] . '/' . $file . '/appinfo/app.php')) {

						$apps[] = $file;

					}

				}
			}

		}

		return $apps;
	}

	/**
	 * Lists all apps, this is used in apps.php
	 * @return array
	 */
	public static function listAllApps($onlyLocal = false) {
		$installedApps = OC_App::getAllApps();

		//TODO which apps do we want to blacklist and how do we integrate
		// blacklisting with the multi apps folder feature?

		$blacklist = array('files'); //we don't want to show configuration for these
		$appList = array();
		$l = \OC::$server->getL10N('core');

		foreach ($installedApps as $app) {
			if (array_search($app, $blacklist) === false) {

				$info = OC_App::getAppInfo($app);

				if (!isset($info['name'])) {
					OC_Log::write('core', 'App id "' . $app . '" has no name in appinfo', OC_Log::ERROR);
					continue;
				}

				$enabled = OC_Appconfig::getValue($app, 'enabled', 'no');
				$info['groups'] = null;
				if ($enabled === 'yes') {
					$active = true;
				} else if($enabled === 'no') {
					$active = false;
				} else {
					$active = true;
					$info['groups'] = $enabled;
				}

				$info['active'] = $active;

				if(isset($info['shipped']) and ($info['shipped'] == 'true')) {
					$info['internal'] = true;
					$info['internallabel'] = (string)$l->t('Recommended');
					$info['internalclass'] = 'recommendedapp';
					$info['removable'] = false;
				} else {
					$info['internal'] = false;
					$info['removable'] = true;
				}

				$info['update'] = OC_Installer::isUpdateAvailable($app);

				$appIcon = self::getAppPath($app) . '/img/' . $app.'.svg';
				if (file_exists($appIcon)) {
					$info['preview'] = OC_Helper::imagePath($app, $app.'.svg');
					$info['previewAsIcon'] = true;
				} else {
					$appIcon = self::getAppPath($app) . '/img/app.svg';
					if (file_exists($appIcon)) {
						$info['preview'] = OC_Helper::imagePath($app, 'app.svg');
						$info['previewAsIcon'] = true;
					}
				}
				$info['version'] = OC_App::getAppVersion($app);
				$appList[] = $info;
			}
		}
		if ($onlyLocal) {
			$remoteApps = array();
		} else {
			$remoteApps = OC_App::getAppstoreApps();
		}
		if ($remoteApps) {
			// Remove duplicates
			foreach ($appList as $app) {
				foreach ($remoteApps AS $key => $remote) {
					if ($app['name'] === $remote['name'] ||
						(isset($app['ocsid']) &&
						$app['ocsid'] ===  $remote['id'])) {
						unset($remoteApps[$key]);
					}
				}
			}
			$combinedApps = array_merge($appList, $remoteApps);
		} else {
			$combinedApps = $appList;
		}
		// bring the apps into the right order with a custom sort function
		usort($combinedApps, function ($a, $b) {

			// priority 1: active
			if ($a['active'] != $b['active']) {
				return $b['active'] - $a['active'];
			}

			// priority 2: shipped
			$aShipped = (array_key_exists('shipped', $a) && $a['shipped'] === 'true') ? 1 : 0;
			$bShipped = (array_key_exists('shipped', $b) && $b['shipped'] === 'true') ? 1 : 0;
			if ($aShipped !== $bShipped) {
				return ($bShipped - $aShipped);
			}

			// priority 3: recommended
			$internalClassA = isset($a['internalclass']) ? $a['internalclass'] : '';
			$internalClassB = isset($b['internalclass']) ? $b['internalclass'] : '';
			if ($internalClassA != $internalClassB) {
				$aTemp = ($internalClassA == 'recommendedapp' ? 1 : 0);
				$bTemp = ($internalClassB == 'recommendedapp' ? 1 : 0);
				return ($bTemp - $aTemp);
			}

			// priority 4: alphabetical
			return strcasecmp($a['name'], $b['name']);

		});

		return $combinedApps;
	}

	/**
	 * Returns the internal app ID or false
	 * @param string $ocsID
	 * @return string|false
	 */
	protected static function getInternalAppIdByOcs($ocsID) {
		if(is_numeric($ocsID)) {
			$idArray = \OC::$server->getAppConfig()->getValues(false, 'ocsid');
			if(array_search($ocsID, $idArray)) {
				return array_search($ocsID, $idArray);
			}
		}
		return false;
	}

	/**
	 * get a list of all apps on apps.owncloud.com
	 * @return array, multi-dimensional array of apps.
	 *     Keys: id, name, type, typename, personid, license, detailpage, preview, changed, description
	 */
	public static function getAppstoreApps($filter = 'approved', $category = null) {
		$categories = array($category);
		if (is_null($category)) {
			$categoryNames = OC_OCSClient::getCategories();
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
		$remoteApps = OC_OCSClient::getApplications($categories, $page, $filter);
		$app1 = array();
		$i = 0;
		$l = \OC::$server->getL10N('core');
		foreach ($remoteApps as $app) {
			$potentialCleanId = self::getInternalAppIdByOcs($app['id']);
			// enhance app info (for example the description)
			$app1[$i] = OC_App::parseAppInfo($app);
			$app1[$i]['author'] = $app['personid'];
			$app1[$i]['ocs_id'] = $app['id'];
			$app1[$i]['internal'] = 0;
			$app1[$i]['active'] = ($potentialCleanId !== false) ? self::isEnabled($potentialCleanId) : false;
			$app1[$i]['update'] = false;
			$app1[$i]['groups'] = false;
			$app1[$i]['score'] = $app['score'];
			$app1[$i]['removable'] = false;
			if ($app['label'] == 'recommended') {
				$app1[$i]['internallabel'] = (string)$l->t('Recommended');
				$app1[$i]['internalclass'] = 'recommendedapp';
			}

			$i++;
		}

		if (empty($app1)) {
			return false;
		} else {
			return $app1;
		}
	}

	public static function shouldUpgrade($app) {
		$versions = self::getAppVersions();
		$currentVersion = OC_App::getAppVersion($app);
		if ($currentVersion && isset($versions[$app])) {
			$installedVersion = $versions[$app];
			if (version_compare($currentVersion, $installedVersion, '>')) {
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
	 * @param array  $appInfo app info (from xml)
	 *
	 * @return boolean true if compatible, otherwise false
	 */
	public static function isAppCompatible($ocVersion, $appInfo){
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
		if (isset($versions)) { // simple cache, needs to be fixed
			return $versions; // when function is used besides in checkUpgrade
		}
		$versions = array();
		try {
			$query = OC_DB::prepare('SELECT `appid`, `configvalue` FROM `*PREFIX*appconfig`'
				. ' WHERE `configkey` = \'installed_version\'');
			$result = $query->execute();
			while ($row = $result->fetchRow()) {
				$versions[$row['appid']] = $row['configvalue'];
			}
			return $versions;
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * @param mixed $app
	 * @return bool
	 * @throws Exception if app is not compatible with this version of ownCloud
	 * @throws Exception if no app-name was specified
	 */
	public static function installApp($app) {
		$l = \OC::$server->getL10N('core');
		$config = \OC::$server->getConfig();
		$appData=OC_OCSClient::getApplication($app);

		// check if app is a shipped app or not. OCS apps have an integer as id, shipped apps use a string
		if(!is_numeric($app)) {
			$shippedVersion=self::getAppVersion($app);
			if($appData && version_compare($shippedVersion, $appData['version'], '<')) {
				$app = self::downloadApp($app);
			} else {
				$app = OC_Installer::installShippedApp($app);
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

		if($app!==false) {
			// check if the app is compatible with this version of ownCloud
			$info = self::getAppInfo($app);
			$version=OC_Util::getVersion();
			if(!self::isAppCompatible($version, $info)) {
				throw new \Exception(
					$l->t('App \"%s\" can\'t be installed because it is not compatible with this version of ownCloud.',
						array($info['name'])
					)
				);
			}

			// check for required dependencies
			$dependencyAnalyzer = new DependencyAnalyzer(new Platform($config), $l);
			$missing = $dependencyAnalyzer->analyze($app);
			if(!empty($missing)) {
				$missingMsg = join(PHP_EOL, $missing);
				throw new \Exception(
					$l->t('App \"%s\" cannot be installed because the following dependencies are not fulfilled: %s',
						array($info['name'], $missingMsg)
					)
				);
			}

			$config->setAppValue($app, 'enabled', 'yes');
			if(isset($appData['id'])) {
				$config->setAppValue($app, 'ocsid', $appData['id'] );
			}
			\OC_Hook::emit('OC_App', 'post_enable', array('app' => $app));
		}else{
			throw new \Exception($l->t("No app name specified"));
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
		if (file_exists(self::getAppPath($appId) . '/appinfo/database.xml')) {
			OC_DB::updateDbFromStructure(self::getAppPath($appId) . '/appinfo/database.xml');
		}
		if (!self::isEnabled($appId)) {
			return false;
		}
		if (file_exists(self::getAppPath($appId) . '/appinfo/update.php')) {
			self::loadApp($appId, false);
			include self::getAppPath($appId) . '/appinfo/update.php';
		}

		//set remote/public handlers
		$appData = self::getAppInfo($appId);
		if (array_key_exists('ocsid', $appData)) {
			OC_Appconfig::setValue($appId, 'ocsid', $appData['ocsid']);
		}
		foreach ($appData['remote'] as $name => $path) {
			OCP\CONFIG::setAppValue('core', 'remote_' . $name, $appId . '/' . $path);
		}
		foreach ($appData['public'] as $name => $path) {
			OCP\CONFIG::setAppValue('core', 'public_' . $name, $appId . '/' . $path);
		}

		self::setAppTypes($appId);

		$version = \OC_App::getAppVersion($appId);
		\OC_Appconfig::setValue($appId, 'installed_version', $version);

		return true;
	}

	/**
	 * @param string $appId
	 * @return \OC\Files\View
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
				OC_Log::write('core', 'Can\'t get app storage, app ' . $appId . ', user not logged in', OC_Log::ERROR);
				return false;
			}
		} else {
			OC_Log::write('core', 'Can\'t get app storage, app ' . $appId . ' not enabled', OC_Log::ERROR);
			return false;
		}
	}

	/**
	 * parses the app data array and enhanced the 'description' value
	 *
	 * @param array $data the app data
	 * @return array improved app data
	 */
	public static function parseAppInfo(array $data) {

		// just modify the description if it is available
		// otherwise this will create a $data element with an empty 'description'
		if(isset($data['description'])) {
			// sometimes the description contains line breaks and they are then also
			// shown in this way in the app management which isn't wanted as HTML
			// manages line breaks itself

			// first of all we split on empty lines
			$paragraphs = preg_split("!\n[[:space:]]*\n!m", $data['description']);

			$result = [];
			foreach ($paragraphs as $value) {
				// replace multiple whitespace (tabs, space, newlines) inside a paragraph
				// with a single space - also trims whitespace
				$result[] = trim(preg_replace('![[:space:]]+!m', ' ', $value));
			}

			// join the single paragraphs with a empty line in between
			$data['description'] = implode("\n\n", $result);
		}

		return $data;
	}
}
