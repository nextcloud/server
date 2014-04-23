<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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

require_once 'public/constants.php';

/**
 * Class that is a namespace for all global OC variables
 * No, we can not put this class in its own file because it is used by
 * OC_autoload!
 */
class OC {
	/**
	 * Associative array for autoloading. classname => filename
	 */
	public static $CLASSPATH = array();
	/**
	 * The installation path for owncloud on the server (e.g. /srv/http/owncloud)
	 */
	public static $SERVERROOT = '';
	/**
	 * the current request path relative to the owncloud root (e.g. files/index.php)
	 */
	private static $SUBURI = '';
	/**
	 * the owncloud root path for http requests (e.g. owncloud/)
	 */
	public static $WEBROOT = '';
	/**
	 * The installation path of the 3rdparty folder on the server (e.g. /srv/http/owncloud/3rdparty)
	 */
	public static $THIRDPARTYROOT = '';
	/**
	 * the root path of the 3rdparty folder for http requests (e.g. owncloud/3rdparty)
	 */
	public static $THIRDPARTYWEBROOT = '';
	/**
	 * The installation path array of the apps folder on the server (e.g. /srv/http/owncloud) 'path' and
	 * web path in 'url'
	 */
	public static $APPSROOTS = array();
	/*
	 * requested app
	 */
	public static $REQUESTEDAPP = '';
	/*
	 * requested file of app
	 */
	public static $REQUESTEDFILE = '';
	/**
	 * check if owncloud runs in cli mode
	 */
	public static $CLI = false;
	/*
	 * OC router
	 */
	protected static $router = null;

	/**
	 * SPL autoload
	 */
	public static function autoload($className) {
		$className = trim($className, '\\');

		if (array_key_exists($className, OC::$CLASSPATH)) {
			$path = OC::$CLASSPATH[$className];
			/** @TODO: Remove this when necessary
			Remove "apps/" from inclusion path for smooth migration to mutli app dir
			 */
			if (strpos($path, 'apps/') === 0) {
				OC_Log::write('core', 'include path for class "' . $className . '" starts with "apps/"', OC_Log::DEBUG);
				$path = str_replace('apps/', '', $path);
			}
		} elseif (strpos($className, 'OC_') === 0) {
			$path = strtolower(str_replace('_', '/', substr($className, 3)) . '.php');
		} elseif (strpos($className, 'OC\\') === 0) {
			$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		} elseif (strpos($className, 'OCP\\') === 0) {
			$path = 'public/' . strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		} elseif (strpos($className, 'OCA\\') === 0) {
			list(, $app, $rest) = explode('\\', $className, 3);
			$app = strtolower($app);
			foreach (self::$APPSROOTS as $appDir) {
				$path = $appDir['path'] . '/' . $app . '/' . strtolower(str_replace('\\', '/', $rest) . '.php');
				$fullPath = stream_resolve_include_path($path);
				if (file_exists($fullPath)) {
					require_once $fullPath;
					return false;
				}
				// If not found in the root of the app directory, insert '/lib' after app id and try again.
				$path = $appDir['path'] . '/' . $app . '/lib/' . strtolower(str_replace('\\', '/', $rest) . '.php');
				$fullPath = stream_resolve_include_path($path);
				if (file_exists($fullPath)) {
					require_once $fullPath;
					return false;
				}
			}
		} elseif (strpos($className, 'Sabre_') === 0) {
			$path = str_replace('_', '/', $className) . '.php';
		} elseif (strpos($className, 'Symfony\\Component\\Routing\\') === 0) {
			$path = 'symfony/routing/' . str_replace('\\', '/', $className) . '.php';
		} elseif (strpos($className, 'Sabre\\VObject') === 0) {
			$path = str_replace('\\', '/', $className) . '.php';
		} elseif (strpos($className, 'Test_') === 0) {
			$path = 'tests/lib/' . strtolower(str_replace('_', '/', substr($className, 5)) . '.php');
		} elseif (strpos($className, 'Test\\') === 0) {
			$path = 'tests/lib/' . strtolower(str_replace('\\', '/', substr($className, 5)) . '.php');
		} else {
			return false;
		}

		if ($fullPath = stream_resolve_include_path($path)) {
			require_once $fullPath;
		}
		return false;
	}

	public static function initPaths() {
		// calculate the root directories
		OC::$SERVERROOT = str_replace("\\", '/', substr(__DIR__, 0, -4));

		// ensure we can find OC_Config
		set_include_path(
			OC::$SERVERROOT . '/lib' . PATH_SEPARATOR .
				get_include_path()
		);

		OC::$SUBURI = str_replace("\\", "/", substr(realpath($_SERVER["SCRIPT_FILENAME"]), strlen(OC::$SERVERROOT)));
		$scriptName = OC_Request::scriptName();
		if (substr($scriptName, -1) == '/') {
			$scriptName .= 'index.php';
			//make sure suburi follows the same rules as scriptName
			if (substr(OC::$SUBURI, -9) != 'index.php') {
				if (substr(OC::$SUBURI, -1) != '/') {
					OC::$SUBURI = OC::$SUBURI . '/';
				}
				OC::$SUBURI = OC::$SUBURI . 'index.php';
			}
		}

		OC::$WEBROOT = substr($scriptName, 0, strlen($scriptName) - strlen(OC::$SUBURI));

		if (OC::$WEBROOT != '' and OC::$WEBROOT[0] !== '/') {
			OC::$WEBROOT = '/' . OC::$WEBROOT;
		}

		// search the 3rdparty folder
		if (OC_Config::getValue('3rdpartyroot', '') <> '' and OC_Config::getValue('3rdpartyurl', '') <> '') {
			OC::$THIRDPARTYROOT = OC_Config::getValue('3rdpartyroot', '');
			OC::$THIRDPARTYWEBROOT = OC_Config::getValue('3rdpartyurl', '');
		} elseif (file_exists(OC::$SERVERROOT . '/3rdparty')) {
			OC::$THIRDPARTYROOT = OC::$SERVERROOT;
			OC::$THIRDPARTYWEBROOT = OC::$WEBROOT;
		} elseif (file_exists(OC::$SERVERROOT . '/../3rdparty')) {
			OC::$THIRDPARTYWEBROOT = rtrim(dirname(OC::$WEBROOT), '/');
			OC::$THIRDPARTYROOT = rtrim(dirname(OC::$SERVERROOT), '/');
		} else {
			echo('3rdparty directory not found! Please put the ownCloud 3rdparty'
				.' folder in the ownCloud folder or the folder above.'
				.' You can also configure the location in the config.php file.');
			exit;
		}
		// search the apps folder
		$config_paths = OC_Config::getValue('apps_paths', array());
		if (!empty($config_paths)) {
			foreach ($config_paths as $paths) {
				if (isset($paths['url']) && isset($paths['path'])) {
					$paths['url'] = rtrim($paths['url'], '/');
					$paths['path'] = rtrim($paths['path'], '/');
					OC::$APPSROOTS[] = $paths;
				}
			}
		} elseif (file_exists(OC::$SERVERROOT . '/apps')) {
			OC::$APPSROOTS[] = array('path' => OC::$SERVERROOT . '/apps', 'url' => '/apps', 'writable' => true);
		} elseif (file_exists(OC::$SERVERROOT . '/../apps')) {
			OC::$APPSROOTS[] = array(
				'path' => rtrim(dirname(OC::$SERVERROOT), '/') . '/apps',
				'url' => '/apps',
				'writable' => true
			);
		}

		if (empty(OC::$APPSROOTS)) {
			echo('apps directory not found! Please put the ownCloud apps folder in the ownCloud folder'
				.' or the folder above. You can also configure the location in the config.php file.');
			exit;
		}
		$paths = array();
		foreach (OC::$APPSROOTS as $path) {
			$paths[] = $path['path'];
		}

		// set the right include path
		set_include_path(
			OC::$SERVERROOT . '/lib' . PATH_SEPARATOR .
				OC::$SERVERROOT . '/config' . PATH_SEPARATOR .
				OC::$THIRDPARTYROOT . '/3rdparty' . PATH_SEPARATOR .
				implode($paths, PATH_SEPARATOR) . PATH_SEPARATOR .
				get_include_path() . PATH_SEPARATOR .
				OC::$SERVERROOT
		);
	}

	public static function checkConfig() {
		if (file_exists(OC::$SERVERROOT . "/config/config.php")
			and !is_writable(OC::$SERVERROOT . "/config/config.php")) {
			$defaults = new OC_Defaults();
			$tmpl = new OC_Template('', 'error', 'guest');
			$tmpl->assign('errors', array(1 => array(
				'error' => "Can't write into config directory 'config'",
				'hint' => 'This can usually be fixed by '
					.'<a href="' . $defaults->getDocBaseUrl() . '/server/5.0/admin_manual/installation/installation_source.html#set-the-directory-permissions" target="_blank">giving the webserver write access to the config directory</a>.'
			)));
			$tmpl->printPage();
			exit();
		}
	}

	public static function checkInstalled() {
		// Redirect to installer if not installed
		if (!OC_Config::getValue('installed', false) && OC::$SUBURI != '/index.php') {
			if (!OC::$CLI) {
				$url = 'http://' . $_SERVER['SERVER_NAME'] . OC::$WEBROOT . '/index.php';
				header("Location: $url");
			}
			exit();
		}
	}

	/*
	* This function adds some security related headers to all requests served via base.php
	* The implementation of this function has to happen here to ensure that all third-party
	* components (e.g. SabreDAV) also benefit from this headers.
	*/
	public static function addSecurityHeaders() {
		header('X-XSS-Protection: 1; mode=block'); // Enforce browser based XSS filters
		header('X-Content-Type-Options: nosniff'); // Disable sniffing the content type for IE

		// iFrame Restriction Policy
		$xFramePolicy = OC_Config::getValue('xframe_restriction', true);
		if ($xFramePolicy) {
			header('X-Frame-Options: Sameorigin'); // Disallow iFraming from other domains
		}

		// Content Security Policy
		// If you change the standard policy, please also change it in config.sample.php
		$policy = OC_Config::getValue('custom_csp_policy',
			'default-src \'self\'; '
			. 'script-src \'self\' \'unsafe-eval\'; '
			. 'style-src \'self\' \'unsafe-inline\'; '
			. 'frame-src *; '
			. 'img-src *; '
			. 'font-src \'self\' data:; '
			. 'media-src *');
		header('Content-Security-Policy:' . $policy);
	}

	public static function checkSSL() {
		// redirect to https site if configured
		if (OC_Config::getValue("forcessl", false)) {
			header('Strict-Transport-Security: max-age=31536000');
			ini_set("session.cookie_secure", "on");
			if (OC_Request::serverProtocol() <> 'https' and !OC::$CLI) {
				$url = "https://" . OC_Request::serverHost() . OC_Request::requestUri();
				header("Location: $url");
				exit();
			}
		} else {
			// Invalidate HSTS headers
			if (OC_Request::serverProtocol() === 'https') {
				header('Strict-Transport-Security: max-age=0');
			}
		}
	}

	public static function checkMaintenanceMode() {
		// Allow ajax update script to execute without being stopped
		if (OC_Config::getValue('maintenance', false) && OC::$SUBURI != '/core/ajax/update.php') {
			// send http status 503
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 120');

			// render error page
			$tmpl = new OC_Template('', 'error', 'guest');
			$tmpl->assign('errors', array(1 => array('error' => 'ownCloud is in maintenance mode')));
			$tmpl->printPage();
			exit();
		}
	}

	public static function checkUpgrade($showTemplate = true) {
		if (OC_Config::getValue('installed', false)) {
			$installedVersion = OC_Config::getValue('version', '0.0.0');
			$currentVersion = implode('.', OC_Util::getVersion());
			if (version_compare($currentVersion, $installedVersion, '>')) {
				if ($showTemplate && !OC_Config::getValue('maintenance', false)) {
					OC_Config::setValue('theme', '');
					$minimizerCSS = new OC_Minimizer_CSS();
					$minimizerCSS->clearCache();
					$minimizerJS = new OC_Minimizer_JS();
					$minimizerJS->clearCache();
					OC_Util::addscript('update');
					$tmpl = new OC_Template('', 'update', 'guest');
					$tmpl->assign('version', OC_Util::getVersionString());
					$tmpl->printPage();
					exit();
				} else {
					return true;
				}
			}
			return false;
		}
	}

	public static function initTemplateEngine() {
		// Add the stuff we need always
		OC_Util::addScript("jquery-1.7.2.min");
		OC_Util::addScript("jquery-ui-1.10.0.custom");
		OC_Util::addScript("jquery-showpassword");
		OC_Util::addScript("jquery.infieldlabel");
		OC_Util::addScript("jquery-tipsy");
		OC_Util::addScript("compatibility");
		OC_Util::addScript("oc-dialogs");
		OC_Util::addScript("js");
		OC_Util::addScript("eventsource");
		OC_Util::addScript("config");
		//OC_Util::addScript( "multiselect" );
		OC_Util::addScript('search', 'result');
		OC_Util::addScript('router');

		OC_Util::addStyle("styles");
		OC_Util::addStyle("multiselect");
		OC_Util::addStyle("jquery-ui-1.10.0.custom");
		OC_Util::addStyle("jquery-tipsy");
		OC_Util::addScript("oc-requesttoken");
	}

	public static function initSession() {
		// prevents javascript from accessing php session cookies
		ini_set('session.cookie_httponly', '1;');

		// set the session name to the instance id - which is unique
		session_name(OC_Util::getInstanceId());

		// if session cant be started break with http 500 error
		if (session_start() === false){
			OC_Log::write('core', 'Session could not be initialized',
				OC_Log::ERROR);

			header('HTTP/1.1 500 Internal Server Error');
			OC_Util::addStyle("styles");
			$error = 'Session could not be initialized. Please contact your ';
			$error .= 'system administrator';

			$tmpl = new OC_Template('', 'error', 'guest');
			$tmpl->assign('errors', array(1 => array('error' => $error)));
			$tmpl->printPage();

			exit();
		}

		$sessionLifeTime = self::getSessionLifeTime();
		// regenerate session id periodically to avoid session fixation
		if (!isset($_SESSION['SID_CREATED'])) {
			$_SESSION['SID_CREATED'] = time();
		} else if (time() - $_SESSION['SID_CREATED'] > $sessionLifeTime / 2) {
			session_regenerate_id(true);
			$_SESSION['SID_CREATED'] = time();
		}

		// session timeout
		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $sessionLifeTime)) {
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time() - 42000, '/');
			}
			session_unset();
			session_destroy();
			session_start();
		}
		$_SESSION['LAST_ACTIVITY'] = time();
	}

	/**
	 * @return int
	 */
	private static function getSessionLifeTime() {
		return OC_Config::getValue('session_lifetime', 60 * 60 * 24);
	}

	public static function getRouter() {
		if (!isset(OC::$router)) {
			OC::$router = new OC_Router();
			OC::$router->loadRoutes();
		}

		return OC::$router;
	}


	public static function loadAppClassPaths() {
		foreach (OC_APP::getEnabledApps() as $app) {
			$file = OC_App::getAppPath($app) . '/appinfo/classpath.php';
			if (file_exists($file)) {
				require_once $file;
			}
		}
	}


	public static function init() {
		// register autoloader
		spl_autoload_register(array('OC', 'autoload'));
		OC_Util::issetlocaleworking();

		// set some stuff
		//ob_start();
		error_reporting(E_ALL | E_STRICT);
		if (defined('DEBUG') && DEBUG) {
			ini_set('display_errors', 1);
		}
		self::$CLI = (php_sapi_name() == 'cli');

		date_default_timezone_set('UTC');
		ini_set('arg_separator.output', '&amp;');

		// try to switch magic quotes off.
		if (get_magic_quotes_gpc()==1) {
			ini_set('magic_quotes_runtime', 0);
		}

		//try to configure php to enable big file uploads.
		//this doesn´t work always depending on the webserver and php configuration.
		//Let´s try to overwrite some defaults anyways

		//try to set the maximum execution time to 60min
		@set_time_limit(3600);
		@ini_set('max_execution_time', 3600);
		@ini_set('max_input_time', 3600);

		//try to set the maximum filesize to 10G
		@ini_set('upload_max_filesize', '10G');
		@ini_set('post_max_size', '10G');
		@ini_set('file_uploads', '50');

		//copy http auth headers for apache+php-fcgid work around
		if (isset($_SERVER['HTTP_XAUTHORIZATION']) && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_XAUTHORIZATION'];
		}

		//set http auth headers for apache+php-cgi work around
		if (isset($_SERVER['HTTP_AUTHORIZATION'])
			&& preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
			list($name, $password) = explode(':', base64_decode($matches[1]), 2);
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
		}

		//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
		if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
			&& preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
			list($name, $password) = explode(':', base64_decode($matches[1]), 2);
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
		}

		self::initPaths();

		// set debug mode if an xdebug session is active
		if (!defined('DEBUG') || !DEBUG) {
			if (isset($_COOKIE['XDEBUG_SESSION'])) {
				define('DEBUG', true);
			}
		}

		if (!defined('PHPUNIT_RUN') and !(defined('DEBUG') and DEBUG)) {
			register_shutdown_function(array('OC_Log', 'onShutdown'));
			set_error_handler(array('OC_Log', 'onError'));
			set_exception_handler(array('OC_Log', 'onException'));
		}

		// register the stream wrappers
		stream_wrapper_register('fakedir', 'OC\Files\Stream\Dir');
		stream_wrapper_register('static', 'OC\Files\Stream\StaticStream');
		stream_wrapper_register('close', 'OC\Files\Stream\Close');
		stream_wrapper_register('oc', 'OC\Files\Stream\OC');

		self::initTemplateEngine();
		self::checkConfig();
		self::checkInstalled();
		self::checkSSL();
		self::addSecurityHeaders();
		self::initSession();

		$errors = OC_Util::checkServer();
		if (count($errors) > 0) {
			OC_Template::printGuestPage('', 'error', array('errors' => $errors));
			exit;
		}

		//try to set the session lifetime
		$sessionLifeTime = self::getSessionLifeTime();
		@ini_set('gc_maxlifetime', (string)$sessionLifeTime);

		// User and Groups
		if (!OC_Config::getValue("installed", false)) {
			$_SESSION['user_id'] = '';
		}

		OC_User::useBackend(new OC_User_Database());
		OC_Group::useBackend(new OC_Group_Database());

		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SESSION['user_id'])
			&& $_SERVER['PHP_AUTH_USER'] != $_SESSION['user_id']) {
			OC_User::logout();
		}

		// Load Apps
		// This includes plugins for users and filesystems as well
		global $RUNTIME_NOAPPS;
		global $RUNTIME_APPTYPES;
		if (!$RUNTIME_NOAPPS) {
			if ($RUNTIME_APPTYPES) {
				OC_App::loadApps($RUNTIME_APPTYPES);
			} else {
				OC_App::loadApps();
			}
		}

		//setup extra user backends
		OC_User::setupBackends();

		self::registerCacheHooks();
		self::registerFilesystemHooks();
		self::registerShareHooks();

		//make sure temporary files are cleaned up
		register_shutdown_function(array('OC_Helper', 'cleanTmp'));

		//parse the given parameters
		self::$REQUESTEDAPP = (isset($_GET['app']) && trim($_GET['app']) != '' && !is_null($_GET['app']) ? OC_App::cleanAppId(strip_tags($_GET['app'])) : OC_Config::getValue('defaultapp', 'files'));
		if (substr_count(self::$REQUESTEDAPP, '?') != 0) {
			$app = substr(self::$REQUESTEDAPP, 0, strpos(self::$REQUESTEDAPP, '?'));
			$param = substr($_GET['app'], strpos($_GET['app'], '?') + 1);
			parse_str($param, $get);
			$_GET = array_merge($_GET, $get);
			self::$REQUESTEDAPP = $app;
			$_GET['app'] = $app;
		}
		self::$REQUESTEDFILE = (isset($_GET['getfile']) ? $_GET['getfile'] : null);
		if (substr_count(self::$REQUESTEDFILE, '?') != 0) {
			$file = substr(self::$REQUESTEDFILE, 0, strpos(self::$REQUESTEDFILE, '?'));
			$param = substr(self::$REQUESTEDFILE, strpos(self::$REQUESTEDFILE, '?') + 1);
			parse_str($param, $get);
			$_GET = array_merge($_GET, $get);
			self::$REQUESTEDFILE = $file;
			$_GET['getfile'] = $file;
		}
		if (!is_null(self::$REQUESTEDFILE)) {
			$subdir = OC_App::getAppPath(OC::$REQUESTEDAPP) . '/' . self::$REQUESTEDFILE;
			$parent = OC_App::getAppPath(OC::$REQUESTEDAPP);
			if (!OC_Helper::issubdirectory($subdir, $parent)) {
				self::$REQUESTEDFILE = null;
				header('HTTP/1.0 404 Not Found');
				exit;
			}
		}

		// write error into log if locale can't be set
		if (OC_Util::issetlocaleworking() == false) {
			OC_Log::write('core',
				'setting locale to en_US.UTF-8/en_US.UTF8 failed. Support is probably not installed on your system',
				OC_Log::ERROR);
		}
		if (OC_Config::getValue('installed', false) && !self::checkUpgrade(false)) {
			if (OC_Appconfig::getValue('core', 'backgroundjobs_mode', 'ajax') == 'ajax') {
				OC_Util::addScript('backgroundjobs');
			}
		}
	}

	/**
	 * register hooks for the cache
	 */
	public static function registerCacheHooks() {
		// register cache cleanup jobs
		OC_BackgroundJob_RegularTask::register('OC_Cache_FileGlobal', 'gc');
		OC_Hook::connect('OC_User', 'post_login', 'OC_Cache_File', 'loginListener');
	}

	/**
	 * register hooks for the filesystem
	 */
	public static function registerFilesystemHooks() {
		// Check for blacklisted files
		OC_Hook::connect('OC_Filesystem', 'write', 'OC_Filesystem', 'isBlacklisted');
		OC_Hook::connect('OC_Filesystem', 'rename', 'OC_Filesystem', 'isBlacklisted');
	}

	/**
	 * register hooks for sharing
	 */
	public static function registerShareHooks() {
		if(\OC_Config::getValue('installed')) {
			OC_Hook::connect('OC_User', 'post_deleteUser', 'OCP\Share', 'post_deleteUser');
			OC_Hook::connect('OC_User', 'post_addToGroup', 'OCP\Share', 'post_addToGroup');
			OC_Hook::connect('OC_User', 'post_removeFromGroup', 'OCP\Share', 'post_removeFromGroup');
			OC_Hook::connect('OC_User', 'post_deleteGroup', 'OCP\Share', 'post_deleteGroup');
		}
	}

	/**
	 * @brief Handle the request
	 */
	public static function handleRequest() {
		// load all the classpaths from the enabled apps so they are available
		// in the routing files of each app
		OC::loadAppClassPaths();

		// Check if ownCloud is installed or in maintenance (update) mode
		if (!OC_Config::getValue('installed', false)) {
			require_once 'core/setup.php';
			exit();
		}

		$host = OC_Request::insecureServerHost();
		// if the host passed in headers isn't trusted
		if (!OC::$CLI
			// overwritehost is always trusted
			&& OC_Request::getOverwriteHost() === null
			&& !OC_Request::isTrustedDomain($host)) {

			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request');
			OC_Template::printErrorPage(
				'You are accessing the server from an untrusted domain.',
				'Please contact your administrator'
			);
			return;
		}

		$request = OC_Request::getPathInfo();
		if(substr($request, -3) !== '.js') {// we need these files during the upgrade
			self::checkMaintenanceMode();
			self::checkUpgrade();
		}

		// Test if the user is already authenticated using Apaches AuthType Basic... very usable in combination with LDAP
		OC::tryBasicAuthLogin();

		if (!self::$CLI) {
			try {
				if (!OC_Config::getValue('maintenance', false)) {
					OC_App::loadApps();
				}
				OC::getRouter()->match(OC_Request::getRawPathInfo());
				return;
			} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
				//header('HTTP/1.0 404 Not Found');
			} catch (Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
				OC_Response::setStatus(405);
				return;
			}
		}

		$app = OC::$REQUESTEDAPP;
		$file = OC::$REQUESTEDFILE;
		$param = array('app' => $app, 'file' => $file);
		// Handle app css files
		if (substr($file, -3) == 'css') {
			self::loadCSSFile($param);
			return;
		}

		// Handle redirect URL for logged in users
		if (isset($_REQUEST['redirect_url']) && OC_User::isLoggedIn()) {
			$location = OC_Helper::makeURLAbsolute(urldecode($_REQUEST['redirect_url']));

			// Deny the redirect if the URL contains a @
			// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
			if (strpos($location, '@') === FALSE) {
				header('Location: ' . $location);
				return;
			}
		}
		// Handle WebDAV
		if ($_SERVER['REQUEST_METHOD'] == 'PROPFIND') {
			header('location: ' . OC_Helper::linkToRemote('webdav'));
			return;
		}

		// Someone is logged in :
		if (OC_User::isLoggedIn()) {
			OC_App::loadApps();
			OC_User::setupBackends();
			if (isset($_GET["logout"]) and ($_GET["logout"])) {
				if (isset($_COOKIE['oc_token'])) {
					OC_Preferences::deleteKey(OC_User::getUser(), 'login_token', $_COOKIE['oc_token']);
				}
				OC_User::logout();
				header("Location: " . OC::$WEBROOT . '/');
			} else {
				if (is_null($file)) {
					$param['file'] = 'index.php';
				}
				$file_ext = substr($param['file'], -3);
				if ($file_ext != 'php'
					|| !self::loadAppScriptFile($param)
				) {
					header('HTTP/1.0 404 Not Found');
				}
			}
			return;
		}
		// Not handled and not logged in
		self::handleLogin();
	}

	public static function loadAppScriptFile($param) {
		OC_App::loadApps();
		$app = $param['app'];
		$file = $param['file'];
		$app_path = OC_App::getAppPath($app);
		$file = $app_path . '/' . $file;
		unset($app, $app_path);
		if (file_exists($file)) {
			require_once $file;
			return true;
		}
		return false;
	}

	public static function loadCSSFile($param) {
		$app = $param['app'];
		$file = $param['file'];
		$app_path = OC_App::getAppPath($app);
		if (file_exists($app_path . '/' . $file)) {
			$app_web_path = OC_App::getAppWebPath($app);
			$filepath = $app_web_path . '/' . $file;
			$minimizer = new OC_Minimizer_CSS();
			$info = array($app_path, $app_web_path, $file);
			$minimizer->output(array($info), $filepath);
		}
	}

	protected static function handleLogin() {
		OC_App::loadApps(array('prelogin'));
		$error = array();
		// remember was checked after last login
		if (OC::tryRememberLogin()) {
			$error[] = 'invalidcookie';

			// Someone wants to log in :
		} elseif (OC::tryFormLogin()) {
			$error[] = 'invalidpassword';
		}
		OC_Util::displayLoginPage(array_unique($error));
	}

	protected static function cleanupLoginTokens($user) {
		$cutoff = time() - OC_Config::getValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$tokens = OC_Preferences::getKeys($user, 'login_token');
		foreach ($tokens as $token) {
			$time = OC_Preferences::getValue($user, 'login_token', $token);
			if ($time < $cutoff) {
				OC_Preferences::deleteKey($user, 'login_token', $token);
			}
		}
	}

	protected static function tryRememberLogin() {
		if (!isset($_COOKIE["oc_remember_login"])
			|| !isset($_COOKIE["oc_token"])
			|| !isset($_COOKIE["oc_username"])
			|| !$_COOKIE["oc_remember_login"]
			|| !OC_Util::rememberLoginAllowed()
		) {
			return false;
		}
		OC_App::loadApps(array('authentication'));
		if (defined("DEBUG") && DEBUG) {
			OC_Log::write('core', 'Trying to login from cookie', OC_Log::DEBUG);
		}
		// confirm credentials in cookie
		if (isset($_COOKIE['oc_token']) && OC_User::userExists($_COOKIE['oc_username'])) {
			// delete outdated cookies
			self::cleanupLoginTokens($_COOKIE['oc_username']);
			// get stored tokens
			$tokens = OC_Preferences::getKeys($_COOKIE['oc_username'], 'login_token');
			// test cookies token against stored tokens
			if (in_array($_COOKIE['oc_token'], $tokens, true)) {
				// replace successfully used token with a new one
				OC_Preferences::deleteKey($_COOKIE['oc_username'], 'login_token', $_COOKIE['oc_token']);
				$token = OC_Util::generate_random_bytes(32);
				OC_Preferences::setValue($_COOKIE['oc_username'], 'login_token', $token, time());
				OC_User::setMagicInCookie($_COOKIE['oc_username'], $token);
				// login
				OC_User::setUserId($_COOKIE['oc_username']);
				OC_User::setDisplayName($_COOKIE['oc_username'], $_COOKIE['display_name']);
				OC_Util::redirectToDefaultPage();
				// doesn't return
			}
			// if you reach this point you have changed your password
			// or you are an attacker
			// we can not delete tokens here because users may reach
			// this point multiple times after a password change
			OC_Log::write('core', 'Authentication cookie rejected for user ' . $_COOKIE['oc_username'], OC_Log::WARN);
		}
		OC_User::unsetMagicInCookie();
		return true;
	}

	protected static function tryFormLogin() {
		if (!isset($_POST["user"]) || !isset($_POST['password'])) {
			return false;
		}

		OC_App::loadApps();

		//setup extra user backends
		OC_User::setupBackends();

		if (OC_User::login($_POST["user"], $_POST["password"])) {
			// setting up the time zone
			if (isset($_POST['timezone-offset'])) {
				$_SESSION['timezone'] = $_POST['timezone-offset'];
			}

			$userid = OC_User::getUser();
			self::cleanupLoginTokens($userid);
			if (!empty($_POST["remember_login"])) {
				if (defined("DEBUG") && DEBUG) {
					OC_Log::write('core', 'Setting remember login to cookie', OC_Log::DEBUG);
				}
				$token = OC_Util::generate_random_bytes(32);
				OC_Preferences::setValue($userid, 'login_token', $token, time());
				OC_User::setMagicInCookie($userid, $token);
			} else {
				OC_User::unsetMagicInCookie();
			}
			OC_Util::redirectToDefaultPage();
			exit();
		}
		return true;
	}

	protected static function tryBasicAuthLogin() {
		if (!isset($_SERVER["PHP_AUTH_USER"])
			|| !isset($_SERVER["PHP_AUTH_PW"])
		) {
			return false;
		}
		OC_App::loadApps(array('authentication'));
		if (OC_User::login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])) {
			//OC_Log::write('core',"Logged in with HTTP Authentication", OC_Log::DEBUG);
			OC_User::unsetMagicInCookie();
			$_SERVER['HTTP_REQUESTTOKEN'] = OC_Util::callRegister();
		}
		return true;
	}

}

// define runtime variables - unless this already has been done
if (!isset($RUNTIME_NOAPPS)) {
	$RUNTIME_NOAPPS = false;
}

if (!function_exists('get_temp_dir')) {
	function get_temp_dir() {
		if ($temp = ini_get('upload_tmp_dir')) return $temp;
		if ($temp = getenv('TMP')) return $temp;
		if ($temp = getenv('TEMP')) return $temp;
		if ($temp = getenv('TMPDIR')) return $temp;
		$temp = tempnam(__FILE__, '');
		if (file_exists($temp)) {
			unlink($temp);
			return dirname($temp);
		}
		if ($temp = sys_get_temp_dir()) return $temp;

		return null;
	}
}

OC::init();
