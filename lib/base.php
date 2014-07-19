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

	public static $configDir;

	/**
	 * requested app
	 */
	public static $REQUESTEDAPP = '';

	/**
	 * check if owncloud runs in cli mode
	 */
	public static $CLI = false;

	/**
	 * @var \OC\Session\Session
	 */
	public static $session = null;

	/**
	 * @var \OC\Autoloader $loader
	 */
	public static $loader = null;

	/**
	 * @var \OC\Server
	 */
	public static $server = null;

	public static function initPaths() {
		// calculate the root directories
		OC::$SERVERROOT = str_replace("\\", '/', substr(__DIR__, 0, -4));

		// ensure we can find OC_Config
		set_include_path(
			OC::$SERVERROOT . '/lib' . PATH_SEPARATOR .
			get_include_path()
		);

		if(defined('PHPUNIT_CONFIG_DIR')) {
			self::$configDir = OC::$SERVERROOT . '/' . PHPUNIT_CONFIG_DIR . '/';
		} elseif(defined('PHPUNIT_RUN') and PHPUNIT_RUN and is_dir(OC::$SERVERROOT . '/tests/config/')) {
			self::$configDir = OC::$SERVERROOT . '/tests/config/';
		} else {
			self::$configDir = OC::$SERVERROOT . '/config/';
		}
		OC_Config::$object = new \OC\Config(self::$configDir);

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

		if (substr($scriptName, 0 - strlen(OC::$SUBURI)) === OC::$SUBURI) {
			OC::$WEBROOT = substr($scriptName, 0, 0 - strlen(OC::$SUBURI));

			if (OC::$WEBROOT != '' && OC::$WEBROOT[0] !== '/') {
				OC::$WEBROOT = '/' . OC::$WEBROOT;
			}
		} else {
			// The scriptName is not ending with OC::$SUBURI
			// This most likely means that we are calling from CLI.
			// However some cron jobs still need to generate
			// a web URL, so we use overwritewebroot as a fallback.
			OC::$WEBROOT = OC_Config::getValue('overwritewebroot', '');
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
			throw new Exception('3rdparty directory not found! Please put the ownCloud 3rdparty'
				. ' folder in the ownCloud folder or the folder above.'
				. ' You can also configure the location in the config.php file.');
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
			throw new Exception('apps directory not found! Please put the ownCloud apps folder in the ownCloud folder'
				. ' or the folder above. You can also configure the location in the config.php file.');
		}
		$paths = array();
		foreach (OC::$APPSROOTS as $path) {
			$paths[] = $path['path'];
		}

		// set the right include path
		set_include_path(
			OC::$SERVERROOT . '/lib/private' . PATH_SEPARATOR .
			OC::$SERVERROOT . '/config' . PATH_SEPARATOR .
			OC::$THIRDPARTYROOT . '/3rdparty' . PATH_SEPARATOR .
			implode(PATH_SEPARATOR, $paths) . PATH_SEPARATOR .
			get_include_path() . PATH_SEPARATOR .
			OC::$SERVERROOT
		);
	}

	public static function checkConfig() {
		$l = OC_L10N::get('lib');
		if (file_exists(self::$configDir . "/config.php")
			and !is_writable(self::$configDir . "/config.php")
		) {
			if (self::$CLI) {
				echo $l->t('Cannot write into "config" directory!')."\n";
				echo $l->t('This can usually be fixed by giving the webserver write access to the config directory')."\n";
				echo "\n";
				echo $l->t('See %s', array(\OC_Helper::linkToDocs('admin-dir_permissions')))."\n";
				exit;
			} else {
				OC_Template::printErrorPage(
					$l->t('Cannot write into "config" directory!'),
					$l->t('This can usually be fixed by '
					. '%sgiving the webserver write access to the config directory%s.',
					 array('<a href="'.\OC_Helper::linkToDocs('admin-dir_permissions').'" target="_blank">', '</a>'))
				);
			}
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
			$tmpl = new OC_Template('', 'update.user', 'guest');
			$tmpl->printPage();
			die();
		}
	}

	public static function checkSingleUserMode() {
		$user = OC_User::getUserSession()->getUser();
		$group = OC_Group::getManager()->get('admin');
		if ($user && OC_Config::getValue('singleuser', false) && !$group->inGroup($user)) {
			// send http status 503
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 120');

			// render error page
			$tmpl = new OC_Template('', 'singleuser.user', 'guest');
			$tmpl->printPage();
			die();
		}
	}

	/**
	 * check if the instance needs to preform an upgrade
	 *
	 * @return bool
	 * @deprecated use \OCP\Util::needUpgrade instead
	 */
	public static function needUpgrade() {
		return \OCP\Util::needUpgrade();
	}

	/**
	 * Checks if the version requires an update and shows
	 * @param bool $showTemplate Whether an update screen should get shown
	 * @return bool|void
	 */
	public static function checkUpgrade($showTemplate = true) {
		if (\OCP\Util::needUpgrade()) {
			if ($showTemplate && !OC_Config::getValue('maintenance', false)) {
				$version = OC_Util::getVersion();
				$oldTheme = OC_Config::getValue('theme');
				OC_Config::setValue('theme', '');
				OC_Util::addScript('config'); // needed for web root
				OC_Util::addScript('update');
				$tmpl = new OC_Template('', 'update.admin', 'guest');
				$tmpl->assign('version', OC_Util::getVersionString());

				// get third party apps
				$apps = OC_App::getEnabledApps();
				$incompatibleApps = array();
				foreach ($apps as $appId) {
					$info = OC_App::getAppInfo($appId);
					if(!OC_App::isAppCompatible($version, $info)) {
						$incompatibleApps[] = $info;
					}
				}
				$tmpl->assign('appList', $incompatibleApps);
				$tmpl->assign('productName', 'ownCloud'); // for now
				$tmpl->assign('oldTheme', $oldTheme);
				$tmpl->printPage();
				exit();
			} else {
				return true;
			}
		}
		return false;
	}

	public static function initTemplateEngine() {
		// Add the stuff we need always
		// TODO: read from core/js/core.json
		OC_Util::addScript("jquery-1.10.0.min");
		OC_Util::addScript("jquery-migrate-1.2.1.min");
		OC_Util::addScript("jquery-ui-1.10.0.custom");
		OC_Util::addScript("jquery-showpassword");
		OC_Util::addScript("placeholders");
		OC_Util::addScript("jquery-tipsy");
		OC_Util::addScript("compatibility");
		OC_Util::addScript("underscore");
		OC_Util::addScript("jquery.ocdialog");
		OC_Util::addScript("oc-dialogs");
		OC_Util::addScript("js");
		OC_Util::addScript("octemplate");
		OC_Util::addScript("eventsource");
		OC_Util::addScript("config");
		//OC_Util::addScript( "multiselect" );
		OC_Util::addScript('search', 'result');
		OC_Util::addScript("oc-requesttoken");
		OC_Util::addScript("apps");
		OC_Util::addScript("snap");

		// avatars
		if (\OC_Config::getValue('enable_avatars', true) === true) {
			\OC_Util::addScript('placeholder');
			\OC_Util::addScript('3rdparty', 'md5/md5.min');
			\OC_Util::addScript('jquery.avatar');
			\OC_Util::addScript('avatar');
		}

		OC_Util::addStyle("styles");
		OC_Util::addStyle("header");
		OC_Util::addStyle("mobile");
		OC_Util::addStyle("icons");
		OC_Util::addStyle("fonts");
		OC_Util::addStyle("apps");
		OC_Util::addStyle("fixes");
		OC_Util::addStyle("multiselect");
		OC_Util::addStyle("jquery-ui-1.10.0.custom");
		OC_Util::addStyle("jquery-tipsy");
		OC_Util::addStyle("jquery.ocdialog");
	}

	public static function initSession() {
		// prevents javascript from accessing php session cookies
		ini_set('session.cookie_httponly', '1;');

		// set the cookie path to the ownCloud directory
		$cookie_path = OC::$WEBROOT ? : '/';
		ini_set('session.cookie_path', $cookie_path);

		//set the session object to a dummy session so code relying on the session existing still works
		self::$session = new \OC\Session\Memory('');

		// Let the session name be changed in the initSession Hook
		$sessionName = OC_Util::getInstanceId();

		try {
			// Allow session apps to create a custom session object
			$useCustomSession = false;
			OC_Hook::emit('OC', 'initSession', array('session' => &self::$session, 'sessionName' => &$sessionName, 'useCustomSession' => &$useCustomSession));
			if(!$useCustomSession) {
				// set the session name to the instance id - which is unique
				self::$session = new \OC\Session\Internal($sessionName);
			}
			// if session cant be started break with http 500 error
		} catch (Exception $e) {
			//show the user a detailed error page
			OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
			OC_Template::printExceptionErrorPage($e);
		}

		$sessionLifeTime = self::getSessionLifeTime();
		// regenerate session id periodically to avoid session fixation
		if (!self::$session->exists('SID_CREATED')) {
			self::$session->set('SID_CREATED', time());
		} else if (time() - self::$session->get('SID_CREATED') > $sessionLifeTime / 2) {
			session_regenerate_id(true);
			self::$session->set('SID_CREATED', time());
		}

		// session timeout
		if (self::$session->exists('LAST_ACTIVITY') && (time() - self::$session->get('LAST_ACTIVITY') > $sessionLifeTime)) {
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time() - 42000, $cookie_path);
			}
			session_unset();
			session_destroy();
			session_start();
		}

		self::$session->set('LAST_ACTIVITY', time());
	}

	/**
	 * @return string
	 */
	private static function getSessionLifeTime() {
		return OC_Config::getValue('session_lifetime', 60 * 60 * 24);
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
		require_once __DIR__ . '/autoloader.php';
		self::$loader = new \OC\Autoloader();
		self::$loader->registerPrefix('Doctrine\\Common', 'doctrine/common/lib');
		self::$loader->registerPrefix('Doctrine\\DBAL', 'doctrine/dbal/lib');
		self::$loader->registerPrefix('Symfony\\Component\\Routing', 'symfony/routing');
		self::$loader->registerPrefix('Symfony\\Component\\Console', 'symfony/console');
		self::$loader->registerPrefix('Patchwork', '3rdparty');
		self::$loader->registerPrefix('Pimple', '3rdparty/Pimple');
		spl_autoload_register(array(self::$loader, 'load'));

		// make a dummy session available as early as possible since error pages need it
		self::$session = new \OC\Session\Memory('');

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
		if (get_magic_quotes_gpc() == 1) {
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

		// Extract PHP_AUTH_USER/PHP_AUTH_PW from other headers if necessary.
		$httpAuthHeaderServerVars = array(
			'HTTP_AUTHORIZATION', // apache+php-cgi work around
			'REDIRECT_HTTP_AUTHORIZATION', // apache+php-cgi alternative
		);
		foreach ($httpAuthHeaderServerVars as $httpAuthHeaderServerVar) {
			if (isset($_SERVER[$httpAuthHeaderServerVar])
				&& preg_match('/Basic\s+(.*)$/i', $_SERVER[$httpAuthHeaderServerVar], $matches)
			) {
				list($name, $password) = explode(':', base64_decode($matches[1]), 2);
				$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
				$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
				break;
			}
		}

		self::initPaths();
		if (OC_Config::getValue('instanceid', false)) {
			// \OC\Memcache\Cache has a hidden dependency on
			// OC_Util::getInstanceId() for namespacing. See #5409.
			try {
				self::$loader->setMemoryCache(\OC\Memcache\Factory::createLowLatency('Autoloader'));
			} catch (\Exception $ex) {
			}
		}
		OC_Util::isSetLocaleWorking();

		// setup 3rdparty autoloader
		$vendorAutoLoad = OC::$THIRDPARTYROOT . '/3rdparty/autoload.php';
		if (file_exists($vendorAutoLoad)) {
			require_once $vendorAutoLoad;
		}

		// set debug mode if an xdebug session is active
		if (!defined('DEBUG') || !DEBUG) {
			if (isset($_COOKIE['XDEBUG_SESSION'])) {
				define('DEBUG', true);
			}
		}

		if (!defined('PHPUNIT_RUN')) {
			OC\Log\ErrorHandler::setLogger(OC_Log::$object);
			if (defined('DEBUG') and DEBUG) {
				OC\Log\ErrorHandler::register(true);
				set_exception_handler(array('OC_Template', 'printExceptionErrorPage'));
			} else {
				OC\Log\ErrorHandler::register();
			}
		}

		// register the stream wrappers
		stream_wrapper_register('fakedir', 'OC\Files\Stream\Dir');
		stream_wrapper_register('static', 'OC\Files\Stream\StaticStream');
		stream_wrapper_register('close', 'OC\Files\Stream\Close');
		stream_wrapper_register('quota', 'OC\Files\Stream\Quota');
		stream_wrapper_register('oc', 'OC\Files\Stream\OC');

		// setup the basic server
		self::$server = new \OC\Server();

		self::initTemplateEngine();
		OC_App::loadApps(array('session'));
		if (!self::$CLI) {
			self::initSession();
		} else {
			self::$session = new \OC\Session\Memory('');
		}
		self::checkConfig();
		self::checkInstalled();
		self::checkSSL();
		OC_Response::addSecurityHeaders();

		$errors = OC_Util::checkServer();
		if (count($errors) > 0) {
			if (self::$CLI) {
				foreach ($errors as $error) {
					echo $error['error'] . "\n";
					echo $error['hint'] . "\n\n";
				}
			} else {
				OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
				OC_Template::printGuestPage('', 'error', array('errors' => $errors));
			}
			exit;
		}

		//try to set the session lifetime
		$sessionLifeTime = self::getSessionLifeTime();
		@ini_set('gc_maxlifetime', (string)$sessionLifeTime);

		// User and Groups
		if (!OC_Config::getValue("installed", false)) {
			self::$session->set('user_id', '');
		}

		OC_User::useBackend(new OC_User_Database());
		OC_Group::useBackend(new OC_Group_Database());

		//setup extra user backends
		OC_User::setupBackends();

		self::registerCacheHooks();
		self::registerFilesystemHooks();
		self::registerPreviewHooks();
		self::registerShareHooks();
		self::registerLogRotate();

		//make sure temporary files are cleaned up
		register_shutdown_function(array('OC_Helper', 'cleanTmp'));

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
		if (OC_Config::getValue('installed', false) && !\OCP\Util::needUpgrade()) { //don't try to do this before we are properly setup
			\OCP\BackgroundJob::registerJob('OC\Cache\FileGlobalGC');

			// NOTE: This will be replaced to use OCP
			$userSession = \OC_User::getUserSession();
			$userSession->listen('postLogin', '\OC\Cache\File', 'loginListener');
		}
	}

	/**
	 * register hooks for the cache
	 */
	public static function registerLogRotate() {
		if (OC_Config::getValue('installed', false) && OC_Config::getValue('log_rotate_size', false) && !\OCP\Util::needUpgrade()) {
			//don't try to do this before we are properly setup
			//use custom logfile path if defined, otherwise use default of owncloud.log in data directory
			\OCP\BackgroundJob::registerJob('OC\Log\Rotate', OC_Config::getValue('logfile', OC_Config::getValue("datadirectory", OC::$SERVERROOT . '/data') . '/owncloud.log'));
		}
	}

	/**
	 * register hooks for the filesystem
	 */
	public static function registerFilesystemHooks() {
		// Check for blacklisted files
		OC_Hook::connect('OC_Filesystem', 'write', 'OC\Files\Filesystem', 'isBlacklisted');
		OC_Hook::connect('OC_Filesystem', 'rename', 'OC\Files\Filesystem', 'isBlacklisted');
	}

	/**
	 * register hooks for previews
	 */
	public static function registerPreviewHooks() {
		OC_Hook::connect('OC_Filesystem', 'post_write', 'OC\Preview', 'post_write');
		OC_Hook::connect('OC_Filesystem', 'delete', 'OC\Preview', 'prepare_delete_files');
		OC_Hook::connect('\OCP\Versions', 'preDelete', 'OC\Preview', 'prepare_delete');
		OC_Hook::connect('\OCP\Trashbin', 'preDelete', 'OC\Preview', 'prepare_delete');
		OC_Hook::connect('OC_Filesystem', 'post_delete', 'OC\Preview', 'post_delete_files');
		OC_Hook::connect('\OCP\Versions', 'delete', 'OC\Preview', 'post_delete');
		OC_Hook::connect('\OCP\Trashbin', 'delete', 'OC\Preview', 'post_delete');
	}

	/**
	 * register hooks for sharing
	 */
	public static function registerShareHooks() {
		if (\OC_Config::getValue('installed')) {
			OC_Hook::connect('OC_User', 'post_deleteUser', 'OC\Share\Hooks', 'post_deleteUser');
			OC_Hook::connect('OC_User', 'post_addToGroup', 'OC\Share\Hooks', 'post_addToGroup');
			OC_Hook::connect('OC_User', 'post_removeFromGroup', 'OC\Share\Hooks', 'post_removeFromGroup');
			OC_Hook::connect('OC_User', 'post_deleteGroup', 'OC\Share\Hooks', 'post_deleteGroup');
		}
	}

	/**
	 * Handle the request
	 */
	public static function handleRequest() {
		$l = \OC_L10N::get('lib');
		// load all the classpaths from the enabled apps so they are available
		// in the routing files of each app
		OC::loadAppClassPaths();

		// Check if ownCloud is installed or in maintenance (update) mode
		if (!OC_Config::getValue('installed', false)) {
			$controller = new OC\Core\Setup\Controller();
			$controller->run($_POST);
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
				$l->t('You are accessing the server from an untrusted domain.'),
				$l->t('Please contact your administrator. If you are an administrator of this instance, configure the "trusted_domain" setting in config/config.php. An example configuration is provided in config/config.sample.php.')
			);
			return;
		}

		$request = OC_Request::getPathInfo();
		if (substr($request, -3) !== '.js') { // we need these files during the upgrade
			self::checkMaintenanceMode();
			self::checkUpgrade();
		}

		if (!OC_User::isLoggedIn()) {
			// Test it the user is already authenticated using Apaches AuthType Basic... very usable in combination with LDAP
			OC::tryBasicAuthLogin();
		}


		if (!self::$CLI and (!isset($_GET["logout"]) or ($_GET["logout"] !== 'true'))) {
			try {
				if (!OC_Config::getValue('maintenance', false) && !\OCP\Util::needUpgrade()) {
					OC_App::loadApps(array('authentication'));
					OC_App::loadApps(array('filesystem', 'logging'));
					OC_App::loadApps();
				}
				self::checkSingleUserMode();
				OC::$server->getRouter()->match(OC_Request::getRawPathInfo());
				return;
			} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
				//header('HTTP/1.0 404 Not Found');
			} catch (Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
				OC_Response::setStatus(405);
				return;
			}
		}

		// Load minimum set of apps
		if (!self::checkUpgrade(false)) {
			// For logged-in users: Load everything
			if(OC_User::isLoggedIn()) {
				OC_App::loadApps();
			} else {
				// For guests: Load only authentication, filesystem and logging
				OC_App::loadApps(array('authentication'));
				OC_App::loadApps(array('filesystem', 'logging'));
			}
		}

		// Handle redirect URL for logged in users
		if (isset($_REQUEST['redirect_url']) && OC_User::isLoggedIn()) {
			$location = OC_Helper::makeURLAbsolute(urldecode($_REQUEST['redirect_url']));

			// Deny the redirect if the URL contains a @
			// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
			if (strpos($location, '@') === false) {
				header('Location: ' . $location);
				return;
			}
		}
		// Handle WebDAV
		if ($_SERVER['REQUEST_METHOD'] == 'PROPFIND') {
			// not allowed any more to prevent people
			// mounting this root directly.
			// Users need to mount remote.php/webdav instead.
			header('HTTP/1.1 405 Method Not Allowed');
			header('Status: 405 Method Not Allowed');
			return;
		}

		// Redirect to index if the logout link is accessed without valid session
		// this is needed to prevent "Token expired" messages while login if a session is expired
		// @see https://github.com/owncloud/core/pull/8443#issuecomment-42425583
		if(isset($_GET['logout']) && !OC_User::isLoggedIn()) {
			header("Location: " . OC::$WEBROOT.(empty(OC::$WEBROOT) ? '/' : ''));
			return;
		}

		// Someone is logged in
		if (OC_User::isLoggedIn()) {
			OC_App::loadApps();
			OC_User::setupBackends();
			if (isset($_GET["logout"]) and ($_GET["logout"])) {
				OC_JSON::callCheck();
				if (isset($_COOKIE['oc_token'])) {
					OC_Preferences::deleteKey(OC_User::getUser(), 'login_token', $_COOKIE['oc_token']);
				}
				if (isset($_SERVER['PHP_AUTH_USER'])) {
					if (isset($_COOKIE['oc_ignore_php_auth_user'])) {
						// Ignore HTTP Authentication for 5 more mintues.
						setcookie('oc_ignore_php_auth_user', $_SERVER['PHP_AUTH_USER'], time() + 300, OC::$WEBROOT.(empty(OC::$WEBROOT) ? '/' : ''));
					} elseif ($_SERVER['PHP_AUTH_USER'] === self::$session->get('loginname')) {
						// Ignore HTTP Authentication to allow a different user to log in.
						setcookie('oc_ignore_php_auth_user', $_SERVER['PHP_AUTH_USER'], 0, OC::$WEBROOT.(empty(OC::$WEBROOT) ? '/' : ''));
					}
				}
				OC_User::logout();
				// redirect to webroot and add slash if webroot is empty
				header("Location: " . OC::$WEBROOT.(empty(OC::$WEBROOT) ? '/' : ''));
			} else {
				// Redirect to default application
				OC_Util::redirectToDefaultPage();
			}
		} else {
			// Not handled and not logged in
			self::handleLogin();
		}
	}

	/**
	 * Load a PHP file belonging to the specified application
	 * @param array $param The application and file to load
	 * @return bool Whether the file has been found (will return 404 and false if not)
	 * @deprecated This function will be removed in ownCloud 8 - use proper routing instead
	 * @param $param
	 * @return bool Whether the file has been found (will return 404 and false if not)
	 */
	public static function loadAppScriptFile($param) {
		OC_App::loadApps();
		$app = $param['app'];
		$file = $param['file'];
		$app_path = OC_App::getAppPath($app);
		$file = $app_path . '/' . $file;

		if (OC_App::isEnabled($app) && $app_path !== false && OC_Helper::issubdirectory($file, $app_path)) {
			unset($app, $app_path);
			if (file_exists($file)) {
				require_once $file;
				return true;
			}
		}
		header('HTTP/1.0 404 Not Found');
		return false;
	}

	protected static function handleLogin() {
		OC_App::loadApps(array('prelogin'));
		$error = array();

		// auth possible via apache module?
		if (OC::tryApacheAuth()) {
			$error[] = 'apacheauthfailed';
		} // remember was checked after last login
		elseif (OC::tryRememberLogin()) {
			$error[] = 'invalidcookie';
		} // logon via web form
		elseif (OC::tryFormLogin()) {
			$error[] = 'invalidpassword';
			if ( OC_Config::getValue('log_authfailip', false) ) {
				OC_Log::write('core', 'Login failed: user \''.$_POST["user"].'\' , wrong password, IP:'.$_SERVER['REMOTE_ADDR'],
				OC_Log::WARN);
			} else {
				OC_Log::write('core', 'Login failed: user \''.$_POST["user"].'\' , wrong password, IP:set log_authfailip=true in conf',
                                OC_Log::WARN);
			}
		}

		OC_Util::displayLoginPage(array_unique($error));
	}

	/**
	 * Remove outdated and therefore invalid tokens for a user
	 * @param string $user
	 */
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

	/**
	 * Try to login a user via HTTP authentication
	 * @return bool|void
	 */
	protected static function tryApacheAuth() {
		$return = OC_User::handleApacheAuth();

		// if return is true we are logged in -> redirect to the default page
		if ($return === true) {
			$_REQUEST['redirect_url'] = \OC_Request::requestUri();
			OC_Util::redirectToDefaultPage();
			exit;
		}

		// in case $return is null apache based auth is not enabled
		return is_null($return) ? false : true;
	}

	/**
	 * Try to login a user using the remember me cookie.
	 * @return bool Whether the provided cookie was valid
	 */
	protected static function tryRememberLogin() {
		if (!isset($_COOKIE["oc_remember_login"])
			|| !isset($_COOKIE["oc_token"])
			|| !isset($_COOKIE["oc_username"])
			|| !$_COOKIE["oc_remember_login"]
			|| !OC_Util::rememberLoginAllowed()
		) {
			return false;
		}

		if (defined("DEBUG") && DEBUG) {
			OC_Log::write('core', 'Trying to login from cookie', OC_Log::DEBUG);
		}

		if(OC_User::userExists($_COOKIE['oc_username'])) {
			self::cleanupLoginTokens($_COOKIE['oc_username']);
			// verify whether the supplied "remember me" token was valid
			$granted = OC_User::loginWithCookie(
				$_COOKIE['oc_username'], $_COOKIE['oc_token']);
			if($granted === true) {
				OC_Util::redirectToDefaultPage();
				// doesn't return
			}
			OC_Log::write('core', 'Authentication cookie rejected for user ' .
				$_COOKIE['oc_username'], OC_Log::WARN);
			// if you reach this point you have changed your password
			// or you are an attacker
			// we can not delete tokens here because users may reach
			// this point multiple times after a password change
		}

		OC_User::unsetMagicInCookie();
		return true;
	}

	/**
	 * Tries to login a user using the formbased authentication
	 * @return bool|void
	 */
	protected static function tryFormLogin() {
		if (!isset($_POST["user"]) || !isset($_POST['password'])) {
			return false;
		}

		OC_JSON::callCheck();
		OC_App::loadApps();

		//setup extra user backends
		OC_User::setupBackends();

		if (OC_User::login($_POST["user"], $_POST["password"])) {
			// setting up the time zone
			if (isset($_POST['timezone-offset'])) {
				self::$session->set('timezone', $_POST['timezone-offset']);
			}

			$userid = OC_User::getUser();
			self::cleanupLoginTokens($userid);
			if (!empty($_POST["remember_login"])) {
				if (defined("DEBUG") && DEBUG) {
					OC_Log::write('core', 'Setting remember login to cookie', OC_Log::DEBUG);
				}
				$token = OC_Util::generateRandomBytes(32);
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

	/**
	 * Try to login a user using HTTP authentication.
	 * @return bool
	 */
	protected static function tryBasicAuthLogin() {
		if (!isset($_SERVER["PHP_AUTH_USER"])
			|| !isset($_SERVER["PHP_AUTH_PW"])
			|| (isset($_COOKIE['oc_ignore_php_auth_user']) && $_COOKIE['oc_ignore_php_auth_user'] === $_SERVER['PHP_AUTH_USER'])
		) {
			return false;
		}

		if (OC_User::login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])) {
			//OC_Log::write('core',"Logged in with HTTP Authentication", OC_Log::DEBUG);
			OC_User::unsetMagicInCookie();
			$_SERVER['HTTP_REQUESTTOKEN'] = OC_Util::callRegister();
		}
		return true;
	}

}

if (!function_exists('get_temp_dir')) {
	/**
	 * Get the temporary dir to store uploaded data
	 * @return null|string Path to the temporary directory or null
	 */
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
