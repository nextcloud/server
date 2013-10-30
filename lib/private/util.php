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
	public static $coreStyles=array();
	public static $coreScripts=array();

	/**
	 * @brief Can be set up
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
		if(!isset($RUNTIME_NOAPPS) || !$RUNTIME_NOAPPS) {
			OC_App::loadApps(array('filesystem'));
		}

		// the filesystem will finish when $user is not empty,
		// mark fs setup here to avoid doing the setup from loading
		// OC_Filesystem
		if ($user != '') {
			self::$fsSetup=true;
		}

		$configDataDirectory = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		//first set up the local "root" storage
		\OC\Files\Filesystem::initMounts();
		if(!self::$rootMounted) {
			\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', array('datadir'=>$configDataDirectory), '/');
			self::$rootMounted = true;
		}

		//if we aren't logged in, there is no use to set up the filesystem
		if( $user != "" ) {
			$quota = self::getUserQuota($user);
			if ($quota !== \OC\Files\SPACE_UNLIMITED) {
				\OC\Files\Filesystem::addStorageWrapper(function($mountPoint, $storage) use ($quota, $user) {
					if ($mountPoint === '/' . $user . '/'){
						return new \OC\Files\Storage\Wrapper\Quota(array('storage' => $storage, 'quota' => $quota));
					} else {
						return $storage;
					}
				});
			}
			$userDir = '/'.$user.'/files';
			$userRoot = OC_User::getHome($user);
			$userDirectory = $userRoot . '/files';
			if( !is_dir( $userDirectory )) {
				mkdir( $userDirectory, 0755, true );
				OC_Util::copySkeleton($userDirectory);
			}
			//jail the user into his "home" directory
			\OC\Files\Filesystem::init($user, $userDir);

			$fileOperationProxy = new OC_FileProxy_FileOperations();
			OC_FileProxy::register($fileOperationProxy);

			OC_Hook::emit('OC_Filesystem', 'setup', array('user' => $user, 'user_dir' => $userDir));
		}
		return true;
	}

	public static function getUserQuota($user){
		$userQuota = OC_Preferences::getValue($user, 'files', 'quota', 'default');
		if($userQuota === 'default') {
			$userQuota = OC_AppConfig::getValue('files', 'default_quota', 'none');
		}
		if($userQuota === 'none') {
			return \OC\Files\SPACE_UNLIMITED;
		}else{
			return OC_Helper::computerFileSize($userQuota);
		}
	}

	/**
	 * @brief copies the user skeleton files into the fresh user home files
	 * @param string $userDirectory
	 */
	public static function copySkeleton($userDirectory) {
		OC_Util::copyr(\OC::$SERVERROOT.'/core/skeleton' , $userDirectory);
	}

	/**
	 * @brief copies a directory recursively
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
	 * @brief get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion() {
		OC_Util::loadVersion();
		return \OC::$server->getSession()->get('OC_Version');
	}

	/**
	 * @brief get the current installed version string of ownCloud
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
	 * @brief add a javascript file
	 *
	 * @param string $application
	 * @param filename $file
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
	 * @brief add a css file
	 *
	 * @param string $application
	 * @param filename $file
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
	 * @brief Add a custom element to the header
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
	 * @brief formats a timestamp in the "right" way
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
	 * @brief check if the current server configuration is suitable for ownCloud
	 * @return array arrays with error messages and hints
	 */
	public static function checkServer() {
		// Assume that if checkServer() succeeded before in this session, then all is fine.
		if(\OC::$session->exists('checkServer_suceeded') && \OC::$session->get('checkServer_suceeded')) {
			return array();
		}

		$errors = array();

		$defaults = new \OC_Defaults();

		$webServerRestart = false;
		//check for database drivers
		if(!(is_callable('sqlite_open') or class_exists('SQLite3'))
			and !is_callable('mysql_connect')
			and !is_callable('pg_connect')
			and !is_callable('oci_connect')) {
			$errors[] = array(
				'error'=>'No database drivers (sqlite, mysql, or postgresql) installed.',
				'hint'=>'' //TODO: sane hint
			);
			$webServerRestart = true;
		}

		//common hint for all file permissions error messages
		$permissionsHint = 'Permissions can usually be fixed by '
			.'<a href="' . OC_Helper::linkToDocs('admin-dir_permissions')
			.'" target="_blank">giving the webserver write access to the root directory</a>.';

		// Check if config folder is writable.
		if(!is_writable(OC::$SERVERROOT."/config/") or !is_readable(OC::$SERVERROOT."/config/")) {
			$errors[] = array(
				'error' => "Can't write into config directory",
				'hint' => 'This can usually be fixed by '
					.'<a href="' . OC_Helper::linkToDocs('admin-dir_permissions')
					.'" target="_blank">giving the webserver write access to the config directory</a>.'
				);
		}

		// Check if there is a writable install folder.
		if(OC_Config::getValue('appstoreenabled', true)) {
			if( OC_App::getInstallPath() === null
				|| !is_writable(OC_App::getInstallPath())
				|| !is_readable(OC_App::getInstallPath()) ) {
				$errors[] = array(
					'error' => "Can't write into apps directory",
					'hint' => 'This can usually be fixed by '
						.'<a href="' . OC_Helper::linkToDocs('admin-dir_permissions')
						.'" target="_blank">giving the webserver write access to the apps directory</a> '
						.'or disabling the appstore in the config file.'
					);
			}
		}
		$CONFIG_DATADIRECTORY = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
		// Create root dir.
		if(!is_dir($CONFIG_DATADIRECTORY)) {
			$success=@mkdir($CONFIG_DATADIRECTORY);
			if ($success) {
				$errors = array_merge($errors, self::checkDataDirectoryPermissions($CONFIG_DATADIRECTORY));
			} else {
				$errors[] = array(
					'error' => "Can't create data directory (".$CONFIG_DATADIRECTORY.")",
					'hint' => 'This can usually be fixed by '
					.'<a href="' . OC_Helper::linkToDocs('admin-dir_permissions')
					.'" target="_blank">giving the webserver write access to the root directory</a>.'
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

		$moduleHint = "Please ask your server administrator to install the module.";
		// check if all required php modules are present
		if(!class_exists('ZipArchive')) {
			$errors[] = array(
				'error'=>'PHP module zip not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!class_exists('DOMDocument')) {
			$errors[] = array(
				'error' => 'PHP module dom not installed.',
				'hint' => $moduleHint
			);
			$webServerRestart =true;
		}
		if(!function_exists('xml_parser_create')) {
			$errors[] = array(
				'error' => 'PHP module libxml not installed.',
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
				'error'=>'PHP module ctype is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('json_encode')) {
			$errors[] = array(
				'error'=>'PHP module JSON is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!extension_loaded('gd') || !function_exists('gd_info')) {
			$errors[] = array(
				'error'=>'PHP module GD is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('gzencode')) {
			$errors[] = array(
				'error'=>'PHP module zlib is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('iconv')) {
			$errors[] = array(
				'error'=>'PHP module iconv is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(!function_exists('simplexml_load_string')) {
			$errors[] = array(
				'error'=>'PHP module SimpleXML is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if(floatval(phpversion()) < 5.3) {
			$errors[] = array(
				'error'=>'PHP 5.3 is required.',
				'hint'=>'Please ask your server administrator to update PHP to version 5.3 or higher.'
					.' PHP 5.2 is no longer supported by ownCloud and the PHP community.'
			);
			$webServerRestart = true;
		}
		if(!defined('PDO::ATTR_DRIVER_NAME')) {
			$errors[] = array(
				'error'=>'PHP PDO module is not installed.',
				'hint'=>$moduleHint
			);
			$webServerRestart = true;
		}
		if (((strtolower(@ini_get('safe_mode')) == 'on')
			|| (strtolower(@ini_get('safe_mode')) == 'yes')
			|| (strtolower(@ini_get('safe_mode')) == 'true')
			|| (ini_get("safe_mode") == 1 ))) {
			$errors[] = array(
				'error'=>'PHP Safe Mode is enabled. ownCloud requires that it is disabled to work properly.',
				'hint'=>'PHP Safe Mode is a deprecated and mostly useless setting that should be disabled. '
					.'Please ask your server administrator to disable it in php.ini or in your webserver config.'
			);
			$webServerRestart = true;
		}
		if (get_magic_quotes_gpc() == 1 ) {
			$errors[] = array(
				'error'=>'Magic Quotes is enabled. ownCloud requires that it is disabled to work properly.',
				'hint'=>'Magic Quotes is a deprecated and mostly useless setting that should be disabled. '
					.'Please ask your server administrator to disable it in php.ini or in your webserver config.'
			);
			$webServerRestart = true;
		}

		if($webServerRestart) {
			$errors[] = array(
				'error'=>'PHP modules have been installed, but they are still listed as missing?',
				'hint'=>'Please ask your server administrator to restart the web server.'
			);
		}

		// Cache the result of this function
		\OC::$session->set('checkServer_suceeded', count($errors) == 0);

		return $errors;
	}

	/**
	 * @brief check if there are still some encrypted files stored
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
	 * @brief Check for correct file permissions of data directory
	 * @paran string $dataDirectory
	 * @return array arrays with error messages and hints
	 */
	public static function checkDataDirectoryPermissions($dataDirectory) {
		$errors = array();
		if (self::runningOnWindows()) {
			//TODO: permissions checks for windows hosts
		} else {
			$permissionsModHint = 'Please change the permissions to 0770 so that the directory'
				.' cannot be listed by other users.';
			$perms = substr(decoct(@fileperms($dataDirectory)), -3);
			if (substr($perms, -1) != '0') {
				OC_Helper::chmodr($dataDirectory, 0770);
				clearstatcache();
				$perms = substr(decoct(@fileperms($dataDirectory)), -3);
				if (substr($perms, 2, 1) != '0') {
					$errors[] = array(
						'error' => 'Data directory ('.$dataDirectory.') is readable for other users',
						'hint' => $permissionsModHint
					);
				}
			}
		}
		return $errors;
	}

	/**
	 * @return void
	 */
	public static function displayLoginPage($errors = array()) {
		$parameters = array();
		foreach( $errors as $key => $value ) {
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
	 * @brief Check if the app is enabled, redirects to home if not
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
				array('redirectUrl' => OC_Request::requestUri())
			));
			exit();
		}
	}

	/**
	 * @brief Check if the user is a admin, redirects to home if not
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
	 * @brief Check if the user is a subadmin, redirects to home if not
	 * @return array $groups where the current user is subadmin
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
	 * @brief Redirect to the user default page
	 * @return void
	 */
	public static function redirectToDefaultPage() {
		if(isset($_REQUEST['redirect_url'])) {
			$location = OC_Helper::makeURLAbsolute(urldecode($_REQUEST['redirect_url']));
		}
		else if (isset(OC::$REQUESTEDAPP) && !empty(OC::$REQUESTEDAPP)) {
			$location = OC_Helper::linkToAbsolute( OC::$REQUESTEDAPP, 'index.php' );
		} else {
			$defaultPage = OC_Appconfig::getValue('core', 'defaultpage');
			if ($defaultPage) {
				$location = OC_Helper::makeURLAbsolute(OC::$WEBROOT.'/'.$defaultPage);
			} else {
				$location = OC_Helper::linkToAbsolute( 'files', 'index.php' );
			}
		}
		OC_Log::write('core', 'redirectToDefaultPage: '.$location, OC_Log::DEBUG);
		header( 'Location: '.$location );
		exit();
	}

	/**
	 * @brief get an id unique for this instance
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
	 * @brief Static lifespan (in seconds) when a request token expires.
	 * @see OC_Util::callRegister()
	 * @see OC_Util::isCallRegistered()
	 * @description
	 * Also required for the client side to compute the point in time when to
	 * request a fresh token. The client will do so when nearly 97% of the
	 * time span coded here has expired.
	 */
	public static $callLifespan = 3600; // 3600 secs = 1 hour

	/**
	 * @brief Register an get/post call. Important to prevent CSRF attacks.
	 * @todo Write howto: CSRF protection guide
	 * @return $token Generated token.
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
	 * @brief Check an ajax get/post call if the request token is valid.
	 * @return boolean False if request token is not set or is invalid.
	 * @see OC_Util::$callLifespan
	 * @see OC_Util::callRegister()
	 */
	public static function isCallRegistered() {
		return \OC::$server->getRequest()->passesCSRFCheck();
	}

	/**
	 * @brief Check an ajax get/post call if the request token is valid. exit if not.
	 * @todo Write howto
	 * @return void
	 */
	public static function callCheck() {
		if(!OC_Util::isCallRegistered()) {
			exit();
		}
	}

	/**
	 * @brief Public function to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any
	 * string or array of strings before displaying it on a web page.
	 *
	 * @param string|array of strings
	 * @return array with sanitized strings or a single sanitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML( &$value ) {
		if (is_array($value)) {
			array_walk_recursive($value, 'OC_Util::sanitizeHTML');
		} else {
			//Specify encoding for PHP<5.4
			$value = htmlentities((string)$value, ENT_QUOTES, 'UTF-8');
		}
		return $value;
	}

	/**
	 * @brief Public function to encode url parameters
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
	 * @brief Check if the htaccess file is working
	 * @return bool
	 * @description Check if the htaccess file is working by creating a test
	 * file in the data directory and trying to access via http
	 */
	public static function isHtAccessWorking() {
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
		@fwrite($fp, $testContent);
		@fclose($fp);

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
	 * @brief test if webDAV is working properly
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

		$return = true;
		try {
			// test PROPFIND
			$client->propfind('', array('{DAV:}resourcetype'));
		} catch (\Sabre_DAV_Exception_NotAuthenticated $e) {
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

		$result = setlocale(LC_ALL, 'en_US.UTF-8', 'en_US.UTF8');
		if($result == false) {
			return false;
		}
		return true;
	}

	/**
	 * @brief Check if the PHP module fileinfo is loaded.
	 * @return bool
	 */
	public static function fileInfoLoaded() {
		return function_exists('finfo_open');
	}

	/**
	 * @brief Check if the ownCloud server can connect to the internet
	 * @return bool
	 */
	public static function isInternetConnectionWorking() {
		// in case there is no internet connection on purpose return false
		if (self::isInternetConnectionEnabled() === false) {
			return false;
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
	 * @brief Check if the connection to the internet is disabled on purpose
	 * @return bool
	 */
	public static function isInternetConnectionEnabled(){
		return \OC_Config::getValue("has_internet_connection", true);
	}

	/**
	 * @brief clear all levels of output buffering
	 * @return void
	 */
	public static function obEnd(){
		while (ob_get_level()) {
			ob_end_clean();
		}
	}


	/**
	 * @brief Generates a cryptographic secure pseudo-random string
	 * @param Int $length of the random string
	 * @return String
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
	 * @brief Checks if a secure random number generator is available
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
	 * @return string of the response or false on error
	 * This function get the content of a page via curl, if curl is enabled.
	 * If not, file_get_contents is used.
	 */
	public static function getUrlContent($url) {
		if (function_exists('curl_init')) {
			$curl = curl_init();

			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

			curl_setopt($curl, CURLOPT_USERAGENT, "ownCloud Server Crawler");
			if(OC_Config::getValue('proxy', '') != '') {
				curl_setopt($curl, CURLOPT_PROXY, OC_Config::getValue('proxy'));
			}
			if(OC_Config::getValue('proxyuserpwd', '') != '') {
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, OC_Config::getValue('proxyuserpwd'));
			}
			$data = curl_exec($curl);
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
	 * @return bool - well are we running on windows or not
	 */
	public static function runningOnWindows() {
		return (substr(PHP_OS, 0, 3) === "WIN");
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
	 * @brief Clear the opcode cache if one exists
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
			xcache_clear_cache(XC_TYPE_VAR, 0);
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
	 * @return string
	 */
	public static function basename($file) {
		$file = rtrim($file, '/');
		$t = explode('/', $file);
		return array_pop($t);
	}
}
