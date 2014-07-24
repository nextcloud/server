<?php

/**
 * Class for utility functions
 *
 */

class OC_Util {
	public static $scripts=array();
	public static $styles=array();
	public static $headers=array();
	private static $rootMounted=false;
	private static $fsSetup=false;

	private static function initLocalStorageRootFS() {
		// mount local file backend as root
		$configDataDirectory = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		//first set up the local "root" storage
		\OC\Files\Filesystem::initMounts();
		if(!self::$rootMounted) {
			\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', array('datadir'=>$configDataDirectory), '/');
			self::$rootMounted = true;
		}
	}

	/**
	 * mounting an object storage as the root fs will in essence remove the
	 * necessity of a data folder being present.
	 * TODO make home storage aware of this and use the object storage instead of local disk access
	 * @param array $config containing 'class' and optional 'arguments'
	 */
	private static function initObjectStoreRootFS($config) {
		// check misconfiguration
		if (empty($config['class'])) {
			\OCP\Util::writeLog('files', 'No class given for objectstore', \OCP\Util::ERROR);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = array();
		}

		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);
		// mount with plain / root object store implementation
		$config['class'] = '\OC\Files\ObjectStore\ObjectStoreStorage';

		// mount object storage as root
		\OC\Files\Filesystem::initMounts();
		if(!self::$rootMounted) {
			\OC\Files\Filesystem::mount($config['class'], $config['arguments'], '/');
			self::$rootMounted = true;
		}
	}

	/**
	 * Can be set up
	 * @param string $user
	 * @return boolean
	 * @description configure the initial filesystem based on the configuration
	 */
	public static function setupFS( $user = '' ) {
		//setting up the filesystem twice can only lead to trouble
		if(self::$fsSetup) {
			return false;
		}

		// If we are not forced to load a specific user we load the one that is logged in
		if( $user == "" && OC_User::isLoggedIn()) {
			$user = OC_User::getUser();
		}

		// load all filesystem apps before, so no setup-hook gets lost
		OC_App::loadApps(array('filesystem'));

		// the filesystem will finish when $user is not empty,
		// mark fs setup here to avoid doing the setup from loading
		// OC_Filesystem
		if ($user != '') {
			self::$fsSetup=true;
		}

		//check if we are using an object storage
		$objectStore = OC_Config::getValue( 'objectstore' );
		if ( isset( $objectStore ) ) {
			self::initObjectStoreRootFS($objectStore);
		} else {
			self::initLocalStorageRootFS();
		}

		if ($user != '' && !OCP\User::userExists($user)) {
			return false;
		}

		//if we aren't logged in, there is no use to set up the filesystem
		if( $user != "" ) {
			\OC\Files\Filesystem::addStorageWrapper('oc_quota', function($mountPoint, $storage){
				// set up quota for home storages, even for other users
				// which can happen when using sharing

				/**
				 * @var \OC\Files\Storage\Storage $storage
				 */
				if ($storage->instanceOfStorage('\OC\Files\Storage\Home')
					|| $storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')
				) {
					if (is_object($storage->getUser())) {
						$user = $storage->getUser()->getUID();
						$quota = OC_Util::getUserQuota($user);
						if ($quota !== \OC\Files\SPACE_UNLIMITED) {
							return new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => $quota, 'root' => 'files'));
						}
					}
				}

				return $storage;
			});

			// copy skeleton for local storage only
			if ( ! isset( $objectStore ) ) {
				$userRoot = OC_User::getHome($user);
				$userDirectory = $userRoot . '/files';
				if( !is_dir( $userDirectory )) {
					mkdir( $userDirectory, 0755, true );
					OC_Util::copySkeleton($userDirectory);
				}
			}

			$userDir = '/'.$user.'/files';

			//jail the user into his "home" directory
			\OC\Files\Filesystem::init($user, $userDir);

			$fileOperationProxy = new OC_FileProxy_FileOperations();
			OC_FileProxy::register($fileOperationProxy);

			OC_Hook::emit('OC_Filesystem', 'setup', array('user' => $user, 'user_dir' => $userDir));
		}
		return true;
	}

	/**
	 * check if a password is required for each public link
	 * @return boolean
	 */
	public static function isPublicLinkPasswordRequired() {
		$appConfig = \OC::$server->getAppConfig();
		$enforcePassword = $appConfig->getValue('core', 'shareapi_enforce_links_password', 'no');
		return ($enforcePassword === 'yes') ? true : false;
	}

	/**
	 * check if sharing is disabled for the current user
	 *
	 * @return boolean
	 */
	public static function isSharingDisabledForUser() {
		if (\OC_Appconfig::getValue('core', 'shareapi_exclude_groups', 'no') === 'yes') {
			$user = \OCP\User::getUser();
			$groupsList = \OC_Appconfig::getValue('core', 'shareapi_exclude_groups_list', '');
			$excludedGroups = explode(',', $groupsList);
			$usersGroups = \OC_Group::getUserGroups($user);
			if (!empty($usersGroups)) {
				$remainingGroups = array_diff($usersGroups, $excludedGroups);
				// if the user is only in groups which are disabled for sharing then
				// sharing is also disabled for the user
				if (empty($remainingGroups)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * check if share API enforces a default expire date
	 * @return boolean
	 */
	public static function isDefaultExpireDateEnforced() {
		$isDefaultExpireDateEnabled = \OCP\Config::getAppValue('core', 'shareapi_default_expire_date', 'no');
		$enforceDefaultExpireDate = false;
		if ($isDefaultExpireDateEnabled === 'yes') {
			$value = \OCP\Config::getAppValue('core', 'shareapi_enforce_expire_date', 'no');
			$enforceDefaultExpireDate = ($value === 'yes') ? true : false;
		}

		return $enforceDefaultExpireDate;
	}

	/**
	 * Get the quota of a user
	 * @param string $user
	 * @return int Quota bytes
	 */
	public static function getUserQuota($user){
		$config = \OC::$server->getConfig();
		$userQuota = $config->getUserValue($user, 'files', 'quota', 'default');
		if($userQuota === 'default') {
			$userQuota = $config->getAppValue('files', 'default_quota', 'none');
		}
		if($userQuota === 'none') {
			return \OC\Files\SPACE_UNLIMITED;
		}else{
			return OC_Helper::computerFileSize($userQuota);
		}
	}

	/**
	 * copies the user skeleton files into the fresh user home files
	 * @param string $userDirectory
	 */
	public static function copySkeleton($userDirectory) {
		OC_Util::copyr(\OC::$SERVERROOT.'/core/skeleton' , $userDirectory);
	}

	/**
	 * copies a directory recursively
	 * @param string $source
	 * @param string $target
	 * @return void
	 */
	public static function copyr($source,$target) {
		$dir = opendir($source);
		@mkdir($target);
		while(false !== ( $file = readdir($dir)) ) {
			if ( !\OC\Files\Filesystem::isIgnoredDir($file) ) {
				if ( is_dir($source . '/' . $file) ) {
					OC_Util::copyr($source . '/' . $file , $target . '/' . $file);
				} else {
					copy($source . '/' . $file,$target . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * @return void
	 */
	public static function tearDownFS() {
		\OC\Files\Filesystem::tearDown();
		self::$fsSetup=false;
		self::$rootMounted=false;
	}

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Version');
	}

	/**
	 * get the current installed version string of ownCloud
	 * @return string
	 */
	public static function getVersionString() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_VersionString');
	}

	/**
	 * @description get the current installed edition of ownCloud. There is the community
	 * edition that just returns an empty string and the enterprise edition
	 * that returns "Enterprise".
	 * @return string
	 */
	public static function getEditionString() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Edition');
	}

	/**
	 * @description get the update channel of the current installed of ownCloud.
	 * @return string
	 */
	public static function getChannel() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Channel');
	}

	/**
	 * @description get the build number of the current installed of ownCloud.
	 * @return string
	 */
	public static function getBuild() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Build');
	}

	/**
	 * @description load the version.php into the session as cache
	 */
	private static function loadVersion() {
		$timestamp = filemtime(OC::$SERVERROOT.'/version.php');
		if(!\OC::$server->getSession()->exists('OC_Version') or OC::$server->getSession()->get('OC_Version_Timestamp') != $timestamp) {
			require 'version.php';
			$session = \OC::$server->getSession();
			/** @var $timestamp int */
			$session->set('OC_Version_Timestamp', $timestamp);
			/** @var $OC_Version string */
			$session->set('OC_Version', $OC_Version);
			/** @var $OC_VersionString string */
			$session->set('OC_VersionString', $OC_VersionString);
			/** @var $OC_Edition string */
			$session->set('OC_Edition', $OC_Edition);
			/** @var $OC_Channel string */
			$session->set('OC_Channel', $OC_Channel);
			/** @var $OC_Build string */
			$session->set('OC_Build', $OC_Build);
		}
	}

	/**
	 * add a javascript file
	 *
	 * @param string $application
	 * @param string|null $file filename
	 * @return void
	 */
	public static function addScript( $application, $file = null ) {
		if ( is_null( $file )) {
			$file = $application;
			$application = "";
		}
		if ( !empty( $application )) {
			self::$scripts[] = "$application/js/$file";
		} else {
			self::$scripts[] = "js/$file";
		}
	}

	/**
	 * add a css file
	 *
	 * @param string $application
	 * @param string|null $file filename
	 * @return void
	 */
	public static function addStyle( $application, $file = null ) {
		if ( is_null( $file )) {
			$file = $application;
			$application = "";
		}
		if ( !empty( $application )) {
			self::$styles[] = "$application/css/$file";
		} else {
			self::$styles[] = "css/$file";
		}
	}

	/**
	 * Add a custom element to the header
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 * @return void
	 */
	public static function addHeader( $tag, $attributes, $text='') {
		self::$headers[] = array(
			'tag'=>$tag,
			'attributes'=>$attributes,
			'text'=>$text
		);
	}

	/**
	 * formats a timestamp in the "right" way
	 *
	 * @param int $timestamp
	 * @param bool $dateOnly option to omit time from the result
	 * @return string timestamp
	 * @description adjust to clients timezone if we know it
	 */
	public static function formatDate( $timestamp, $dateOnly=false) {
		if(\OC::$session->exists('timezone')) {
			$systemTimeZone = intval(date('O'));
			$systemTimeZone = (round($systemTimeZone/100, 0)*60) + ($systemTimeZone%100);
			$clientTimeZone = \OC::$session->get('timezone')*60;
			$offset = $clientTimeZone - $systemTimeZone;
			$timestamp = $timestamp + $offset*60;
		}
		$l = OC_L10N::get('lib');
		return $l->l($dateOnly ? 'date' : 'datetime', $timestamp);
	}

	/**
	 * check if the current server configuration is suitable for ownCloud
	 * @return array arrays with error messages and hints
	 */
	public static function checkServer() {
		$l = OC_L10N::get('lib');
		$errors = array();
		$CONFIG_DATADIRECTORY = OC_Config::getValue('datadirectory', OC::$SERVERROOT . '/data');

		if (!self::needUpgrade() && OC_Config::getValue('installed', false)) {
			// this check needs to be done every time
			$errors = self::checkDataDirectoryValidity($CONFIG_DATADIRECTORY);
		}

		// Assume that if checkServer() succeeded before in this session, then all is fine.
		if(\OC::$session->exists('checkServer_succeeded') && \OC::$session->get('checkServer_succeeded')) {
			return $errors;
		}

		$webServerRestart = false;
		//check for database drivers
		if(!(is_callable('sqlite_open') or class_exists('SQLite3'))
			and !is_callable('mysql_connect')
			and !is_callable('pg_connect')
			and !is_callable('oci_connect')) {
			$errors[] = array(
				'error'=> $l->t('No database drivers (sqlite, mysql, or postgresql) installed.'),
				'hint'=>'' //TODO: sane hint
			);
			$webServerRestart = true;
		}

		//common hint for all file permissions error messages
		$permissionsHint = $l->t('Permissions can usually be fixed by '
			.'%sgiving the webserver write access to the root directory%s.',
			array('<a href="'.\OC_Helper::linkToDocs('admin-dir_permissions').'" target="_blank">', '</a>'));

		// Check if config folder is writable.
		if(!is_writable(OC::$configDir) or !is_readable(OC::$configDir)) {
			$errors[] = array(
				'error' => $l->t('Cannot write into "config" directory'),
				'hint' => $l->t('This can usually be fixed by '
					  .'%sgiving the webserver write access to the config directory%s.',
					  array('<a href="'.\OC_Helper::linkToDocs('admin-dir_permissions').'" target="_blank">', '</a>'))
				);
		}

		// Check if there is a writable install folder.
		if(OC_Config::getValue('appstoreenabled', true)) {
			if( OC_App::getInstallPath() === null
				|| !is_writable(OC_App::getInstallPath())
				|| !is_readable(OC_App::getInstallPath()) ) {
				$errors[] = array(
					'error' => $l->t('Cannot write into "apps" directory'),
					'hint' => $l->t('This can usually be fixed by '
						  .'%sgiving the webserver write access to the apps directory%s'
						  .' or disabling the appstore in the config file.',
						  array('<a href="'.\OC_Helper::linkToDocs('admin-dir_permissions').'" target="_blank">', '</a>'))
					);
			}
		}
		// Create root dir.
		if(!is_dir($CONFIG_DATADIRECTORY)) {
			$success=@mkdir($CONFIG_DATADIRECTORY);
			if ($success) {
				$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
			} else {
				$errors[] = array(
					'error' => $l->t('Cannot create "data" directory (%s)', array($CONFIG_DATADIRECTORY)),
					'hint' => $l->t('This can usually be fixed by '
						  .'<a href="%s" target="_blank">giving the webserver write access to the root directory</a>.',
						  array(OC_Helper::linkToDocs('admin-dir_permissions')))
					);
			}
		} else if(!is_writable($CONFIG_DATADIRECTORY) or !is_readable($CONFIG_DATADIRECTORY)) {
			$errors[] = array(
				'error'=>'Data directory ('.$CONFIG_DATADIRECTORY.') not writable by ownCloud',
				'hint'=>$permissionsHint
			);
		} else {
			$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
		}

		if(!OC_Util::isSetLocaleWorking()) {
			$errors[] = array(
				'error' => $l->t('Setting locale to %s failed',
				array('en_US.UTF-8/fr_FR.UTF-8/es_ES.UTF-8/de_DE.UTF-8/ru_RU.UTF-8/'
				     .'pt_BR.UTF-8/it_IT.UTF-8/ja_JP.UTF-8/zh_CN.UTF-8')),
				'hint' => $l->t('Please install one of theses locales on your system and restart your webserver.')
			);
		}

		$moduleHint = $l->t('Please ask your server administrator to install the module.');
		// check if all required php modules are present
		if(!class_exists('ZipArchive')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('zip')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!class_exists('DOMDocument')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('dom')),
				'hint' => $moduleHint
			);
			$webServerRestart =true;
		}
		if(!function_exists('xml_parser_create')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('libxml')),
				'hint' => $moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('mb_detect_encoding')) {
			$errors[] = array(
				'error'=>'PHP module mb multibyte not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('ctype_digit')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('ctype')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('json_encode')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('JSON')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!extension_loaded('gd') || !function_exists('gd_info')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('GD')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('gzencode')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('zlib')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('iconv')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('iconv')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('simplexml_load_string')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('SimpleXML')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(version_compare(phpversion(), '5.3.3', '<')) {
			$errors[] = array(
				'error'=> $l->t('PHP %s or higher is required.', '5.3.3'),
				'hint'=> $l->t('Please ask your server administrator to update PHP to the latest version.'
					.' Your PHP version is no longer supported by ownCloud and the PHP community.')
			);
			$webServerRestart = true;
		}
		if(!defined('PDO::ATTR_DRIVER_NAME')) {
			$errors[] = array(
				'error'=> $l->t('PHP module %s not installed.', array('PDO')),
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if (((strtolower(@ini_get('safe_mode')) == 'on')
			|| (strtolower(@ini_get('safe_mode')) == 'yes')
			|| (strtolower(@ini_get('safe_mode')) == 'true')
			|| (ini_get("safe_mode") == 1 ))) {
			$errors[] = array(
				'error'=> $l->t('PHP Safe Mode is enabled. ownCloud requires that it is disabled to work properly.'),
				'hint'=> $l->t('PHP Safe Mode is a deprecated and mostly useless setting that should be disabled. '
					.'Please ask your server administrator to disable it in php.ini or in your webserver config.')
			);
			$webServerRestart = true;
		}
		if (get_magic_quotes_gpc() == 1 ) {
			$errors[] = array(
				'error'=> $l->t('Magic Quotes is enabled. ownCloud requires that it is disabled to work properly.'),
				'hint'=> $l->t('Magic Quotes is a deprecated and mostly useless setting that should be disabled. '
					.'Please ask your server administrator to disable it in php.ini or in your webserver config.')
			);
			$webServerRestart = true;
		}
		if (!self::isAnnotationsWorking()) {
			$errors[] = array(
				'error'=>'PHP is apparently setup to strip inline doc blocks. This will make several core apps inaccessible.',
				'hint'=>'This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.'
			);
		}

		if($webServerRestart) {
			$errors[] = array(
				'error'=> $l->t('PHP modules have been installed, but they are still listed as missing?'),
				'hint'=> $l->t('Please ask your server administrator to restart the web server.')
			);
		}

		$errors = array_merge($errors, self::checkDatabaseVersion());

		// Cache the result of this function
		\OC::$session->set('checkServer_succeeded', count($errors) == 0);

		return $errors;
	}

	/**
	 * Check the database version
	 * @return array errors array
	 */
	public static function checkDatabaseVersion() {
		$l = OC_L10N::get('lib');
		$errors = array();
		$dbType = \OC_Config::getValue('dbtype', 'sqlite');
		if ($dbType === 'pgsql') {
			// check PostgreSQL version
			try {
				$result = \OC_DB::executeAudited('SHOW SERVER_VERSION');
				$data = $result->fetchRow();
				if (isset($data['server_version'])) {
					$version = $data['server_version'];
					if (version_compare($version, '9.0.0', '<')) {
						$errors[] = array(
							'error' => $l->t('PostgreSQL >= 9 required'),
							'hint' => $l->t('Please upgrade your database version')
						);
					}
				}
			} catch (\Doctrine\DBAL\DBALException $e) {
				\OCP\Util::logException('core', $e);
				$errors[] = array(
					'error' => $l->t('Error occurred while checking PostgreSQL version'),
					'hint' => $l->t('Please make sure you have PostgreSQL >= 9 or'
							.' check the logs for more information about the error')
				);
			}
		}
		return $errors;
	}


	/**
	 * check if there are still some encrypted files stored
	 * @return boolean
	 */
	public static function encryptedFiles() {
		//check if encryption was enabled in the past
		$encryptedFiles = false;
		if (OC_App::isEnabled('files_encryption') === false) {
			$view = new OC\Files\View('/' . OCP\User::getUser());
			$keyfilePath = '/files_encryption/keyfiles';
			if ($view->is_dir($keyfilePath)) {
				$dircontent = $view->getDirectoryContent($keyfilePath);
				if (!empty($dircontent)) {
					$encryptedFiles = true;
				}
			}
		}

		return $encryptedFiles;
	}

	/**
	 * check if a backup from the encryption keys exists
	 * @return boolean
	 */
	public static function backupKeysExists() {
		//check if encryption was enabled in the past
		$backupExists = false;
		if (OC_App::isEnabled('files_encryption') === false) {
			$view = new OC\Files\View('/' . OCP\User::getUser());
			$backupPath = '/files_encryption/keyfiles.backup';
			if ($view->is_dir($backupPath)) {
				$dircontent = $view->getDirectoryContent($backupPath);
				if (!empty($dircontent)) {
					$backupExists = true;
				}
			}
		}

		return $backupExists;
	}

	/**
	 * Check for correct file permissions of data directory
	 * @param string $dataDirectory
	 * @return array arrays with error messages and hints
	 */
	public static function checkDataDirectoryPermissions($dataDirectory) {
		$l = OC_L10N::get('lib');
		$errors = array();
		if (self::runningOnWindows()) {
			//TODO: permissions checks for windows hosts
		} else {
			$permissionsModHint = $l->t('Please change the permissions to 0770 so that the directory'
				.' cannot be listed by other users.');
			$perms = substr(decoct(@fileperms($dataDirectory)), -3);
			if (substr($perms, -1) != '0') {
				chmod($dataDirectory, 0770);
				clearstatcache();
				$perms = substr(decoct(@fileperms($dataDirectory)), -3);
				if (substr($perms, 2, 1) != '0') {
					$errors[] = array(
						'error' => $l->t('Data directory (%s) is readable by other users', array($dataDirectory)),
						'hint' => $permissionsModHint
					);
				}
			}
		}
		return $errors;
	}

	/**
	 * Check that the data directory exists and is valid by
	 * checking the existence of the ".ocdata" file.
	 *
	 * @param string $dataDirectory data directory path
	 * @return bool true if the data directory is valid, false otherwise
	 */
	public static function checkDataDirectoryValidity($dataDirectory) {
		$l = OC_L10N::get('lib');
		$errors = array();
		if (!file_exists($dataDirectory.'/.ocdata')) {
			$errors[] = array(
				'error' => $l->t('Data directory (%s) is invalid', array($dataDirectory)),
				'hint' => $l->t('Please check that the data directory contains a file' .
					' ".ocdata" in its root.')
			);
		}
		return $errors;
	}

	/**
	 * @param array $errors
	 */
	public static function displayLoginPage($errors = array()) {
		$parameters = array();
		foreach( $errors as $value ) {
			$parameters[$value] = true;
		}
		if (!empty($_POST['user'])) {
			$parameters["username"] = $_POST['user'];
			$parameters['user_autofocus'] = false;
		} else {
			$parameters["username"] = '';
			$parameters['user_autofocus'] = true;
		}
		if (isset($_REQUEST['redirect_url'])) {
			$redirectUrl = $_REQUEST['redirect_url'];
			$parameters['redirect_url'] = urlencode($redirectUrl);
		}

		$parameters['alt_login'] = OC_App::getAlternativeLogIns();
		$parameters['rememberLoginAllowed'] = self::rememberLoginAllowed();
		OC_Template::printGuestPage("", "login", $parameters);
	}


	/**
	 * Check if the app is enabled, redirects to home if not
	 * @param string $app
	 * @return void
	 */
	public static function checkAppEnabled($app) {
		if( !OC_App::isEnabled($app)) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
	}

	/**
	 * Check if the user is logged in, redirects to home if not. With
	 * redirect URL parameter to the request URI.
	 * @return void
	 */
	public static function checkLoggedIn() {
		// Check if we are a user
		if( !OC_User::isLoggedIn()) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php',
				array('redirect_url' => OC_Request::requestUri())
			));
			exit();
		}
	}

	/**
	 * Check if the user is a admin, redirects to home if not
	 * @return void
	 */
	public static function checkAdminUser() {
		OC_Util::checkLoggedIn();
		if( !OC_User::isAdminUser(OC_User::getUser())) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
	}

	/**
	 * Check if it is allowed to remember login.
	 *
	 * @note Every app can set 'rememberlogin' to 'false' to disable the remember login feature
	 *
	 * @return bool
	 */
	public static function rememberLoginAllowed() {

		$apps = OC_App::getEnabledApps();

		foreach ($apps as $app) {
			$appInfo = OC_App::getAppInfo($app);
			if (isset($appInfo['rememberlogin']) && $appInfo['rememberlogin'] === 'false') {
				return false;
			}

		}
		return true;
	}

	/**
	 * Check if the user is a subadmin, redirects to home if not
	 * @return null|boolean $groups where the current user is subadmin
	 */
	public static function checkSubAdminUser() {
		OC_Util::checkLoggedIn();
		if(!OC_SubAdmin::isSubAdmin(OC_User::getUser())) {
			header( 'Location: '.OC_Helper::linkToAbsolute( '', 'index.php' ));
			exit();
		}
		return true;
	}

	/**
	 * Returns the URL of the default page
	 * based on the system configuration and
	 * the apps visible for the current user
	 *
	 * @return string URL
	 */
	public static function getDefaultPageUrl() {
		$urlGenerator = \OC::$server->getURLGenerator();
		if(isset($_REQUEST['redirect_url'])) {
			$location = urldecode($_REQUEST['redirect_url']);
		} else {
			$defaultPage = OC_Appconfig::getValue('core', 'defaultpage');
			if ($defaultPage) {
				$location = $urlGenerator->getAbsoluteURL($defaultPage);
			} else {
				$appId = 'files';
				$defaultApps = explode(',', \OCP\Config::getSystemValue('defaultapp', 'files'));
				// find the first app that is enabled for the current user
				foreach ($defaultApps as $defaultApp) {
					$defaultApp = OC_App::cleanAppId(strip_tags($defaultApp));
					if (OC_App::isEnabled($defaultApp)) {
						$appId = $defaultApp;
						break;
					}
				}
				$location = $urlGenerator->getAbsoluteURL('/index.php/apps/' . $appId . '/');
			}
		}
		return $location;
	}

	/**
	 * Redirect to the user default page
	 * @return void
	 */
	public static function redirectToDefaultPage() {
		$location = self::getDefaultPageUrl();
		header('Location: '.$location);
		exit();
	}

	/**
	 * get an id unique for this instance
	 * @return string
	 */
	public static function getInstanceId() {
		$id = OC_Config::getValue('instanceid', null);
		if(is_null($id)) {
			// We need to guarantee at least one letter in instanceid so it can be used as the session_name
			$id = 'oc' . self::generateRandomBytes(10);
			OC_Config::$object->setValue('instanceid', $id);
		}
		return $id;
	}

	/**
	 * Static lifespan (in seconds) when a request token expires.
	 * @see OC_Util::callRegister()
	 * @see OC_Util::isCallRegistered()
	 * @description
	 * Also required for the client side to compute the point in time when to
	 * request a fresh token. The client will do so when nearly 97% of the
	 * time span coded here has expired.
	 */
	public static $callLifespan = 3600; // 3600 secs = 1 hour

	/**
	 * Register an get/post call. Important to prevent CSRF attacks.
	 * @todo Write howto: CSRF protection guide
	 * @return string Generated token.
	 * @description
	 * Creates a 'request token' (random) and stores it inside the session.
	 * Ever subsequent (ajax) request must use such a valid token to succeed,
	 * otherwise the request will be denied as a protection against CSRF.
	 * The tokens expire after a fixed lifespan.
	 * @see OC_Util::$callLifespan
	 * @see OC_Util::isCallRegistered()
	 */
	public static function callRegister() {
		// Check if a token exists
		if(!\OC::$session->exists('requesttoken')) {
			// No valid token found, generate a new one.
			$requestToken = self::generateRandomBytes(20);
			\OC::$session->set('requesttoken', $requestToken);
		} else {
			// Valid token already exists, send it
			$requestToken = \OC::$session->get('requesttoken');
		}
		return($requestToken);
	}

	/**
	 * Check an ajax get/post call if the request token is valid.
	 * @return boolean False if request token is not set or is invalid.
	 * @see OC_Util::$callLifespan
	 * @see OC_Util::callRegister()
	 */
	public static function isCallRegistered() {
		return \OC::$server->getRequest()->passesCSRFCheck();
	}

	/**
	 * Check an ajax get/post call if the request token is valid. Exit if not.
	 * @todo Write howto
	 * @return void
	 */
	public static function callCheck() {
		if(!OC_Util::isCallRegistered()) {
			exit();
		}
	}

	/**
	 * Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|array &$value
	 * @return string|array an array of sanitized strings or a single sanitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML( &$value ) {
		if (is_array($value)) {
			array_walk_recursive($value, 'OC_Util::sanitizeHTML');
		} else {
			//Specify encoding for PHP<5.4
			$value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		return $value;
	}

	/**
	 * Public function to encode url parameters
	 *
	 * This function is used to encode path to file before output.
	 * Encoding is done according to RFC 3986 with one exception:
	 * Character '/' is preserved as is.
	 *
	 * @param string $component part of URI to encode
	 * @return string
	 */
	public static function encodePath($component) {
		$encoded = rawurlencode($component);
		$encoded = str_replace('%2F', '/', $encoded);
		return $encoded;
	}

	/**
	 * Check if the .htaccess file is working
	 * @throws OC\HintException If the testfile can't get written.
	 * @return bool
	 * @description Check if the .htaccess file is working by creating a test
	 * file in the data directory and trying to access via http
	 */
	public static function isHtaccessWorking() {
		if (!\OC_Config::getValue("check_for_working_htaccess", true)) {
			return true;
		}

		// testdata
		$fileName = '/htaccesstest.txt';
		$testContent = 'testcontent';

		// creating a test file
		$testFile = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" ).'/'.$fileName;

		if(file_exists($testFile)) {// already running this test, possible recursive call
			return false;
		}

		$fp = @fopen($testFile, 'w');
		if (!$fp) {
			throw new OC\HintException('Can\'t create test file to check for working .htaccess file.',
				'Make sure it is possible for the webserver to write to '.$testFile);
		}
		fwrite($fp, $testContent);
		fclose($fp);

		// accessing the file via http
		$url = OC_Helper::makeURLAbsolute(OC::$WEBROOT.'/data'.$fileName);
		$fp = @fopen($url, 'r');
		$content=@fread($fp, 2048);
		@fclose($fp);

		// cleanup
		@unlink($testFile);

		// does it work ?
		if($content==$testContent) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * test if webDAV is working properly
	 * @return bool
	 * @description
	 * The basic assumption is that if the server returns 401/Not Authenticated for an unauthenticated PROPFIND
	 * the web server it self is setup properly.
	 *
	 * Why not an authenticated PROPFIND and other verbs?
	 *  - We don't have the password available
	 *  - We have no idea about other auth methods implemented (e.g. OAuth with Bearer header)
	 *
	 */
	public static function isWebDAVWorking() {
		if (!function_exists('curl_init')) {
			return true;
		}
		if (!\OC_Config::getValue("check_for_working_webdav", true)) {
			return true;
		}
		$settings = array(
			'baseUri' => OC_Helper::linkToRemote('webdav'),
		);

		$client = new \OC_DAVClient($settings);

		$client->setRequestTimeout(10);

		// for this self test we don't care if the ssl certificate is self signed and the peer cannot be verified.
		$client->setVerifyPeer(false);
		// also don't care if the host can't be verified
		$client->setVerifyHost(0);

		$return = true;
		try {
			// test PROPFIND
			$client->propfind('', array('{DAV:}resourcetype'));
		} catch (\Sabre\DAV\Exception\NotAuthenticated $e) {
			$return = true;
		} catch (\Exception $e) {
			OC_Log::write('core', 'isWebDAVWorking: NO - Reason: '.$e->getMessage(). ' ('.get_class($e).')', OC_Log::WARN);
			$return = false;
		}

		return $return;
	}

	/**
	 * Check if the setlocal call does not work. This can happen if the right
	 * local packages are not available on the server.
	 * @return bool
	 */
	public static function isSetLocaleWorking() {
		// setlocale test is pointless on Windows
		if (OC_Util::runningOnWindows() ) {
			return true;
		}

		\Patchwork\Utf8\Bootup::initLocale();
		if ('' === basename('§')) {
			return false;
		}
		return true;
	}

	/**
	 * Check if it's possible to get the inline annotations
	 *
	 * @return bool
	 */
	public static function isAnnotationsWorking() {
		$reflection = new \ReflectionMethod(__METHOD__);
		$docs = $reflection->getDocComment();

		return (is_string($docs) && strlen($docs) > 50);
	}

	/**
	 * Check if the PHP module fileinfo is loaded.
	 * @return bool
	 */
	public static function fileInfoLoaded() {
		return function_exists('finfo_open');
	}

	/**
	 * Check if a PHP version older then 5.3.8 is installed.
	 * @return bool
	 */
	public static function isPHPoutdated() {
		return version_compare(phpversion(), '5.3.8', '<');
	}

	/**
	 * Check if the ownCloud server can connect to the internet
	 * @return bool
	 */
	public static function isInternetConnectionWorking() {
		// in case there is no internet connection on purpose return false
		if (self::isInternetConnectionEnabled() === false) {
			return false;
		}

		// in case the connection is via proxy return true to avoid connecting to owncloud.org
		if(OC_Config::getValue('proxy', '') != '') {
			return true;
		}

		// try to connect to owncloud.org to see if http connections to the internet are possible.
		$connected = @fsockopen("www.owncloud.org", 80);
		if ($connected) {
			fclose($connected);
			return true;
		} else {
			// second try in case one server is down
			$connected = @fsockopen("apps.owncloud.com", 80);
			if ($connected) {
				fclose($connected);
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Check if the connection to the internet is disabled on purpose
	 * @return string
	 */
	public static function isInternetConnectionEnabled(){
		return \OC_Config::getValue("has_internet_connection", true);
	}

	/**
	 * clear all levels of output buffering
	 * @return void
	 */
	public static function obEnd(){
		while (ob_get_level()) {
			ob_end_clean();
		}
	}


	/**
	 * Generates a cryptographic secure pseudo-random string
	 * @param int $length of the random string
	 * @return string
	 * Please also update secureRNGAvailable if you change something here
	 */
	public static function generateRandomBytes($length = 30) {
		// Try to use openssl_random_pseudo_bytes
		if (function_exists('openssl_random_pseudo_bytes')) {
			$pseudoByte = bin2hex(openssl_random_pseudo_bytes($length, $strong));
			if($strong == true) {
				return substr($pseudoByte, 0, $length); // Truncate it to match the length
			}
		}

		// Try to use /dev/urandom
		if (!self::runningOnWindows()) {
			$fp = @file_get_contents('/dev/urandom', false, null, 0, $length);
			if ($fp !== false) {
				$string = substr(bin2hex($fp), 0, $length);
				return $string;
			}
		}

		// Fallback to mt_rand()
		$characters = '0123456789';
		$characters .= 'abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen($characters)-1;
		$pseudoByte = "";

		// Select some random characters
		for ($i = 0; $i < $length; $i++) {
			$pseudoByte .= $characters[mt_rand(0, $charactersLength)];
		}
		return $pseudoByte;
	}

	/**
	 * Checks if a secure random number generator is available
	 * @return bool
	 */
	public static function secureRNGAvailable() {
		// Check openssl_random_pseudo_bytes
		if(function_exists('openssl_random_pseudo_bytes')) {
			openssl_random_pseudo_bytes(1, $strong);
			if($strong == true) {
				return true;
			}
		}

		// Check /dev/urandom
		if (!self::runningOnWindows()) {
			$fp = @file_get_contents('/dev/urandom', false, null, 0, 1);
			if ($fp !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @Brief Get file content via curl.
	 * @param string $url Url to get content
	 * @throws Exception If the URL does not start with http:// or https://
	 * @return string of the response or false on error
	 * This function get the content of a page via curl, if curl is enabled.
	 * If not, file_get_contents is used.
	 */
	public static function getUrlContent($url) {
		if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
			throw new Exception('$url must start with https:// or http://', 1);
		}
		
		if (function_exists('curl_init')) {
			$curl = curl_init();
			$max_redirects = 10;

			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl, CURLOPT_URL, $url);


			curl_setopt($curl, CURLOPT_USERAGENT, "ownCloud Server Crawler");
			if(OC_Config::getValue('proxy', '') != '') {
				curl_setopt($curl, CURLOPT_PROXY, OC_Config::getValue('proxy'));
			}
			if(OC_Config::getValue('proxyuserpwd', '') != '') {
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, OC_Config::getValue('proxyuserpwd'));
			}

			if (ini_get('open_basedir') === '' && ini_get('safe_mode') === 'Off') {
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_MAXREDIRS, $max_redirects);
				$data = curl_exec($curl);
			} else {
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
				$mr = $max_redirects;
				if ($mr > 0) {
					$newURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
					$rcurl = curl_copy_handle($curl);
					curl_setopt($rcurl, CURLOPT_HEADER, true);
					curl_setopt($rcurl, CURLOPT_NOBODY, true);
					curl_setopt($rcurl, CURLOPT_FORBID_REUSE, false);
					curl_setopt($rcurl, CURLOPT_RETURNTRANSFER, true);
					do {
						curl_setopt($rcurl, CURLOPT_URL, $newURL);
						$header = curl_exec($rcurl);
						if (curl_errno($rcurl)) {
							$code = 0;
						} else {
							$code = curl_getinfo($rcurl, CURLINFO_HTTP_CODE);
							if ($code == 301 || $code == 302) {
								preg_match('/Location:(.*?)\n/', $header, $matches);
								$newURL = trim(array_pop($matches));
							} else {
								$code = 0;
							}
						}
					} while ($code && --$mr);
					curl_close($rcurl);
					if ($mr > 0) {
						curl_setopt($curl, CURLOPT_URL, $newURL);
					}
				}

				if($mr == 0 && $max_redirects > 0) {
					$data = false;
				} else {
					$data = curl_exec($curl);
				}
			}
			curl_close($curl);
		} else {
			$contextArray = null;

			if(OC_Config::getValue('proxy', '') != '') {
				$contextArray = array(
					'http' => array(
						'timeout' => 10,
						'proxy' => OC_Config::getValue('proxy')
					)
				);
			} else {
				$contextArray = array(
					'http' => array(
						'timeout' => 10
					)
				);
			}

			$ctx = stream_context_create(
				$contextArray
			);
			$data = @file_get_contents($url, 0, $ctx);

		}
		return $data;
	}

	/**
	 * Checks whether the server is running on Windows
	 * @return bool true if running on Windows, false otherwise
	 */
	public static function runningOnWindows() {
		return (substr(PHP_OS, 0, 3) === "WIN");
	}

	/**
	 * Checks whether the server is running on Mac OS X
	 * @return bool true if running on Mac OS X, false otherwise
	 */
	public static function runningOnMac() {
		return (strtoupper(substr(PHP_OS, 0, 6)) === 'DARWIN');
	}

	/**
	 * Handles the case that there may not be a theme, then check if a "default"
	 * theme exists and take that one
	 * @return string the theme
	 */
	public static function getTheme() {
		$theme = OC_Config::getValue("theme", '');

		if($theme === '') {
			if(is_dir(OC::$SERVERROOT . '/themes/default')) {
				$theme = 'default';
			}
		}

		return $theme;
	}

	/**
	 * Clear the opcode cache if one exists
	 * This is necessary for writing to the config file
	 * in case the opcode cache does not re-validate files
	 * @return void
	 */
	public static function clearOpcodeCache() {
		// APC
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
		}
		// Zend Opcache
		if (function_exists('accelerator_reset')) {
			accelerator_reset();
		}
		// XCache
		if (function_exists('xcache_clear_cache')) {
			if (ini_get('xcache.admin.enable_auth')) {
				OC_Log::write('core', 'XCache opcode cache will not be cleared because "xcache.admin.enable_auth" is enabled.', \OC_Log::WARN);
			} else {
				xcache_clear_cache(XC_TYPE_PHP, 0);
			}
		}
		// Opcache (PHP >= 5.5)
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
	}

	/**
	 * Normalize a unicode string
	 * @param string $value a not normalized string
	 * @return bool|string
	 */
	public static function normalizeUnicode($value) {
		if(class_exists('Patchwork\PHP\Shim\Normalizer')) {
			$normalizedValue = \Patchwork\PHP\Shim\Normalizer::normalize($value);
			if($normalizedValue === false) {
				\OC_Log::write( 'core', 'normalizing failed for "' . $value . '"', \OC_Log::WARN);
			} else {
				$value = $normalizedValue;
			}
		}

		return $value;
	}

	/**
	 * @param boolean|string $file
	 * @return string
	 */
	public static function basename($file) {
		$file = rtrim($file, '/');
		$t = explode('/', $file);
		return array_pop($t);
	}

	/**
	 * A human readable string is generated based on version, channel and build number
	 * @return string
	 */
	public static function getHumanVersion() {
		$version = OC_Util::getVersionString().' ('.OC_Util::getChannel().')';
		$build = OC_Util::getBuild();
		if(!empty($build) and OC_Util::getChannel() === 'daily') {
			$version .= ' Build:' . $build;
		}
		return $version;
	}

	/**
	 * Returns whether the given file name is valid
	 * @param string $file file name to check
	 * @return bool true if the file name is valid, false otherwise
	 */
	public static function isValidFileName($file) {
		$trimmed = trim($file);
		if ($trimmed === '') {
			return false;
		}
		if ($trimmed === '.' || $trimmed === '..') {
			return false;
		}
		foreach (str_split($trimmed) as $char) {
			if (strpos(\OCP\FILENAME_INVALID_CHARS, $char) !== false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check whether the instance needs to preform an upgrade
	 *
	 * @return bool
	 */
	public static function needUpgrade() {
		if (OC_Config::getValue('installed', false)) {
			$installedVersion = OC_Config::getValue('version', '0.0.0');
			$currentVersion = implode('.', OC_Util::getVersion());
			if (version_compare($currentVersion, $installedVersion, '>')) {
				return true;
			}

			// also check for upgrades for apps
			$apps = \OC_App::getEnabledApps();
			foreach ($apps as $app) {
				if (\OC_App::shouldUpgrade($app)) {
					return true;
				}
			}
			return false;
		} else {
			return false;
		}
	}
}
