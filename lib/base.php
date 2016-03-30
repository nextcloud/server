<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author davidgumberg <davidnoizgumberg@gmail.com>
 * @author Florian Scholz <FlorianScholz@bgstyle.de>
 * @author Florin Peter <github@florin-peter.de>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Hugo Gonzalez Labrador <hglavra@gmail.com>
 * @author Individual IT Services <info@individual-it.net>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author marc0s <marcos@tenak.net>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Phil Davis <phil.davis@inf.org>
 * @author Ramiro Aparicio <rapariciog@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author scolebrook <scolebrook@mac.com>
 * @author Stefan Herbrechtsmeier <stefan@herbrechtsmeier.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Volkan Gezer <volkangezer@gmail.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
	 * check if ownCloud runs in cli mode
	 */
	public static $CLI = false;

	/**
	 * @var \OC\Autoloader $loader
	 */
	public static $loader = null;

	/**
	 * @var \OC\Server
	 */
	public static $server = null;

	/**
	 * @throws \RuntimeException when the 3rdparty directory is missing or
	 * the app path list is empty or contains an invalid path
	 */
	public static function initPaths() {
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
		/**
		 * FIXME: The following lines are required because we can't yet instantiiate
		 *        \OC::$server->getRequest() since \OC::$server does not yet exist.
		 */
		$params = [
			'server' => [
				'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
			],
		];
		$fakeRequest = new \OC\AppFramework\Http\Request($params, null, new \OC\AllConfig(new \OC\SystemConfig()));
		$scriptName = $fakeRequest->getScriptName();
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


		if (OC::$CLI) {
			OC::$WEBROOT = OC_Config::getValue('overwritewebroot', '');
		} else {
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
		}

		// search the 3rdparty folder
		OC::$THIRDPARTYROOT = OC_Config::getValue('3rdpartyroot', null);
		OC::$THIRDPARTYWEBROOT = OC_Config::getValue('3rdpartyurl', null);

		if (empty(OC::$THIRDPARTYROOT) && empty(OC::$THIRDPARTYWEBROOT)) {
			if (file_exists(OC::$SERVERROOT . '/3rdparty')) {
				OC::$THIRDPARTYROOT = OC::$SERVERROOT;
				OC::$THIRDPARTYWEBROOT = OC::$WEBROOT;
			} elseif (file_exists(OC::$SERVERROOT . '/../3rdparty')) {
				OC::$THIRDPARTYWEBROOT = rtrim(dirname(OC::$WEBROOT), '/');
				OC::$THIRDPARTYROOT = rtrim(dirname(OC::$SERVERROOT), '/');
			}
		}
		if (empty(OC::$THIRDPARTYROOT) || !file_exists(OC::$THIRDPARTYROOT)) {
			throw new \RuntimeException('3rdparty directory not found! Please put the ownCloud 3rdparty'
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
			throw new \RuntimeException('apps directory not found! Please put the ownCloud apps folder in the ownCloud folder'
				. ' or the folder above. You can also configure the location in the config.php file.');
		}
		$paths = array();
		foreach (OC::$APPSROOTS as $path) {
			$paths[] = $path['path'];
			if (!is_dir($path['path'])) {
				throw new \RuntimeException(sprintf('App directory "%s" not found! Please put the ownCloud apps folder in the'
					. ' ownCloud folder or the folder above. You can also configure the location in the'
					. ' config.php file.', $path['path']));
			}
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
		$l = \OC::$server->getL10N('lib');

		// Create config if it does not already exist
		$configFilePath = self::$configDir .'/config.php';
		if(!file_exists($configFilePath)) {
			@touch($configFilePath);
		}

		// Check if config is writable
		$configFileWritable = is_writable($configFilePath);
		if (!$configFileWritable && !OC_Helper::isReadOnlyConfigEnabled()
			|| !$configFileWritable && self::checkUpgrade(false)) {
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
		if (defined('OC_CONSOLE')) {
			return;
		}
		// Redirect to installer if not installed
		if (!\OC::$server->getSystemConfig()->getValue('installed', false) && OC::$SUBURI != '/index.php') {
			if (OC::$CLI) {
				throw new Exception('Not installed');
			} else {
				$url = 'http://' . $_SERVER['SERVER_NAME'] . OC::$WEBROOT . '/index.php';
				header('Location: ' . $url);
			}
			exit();
		}
	}

	public static function checkMaintenanceMode() {
		// Allow ajax update script to execute without being stopped
		if (\OC::$server->getSystemConfig()->getValue('maintenance', false) && OC::$SUBURI != '/core/ajax/update.php') {
			// send http status 503
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 120');

			// render error page
			$template = new OC_Template('', 'update.user', 'guest');
			OC_Util::addscript('maintenance-check');
			$template->printPage();
			die();
		}
	}

	public static function checkSingleUserMode($lockIfNoUserLoggedIn = false) {
		if (!\OC::$server->getSystemConfig()->getValue('singleuser', false)) {
			return;
		}
		$user = OC_User::getUserSession()->getUser();
		if ($user) {
			$group = \OC::$server->getGroupManager()->get('admin');
			if ($group->inGroup($user)) {
				return;
			}
		} else {
			if(!$lockIfNoUserLoggedIn) {
				return;
			}
		}
		// send http status 503
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 120');

		// render error page
		$template = new OC_Template('', 'singleuser.user', 'guest');
		$template->printPage();
		die();
	}

	/**
	 * check if the instance needs to perform an upgrade
	 *
	 * @return bool
	 * @deprecated use \OCP\Util::needUpgrade() instead
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
			$systemConfig = \OC::$server->getSystemConfig();
			if ($showTemplate && !$systemConfig->getValue('maintenance', false)) {
				self::printUpgradePage();
				exit();
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Prints the upgrade page
	 */
	private static function printUpgradePage() {
		$systemConfig = \OC::$server->getSystemConfig();
		$oldTheme = $systemConfig->getValue('theme');
		$systemConfig->setValue('theme', '');
		\OCP\Util::addScript('config'); // needed for web root
		\OCP\Util::addScript('update');

		// check whether this is a core update or apps update
		$installedVersion = $systemConfig->getValue('version', '0.0.0');
		$currentVersion = implode('.', OC_Util::getVersion());

		$appManager = \OC::$server->getAppManager();

		$tmpl = new OC_Template('', 'update.admin', 'guest');
		$tmpl->assign('version', OC_Util::getVersionString());

		// if not a core upgrade, then it's apps upgrade
		if (version_compare($currentVersion, $installedVersion, '=')) {
			$tmpl->assign('isAppsOnlyUpgrade', true);
		} else {
			$tmpl->assign('isAppsOnlyUpgrade', false);
		}

		// get third party apps
		$ocVersion = OC_Util::getVersion();
		$tmpl->assign('appsToUpgrade', $appManager->getAppsNeedingUpgrade($ocVersion));
		$tmpl->assign('incompatibleAppsList', $appManager->getIncompatibleApps($ocVersion));
		$tmpl->assign('productName', 'ownCloud'); // for now
		$tmpl->assign('oldTheme', $oldTheme);
		$tmpl->printPage();
	}

	public static function initSession() {
		// prevents javascript from accessing php session cookies
		ini_set('session.cookie_httponly', true);

		// set the cookie path to the ownCloud directory
		$cookie_path = OC::$WEBROOT ? : '/';
		ini_set('session.cookie_path', $cookie_path);

		// Let the session name be changed in the initSession Hook
		$sessionName = OC_Util::getInstanceId();

		try {
			// Allow session apps to create a custom session object
			$useCustomSession = false;
			$session = self::$server->getSession();
			OC_Hook::emit('OC', 'initSession', array('session' => &$session, 'sessionName' => &$sessionName, 'useCustomSession' => &$useCustomSession));
			if (!$useCustomSession) {
				// set the session name to the instance id - which is unique
				$session = new \OC\Session\Internal($sessionName);
			}

			$cryptoWrapper = \OC::$server->getSessionCryptoWrapper();
			$session = $cryptoWrapper->wrapSession($session);
			self::$server->setSession($session);

			// if session cant be started break with http 500 error
		} catch (Exception $e) {
			\OCP\Util::logException('base', $e);
			//show the user a detailed error page
			OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
			OC_Template::printExceptionErrorPage($e);
		}

		$sessionLifeTime = self::getSessionLifeTime();
		// regenerate session id periodically to avoid session fixation
		/**
		 * @var \OCP\ISession $session
		 */
		$session = self::$server->getSession();
		if (!$session->exists('SID_CREATED')) {
			$session->set('SID_CREATED', time());
		} else if (time() - $session->get('SID_CREATED') > $sessionLifeTime / 2) {
			session_regenerate_id(true);
			$session->set('SID_CREATED', time());
		}

		// session timeout
		if ($session->exists('LAST_ACTIVITY') && (time() - $session->get('LAST_ACTIVITY') > $sessionLifeTime)) {
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time() - 42000, $cookie_path);
			}
			$session->clear();
		}

		$session->set('LAST_ACTIVITY', time());
	}

	/**
	 * @return string
	 */
	private static function getSessionLifeTime() {
		return \OC::$server->getConfig()->getSystemValue('session_lifetime', 60 * 60 * 24);
	}

	public static function loadAppClassPaths() {
		foreach (OC_APP::getEnabledApps() as $app) {
			$file = OC_App::getAppPath($app) . '/appinfo/classpath.php';
			if (file_exists($file)) {
				require_once $file;
			}
		}
	}

	/**
	 * Try to set some values to the required ownCloud default
	 */
	public static function setRequiredIniValues() {
		@ini_set('default_charset', 'UTF-8');
	}

	public static function init() {
		// calculate the root directories
		OC::$SERVERROOT = str_replace("\\", '/', substr(__DIR__, 0, -4));

		// register autoloader
		$loaderStart = microtime(true);
		require_once __DIR__ . '/autoloader.php';
		self::$loader = new \OC\Autoloader([
			OC::$SERVERROOT . '/lib',
			OC::$SERVERROOT . '/core',
			OC::$SERVERROOT . '/settings',
			OC::$SERVERROOT . '/ocs',
			OC::$SERVERROOT . '/ocs-provider',
			OC::$SERVERROOT . '/3rdparty',
			OC::$SERVERROOT . '/tests',
		]);
		spl_autoload_register(array(self::$loader, 'load'));
		$loaderEnd = microtime(true);

		self::$CLI = (php_sapi_name() == 'cli');

		try {
			self::initPaths();
			// setup 3rdparty autoloader
			$vendorAutoLoad = OC::$THIRDPARTYROOT . '/3rdparty/autoload.php';
			if (!file_exists($vendorAutoLoad)) {
				throw new \RuntimeException('Composer autoloader not found, unable to continue. Check the folder "3rdparty". Running "git submodule update --init" will initialize the git submodule that handles the subfolder "3rdparty".');
			}
			require_once $vendorAutoLoad;

		} catch (\RuntimeException $e) {
			OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
			// we can't use the template error page here, because this needs the
			// DI container which isn't available yet
			print($e->getMessage());
			exit();
		}

		// setup the basic server
		self::$server = new \OC\Server(\OC::$WEBROOT);
		\OC::$server->getEventLogger()->log('autoloader', 'Autoloader', $loaderStart, $loaderEnd);
		\OC::$server->getEventLogger()->start('boot', 'Initialize');

		// Don't display errors and log them
		error_reporting(E_ALL | E_STRICT);
		@ini_set('display_errors', 0);
		@ini_set('log_errors', 1);

		date_default_timezone_set('UTC');

		//try to configure php to enable big file uploads.
		//this doesn´t work always depending on the webserver and php configuration.
		//Let´s try to overwrite some defaults anyway

		//try to set the maximum execution time to 60min
		@set_time_limit(3600);
		@ini_set('max_execution_time', 3600);
		@ini_set('max_input_time', 3600);

		//try to set the maximum filesize to 10G
		@ini_set('upload_max_filesize', '10G');
		@ini_set('post_max_size', '10G');
		@ini_set('file_uploads', '50');

		self::setRequiredIniValues();
		self::handleAuthHeaders();
		self::registerAutoloaderCache();

		// initialize intl fallback is necessary
		\Patchwork\Utf8\Bootup::initIntl();
		OC_Util::isSetLocaleWorking();

		if (!defined('PHPUNIT_RUN')) {
			$logger = \OC::$server->getLogger();
			OC\Log\ErrorHandler::setLogger($logger);
			if (\OC::$server->getConfig()->getSystemValue('debug', false)) {
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

		\OC::$server->getEventLogger()->start('init_session', 'Initialize session');
		OC_App::loadApps(array('session'));
		if (!self::$CLI) {
			self::initSession();
		}
		\OC::$server->getEventLogger()->end('init_session');
		self::checkConfig();
		self::checkInstalled();

		OC_Response::addSecurityHeaders();
		if(self::$server->getRequest()->getServerProtocol() === 'https') {
			ini_set('session.cookie_secure', true);
		}

		if (!defined('OC_CONSOLE')) {
			$errors = OC_Util::checkServer(\OC::$server->getConfig());
			if (count($errors) > 0) {
				if (self::$CLI) {
					// Convert l10n string into regular string for usage in database
					$staticErrors = [];
					foreach ($errors as $error) {
						echo $error['error'] . "\n";
						echo $error['hint'] . "\n\n";
						$staticErrors[] = [
							'error' => (string)$error['error'],
							'hint' => (string)$error['hint'],
						];
					}

					try {
						\OC::$server->getConfig()->setAppValue('core', 'cronErrors', json_encode($staticErrors));
					} catch (\Exception $e) {
						echo('Writing to database failed');
					}
					exit(1);
				} else {
					OC_Response::setStatus(OC_Response::STATUS_SERVICE_UNAVAILABLE);
					OC_Template::printGuestPage('', 'error', array('errors' => $errors));
					exit;
				}
			} elseif (self::$CLI && \OC::$server->getConfig()->getSystemValue('installed', false)) {
				\OC::$server->getConfig()->deleteAppValue('core', 'cronErrors');
			}
		}
		//try to set the session lifetime
		$sessionLifeTime = self::getSessionLifeTime();
		@ini_set('gc_maxlifetime', (string)$sessionLifeTime);

		$systemConfig = \OC::$server->getSystemConfig();

		// User and Groups
		if (!$systemConfig->getValue("installed", false)) {
			self::$server->getSession()->set('user_id', '');
		}

		OC_User::useBackend(new OC_User_Database());
		OC_Group::useBackend(new OC_Group_Database());

		// Subscribe to the hook
		\OCP\Util::connectHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			'\OC_User_Database',
			'preLoginNameUsedAsUserName'
		);

		//setup extra user backends
		if (!self::checkUpgrade(false)) {
			OC_User::setupBackends();
		}

		self::registerCacheHooks();
		self::registerFilesystemHooks();
		if ($systemConfig->getValue('enable_previews', true)) {
			self::registerPreviewHooks();
		}
		self::registerShareHooks();
		self::registerLogRotate();
		self::registerLocalAddressBook();
		self::registerEncryptionWrapper();
		self::registerEncryptionHooks();

		//make sure temporary files are cleaned up
		$tmpManager = \OC::$server->getTempManager();
		register_shutdown_function(array($tmpManager, 'clean'));
		$lockProvider = \OC::$server->getLockingProvider();
		register_shutdown_function(array($lockProvider, 'releaseAll'));

		// Check whether the sample configuration has been copied
		if($systemConfig->getValue('copied_sample_config', false)) {
			$l = \OC::$server->getL10N('lib');
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			OC_Template::printErrorPage(
				$l->t('Sample configuration detected'),
				$l->t('It has been detected that the sample configuration has been copied. This can break your installation and is unsupported. Please read the documentation before performing changes on config.php')
			);
			return;
		}

		$request = \OC::$server->getRequest();
		$host = $request->getInsecureServerHost();
		/**
		 * if the host passed in headers isn't trusted
		 * FIXME: Should not be in here at all :see_no_evil:
		 */
		if (!OC::$CLI
			// overwritehost is always trusted, workaround to not have to make
			// \OC\AppFramework\Http\Request::getOverwriteHost public
			&& self::$server->getConfig()->getSystemValue('overwritehost') === ''
			&& !\OC::$server->getTrustedDomainHelper()->isTrustedDomain($host)
			&& self::$server->getConfig()->getSystemValue('installed', false)
		) {
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request');

			$tmpl = new OCP\Template('core', 'untrustedDomain', 'guest');
			$tmpl->assign('domain', $request->server['SERVER_NAME']);
			$tmpl->printPage();

			exit();
		}
		\OC::$server->getEventLogger()->end('boot');
	}

	private static function registerLocalAddressBook() {
		self::$server->getContactsManager()->register(function() {
			$userManager = \OC::$server->getUserManager();
			\OC::$server->getContactsManager()->registerAddressBook(
				new \OC\Contacts\LocalAddressBook($userManager));
		});
	}

	/**
	 * register hooks for the cache
	 */
	public static function registerCacheHooks() {
		//don't try to do this before we are properly setup
		if (\OC::$server->getSystemConfig()->getValue('installed', false) && !self::checkUpgrade(false)) {

			// NOTE: This will be replaced to use OCP
			$userSession = self::$server->getUserSession();
			$userSession->listen('\OC\User', 'postLogin', function () {
				try {
					$cache = new \OC\Cache\File();
					$cache->gc();
				} catch (\OC\ServerNotAvailableException $e) {
					// not a GC exception, pass it on
					throw $e;
				} catch (\Exception $e) {
					// a GC exception should not prevent users from using OC,
					// so log the exception
					\OC::$server->getLogger()->warning('Exception when running cache gc: ' . $e->getMessage(), array('app' => 'core'));
				}
			});
		}
	}

	private static function registerEncryptionWrapper() {
		$manager = self::$server->getEncryptionManager();
		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', $manager, 'setupStorage');
	}

	private static function registerEncryptionHooks() {
		$enabled = self::$server->getEncryptionManager()->isEnabled();
		if ($enabled) {
			\OCP\Util::connectHook('OCP\Share', 'post_shared', 'OC\Encryption\HookManager', 'postShared');
			\OCP\Util::connectHook('OCP\Share', 'post_unshare', 'OC\Encryption\HookManager', 'postUnshared');
			\OCP\Util::connectHook('OC_Filesystem', 'post_rename', 'OC\Encryption\HookManager', 'postRename');
			\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', 'OC\Encryption\HookManager', 'postRestore');
		}
	}

	/**
	 * register hooks for the cache
	 */
	public static function registerLogRotate() {
		$systemConfig = \OC::$server->getSystemConfig();
		if ($systemConfig->getValue('installed', false) && $systemConfig->getValue('log_rotate_size', false) && !self::checkUpgrade(false)) {
			//don't try to do this before we are properly setup
			//use custom logfile path if defined, otherwise use default of owncloud.log in data directory
			\OCP\BackgroundJob::registerJob('OC\Log\Rotate', $systemConfig->getValue('logfile', $systemConfig->getValue('datadirectory', OC::$SERVERROOT . '/data') . '/owncloud.log'));
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
		OC_Hook::connect('\OCP\Versions', 'delete', 'OC\Preview', 'post_delete_versions');
		OC_Hook::connect('\OCP\Trashbin', 'delete', 'OC\Preview', 'post_delete');
		OC_Hook::connect('\OCP\Versions', 'rollback', 'OC\Preview', 'post_delete_versions');
	}

	/**
	 * register hooks for sharing
	 */
	public static function registerShareHooks() {
		if (\OC::$server->getSystemConfig()->getValue('installed')) {
			OC_Hook::connect('OC_User', 'post_deleteUser', 'OC\Share\Hooks', 'post_deleteUser');
			OC_Hook::connect('OC_User', 'post_addToGroup', 'OC\Share\Hooks', 'post_addToGroup');
			OC_Hook::connect('OC_Group', 'pre_addToGroup', 'OC\Share\Hooks', 'pre_addToGroup');
			OC_Hook::connect('OC_User', 'post_removeFromGroup', 'OC\Share\Hooks', 'post_removeFromGroup');
			OC_Hook::connect('OC_User', 'post_deleteGroup', 'OC\Share\Hooks', 'post_deleteGroup');
		}
	}

	protected static function registerAutoloaderCache() {
		// The class loader takes an optional low-latency cache, which MUST be
		// namespaced. The instanceid is used for namespacing, but might be
		// unavailable at this point. Futhermore, it might not be possible to
		// generate an instanceid via \OC_Util::getInstanceId() because the
		// config file may not be writable. As such, we only register a class
		// loader cache if instanceid is available without trying to create one.
		$instanceId = \OC::$server->getSystemConfig()->getValue('instanceid', null);
		if ($instanceId) {
			try {
				$memcacheFactory = \OC::$server->getMemCacheFactory();
				self::$loader->setMemoryCache($memcacheFactory->createLocal('Autoloader'));
			} catch (\Exception $ex) {
			}
		}
	}

	/**
	 * Handle the request
	 */
	public static function handleRequest() {

		\OC::$server->getEventLogger()->start('handle_request', 'Handle request');
		$systemConfig = \OC::$server->getSystemConfig();
		// load all the classpaths from the enabled apps so they are available
		// in the routing files of each app
		OC::loadAppClassPaths();

		// Check if ownCloud is installed or in maintenance (update) mode
		if (!$systemConfig->getValue('installed', false)) {
			\OC::$server->getSession()->clear();
			$setupHelper = new OC\Setup(\OC::$server->getConfig(), \OC::$server->getIniWrapper(),
				\OC::$server->getL10N('lib'), new \OC_Defaults(), \OC::$server->getLogger(),
				\OC::$server->getSecureRandom());
			$controller = new OC\Core\Setup\Controller($setupHelper);
			$controller->run($_POST);
			exit();
		}

		$request = \OC::$server->getRequest()->getPathInfo();
		if (substr($request, -3) !== '.js') { // we need these files during the upgrade
			self::checkMaintenanceMode();
			self::checkUpgrade();
		}

		// Always load authentication apps
		OC_App::loadApps(['authentication']);

		// Load minimum set of apps
		if (!self::checkUpgrade(false)
			&& !$systemConfig->getValue('maintenance', false)) {
			// For logged-in users: Load everything
			if(OC_User::isLoggedIn()) {
				OC_App::loadApps();
			} else {
				// For guests: Load only filesystem and logging
				OC_App::loadApps(array('filesystem', 'logging'));
				\OC_User::tryBasicAuthLogin();
			}
		}

		if (!self::$CLI and (!isset($_GET["logout"]) or ($_GET["logout"] !== 'true'))) {
			try {
				if (!$systemConfig->getValue('maintenance', false) && !self::checkUpgrade(false)) {
					OC_App::loadApps(array('filesystem', 'logging'));
					OC_App::loadApps();
				}
				self::checkSingleUserMode();
				OC_Util::setupFS();
				OC::$server->getRouter()->match(\OC::$server->getRequest()->getRawPathInfo());
				return;
			} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
				//header('HTTP/1.0 404 Not Found');
			} catch (Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
				OC_Response::setStatus(405);
				return;
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
			OC_Util::setupFS();
			if (isset($_GET["logout"]) and ($_GET["logout"])) {
				OC_JSON::callCheck();
				if (isset($_COOKIE['oc_token'])) {
					\OC::$server->getConfig()->deleteUserValue(OC_User::getUser(), 'login_token', $_COOKIE['oc_token']);
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

	protected static function handleAuthHeaders() {
		//copy http auth headers for apache+php-fcgid work around
		if (isset($_SERVER['HTTP_XAUTHORIZATION']) && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_XAUTHORIZATION'];
		}

		// Extract PHP_AUTH_USER/PHP_AUTH_PW from other headers if necessary.
		$vars = array(
			'HTTP_AUTHORIZATION', // apache+php-cgi work around
			'REDIRECT_HTTP_AUTHORIZATION', // apache+php-cgi alternative
		);
		foreach ($vars as $var) {
			if (isset($_SERVER[$var]) && preg_match('/Basic\s+(.*)$/i', $_SERVER[$var], $matches)) {
				list($name, $password) = explode(':', base64_decode($matches[1]), 2);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW'] = $password;
				break;
			}
		}
	}

	protected static function handleLogin() {
		OC_App::loadApps(array('prelogin'));
		$error = array();
		$messages = [];

		try {
			// auth possible via apache module?
			if (OC::tryApacheAuth()) {
				$error[] = 'apacheauthfailed';
			} // remember was checked after last login
			elseif (OC::tryRememberLogin()) {
				$error[] = 'invalidcookie';
			} // logon via web form
			elseif (OC::tryFormLogin()) {
				$error[] = 'invalidpassword';
			}
		} catch (\OC\User\LoginException $e) {
			$messages[] = $e->getMessage();
		} catch (\Exception $ex) {
			\OCP\Util::logException('handleLogin', $ex);
			// do not disclose information. show generic error
			$error[] = 'internalexception';
		}

		OC_Util::displayLoginPage(array_unique($error), $messages);
	}

	/**
	 * Remove outdated and therefore invalid tokens for a user
	 * @param string $user
	 */
	protected static function cleanupLoginTokens($user) {
		$config = \OC::$server->getConfig();
		$cutoff = time() - $config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$tokens = $config->getUserKeys($user, 'login_token');
		foreach ($tokens as $token) {
			$time = $config->getUserValue($user, 'login_token', $token);
			if ($time < $cutoff) {
				$config->deleteUserValue($user, 'login_token', $token);
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
			$_REQUEST['redirect_url'] = \OC::$server->getRequest()->getRequestUri();
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

		if (\OC::$server->getConfig()->getSystemValue('debug', false)) {
			\OCP\Util::writeLog('core', 'Trying to login from cookie', \OCP\Util::DEBUG);
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
			\OCP\Util::writeLog('core', 'Authentication cookie rejected for user ' .
				$_COOKIE['oc_username'], \OCP\Util::WARN);
			// if you reach this point you have changed your password
			// or you are an attacker
			// we can not delete tokens here because users may reach
			// this point multiple times after a password change
		}

		OC_User::unsetMagicInCookie();
		return true;
	}

	/**
	 * Tries to login a user using the form based authentication
	 * @return bool|void
	 */
	protected static function tryFormLogin() {
		if (!isset($_POST["user"]) || !isset($_POST['password'])) {
			return false;
		}

		if(!OC_Util::isCallRegistered()) {
			return false;
		}
		OC_App::loadApps();

		//setup extra user backends
		OC_User::setupBackends();

		if (OC_User::login((string)$_POST["user"], (string)$_POST["password"])) {
			$userId = OC_User::getUser();

			// setting up the time zone
			if (isset($_POST['timezone-offset'])) {
				self::$server->getSession()->set('timezone', (string)$_POST['timezone-offset']);
				self::$server->getConfig()->setUserValue($userId, 'core', 'timezone', (string)$_POST['timezone']);
			}

			self::cleanupLoginTokens($userId);
			if (!empty($_POST["remember_login"])) {
				$config = self::$server->getConfig();
				if ($config->getSystemValue('debug', false)) {
					self::$server->getLogger()->debug('Setting remember login to cookie', array('app' => 'core'));
				}
				$token = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(32);
				$config->setUserValue($userId, 'login_token', $token, time());
				OC_User::setMagicInCookie($userId, $token);
			} else {
				OC_User::unsetMagicInCookie();
			}
			OC_Util::redirectToDefaultPage();
			exit();
		}
		return true;
	}

}


OC::init();
