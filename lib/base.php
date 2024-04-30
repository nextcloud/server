<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Damjan Georgievski <gdamjan@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author davidgumberg <davidnoizgumberg@gmail.com>
 * @author Eric Masseran <rico.masseran@gmail.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Greta Doci <gretadoci@gmail.com>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author jaltek <jaltek@mailbox.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joachim Sokolowski <github@sokolowski.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Jose Quinteiro <github@quinteiro.org>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ko- <k.stoffelen@cs.ru.nl>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author MartB <mart.b@outlook.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Phil Davis <phil.davis@inf.org>
 * @author Ramiro Aparicio <rapariciog@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sebastian Wessalowski <sebastian@wessalowski.org>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Tobia De Koninck <tobia@ledfan.be>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Volkan Gezer <volkangezer@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Encryption\HookManager;
use OC\Share20\Hooks;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserRemovedEvent;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use OCP\Share;
use OCP\User\Events\UserChangedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use function OCP\Log\logger;

require_once 'public/Constants.php';

/**
 * Class that is a namespace for all global OC variables
 * No, we can not put this class in its own file because it is used by
 * OC_autoload!
 */
class OC {
	/**
	 * Associative array for autoloading. classname => filename
	 */
	public static array $CLASSPATH = [];
	/**
	 * The installation path for Nextcloud  on the server (e.g. /srv/http/nextcloud)
	 */
	public static string $SERVERROOT = '';
	/**
	 * the current request path relative to the Nextcloud root (e.g. files/index.php)
	 */
	private static string $SUBURI = '';
	/**
	 * the Nextcloud root path for http requests (e.g. nextcloud/)
	 */
	public static string $WEBROOT = '';
	/**
	 * The installation path array of the apps folder on the server (e.g. /srv/http/nextcloud) 'path' and
	 * web path in 'url'
	 */
	public static array $APPSROOTS = [];

	public static string $configDir;

	/**
	 * requested app
	 */
	public static string $REQUESTEDAPP = '';

	/**
	 * check if Nextcloud runs in cli mode
	 */
	public static bool $CLI = false;

	public static \OC\Autoloader $loader;

	public static \Composer\Autoload\ClassLoader $composerAutoloader;

	public static \OC\Server $server;

	private static \OC\Config $config;

	/**
	 * @throws \RuntimeException when the 3rdparty directory is missing or
	 * the app path list is empty or contains an invalid path
	 */
	public static function initPaths(): void {
		if (defined('PHPUNIT_CONFIG_DIR')) {
			self::$configDir = OC::$SERVERROOT . '/' . PHPUNIT_CONFIG_DIR . '/';
		} elseif (defined('PHPUNIT_RUN') and PHPUNIT_RUN and is_dir(OC::$SERVERROOT . '/tests/config/')) {
			self::$configDir = OC::$SERVERROOT . '/tests/config/';
		} elseif ($dir = getenv('NEXTCLOUD_CONFIG_DIR')) {
			self::$configDir = rtrim($dir, '/') . '/';
		} else {
			self::$configDir = OC::$SERVERROOT . '/config/';
		}
		self::$config = new \OC\Config(self::$configDir);

		OC::$SUBURI = str_replace("\\", "/", substr(realpath($_SERVER["SCRIPT_FILENAME"] ?? ''), strlen(OC::$SERVERROOT)));
		/**
		 * FIXME: The following lines are required because we can't yet instantiate
		 *        Server::get(\OCP\IRequest::class) since \OC::$server does not yet exist.
		 */
		$params = [
			'server' => [
				'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? null,
			],
		];
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$params['server']['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		}
		$fakeRequest = new \OC\AppFramework\Http\Request(
			$params,
			new \OC\AppFramework\Http\RequestId($_SERVER['UNIQUE_ID'] ?? '', new \OC\Security\SecureRandom()),
			new \OC\AllConfig(new \OC\SystemConfig(self::$config))
		);
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
			OC::$WEBROOT = self::$config->getValue('overwritewebroot', '');
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
				OC::$WEBROOT = self::$config->getValue('overwritewebroot', '');
			}

			// Resolve /nextcloud to /nextcloud/ to ensure to always have a trailing
			// slash which is required by URL generation.
			if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === \OC::$WEBROOT &&
					substr($_SERVER['REQUEST_URI'], -1) !== '/') {
				header('Location: '.\OC::$WEBROOT.'/');
				exit();
			}
		}

		// search the apps folder
		$config_paths = self::$config->getValue('apps_paths', []);
		if (!empty($config_paths)) {
			foreach ($config_paths as $paths) {
				if (isset($paths['url']) && isset($paths['path'])) {
					$paths['url'] = rtrim($paths['url'], '/');
					$paths['path'] = rtrim($paths['path'], '/');
					OC::$APPSROOTS[] = $paths;
				}
			}
		} elseif (file_exists(OC::$SERVERROOT . '/apps')) {
			OC::$APPSROOTS[] = ['path' => OC::$SERVERROOT . '/apps', 'url' => '/apps', 'writable' => true];
		}

		if (empty(OC::$APPSROOTS)) {
			throw new \RuntimeException('apps directory not found! Please put the Nextcloud apps folder in the Nextcloud folder'
				. '. You can also configure the location in the config.php file.');
		}
		$paths = [];
		foreach (OC::$APPSROOTS as $path) {
			$paths[] = $path['path'];
			if (!is_dir($path['path'])) {
				throw new \RuntimeException(sprintf('App directory "%s" not found! Please put the Nextcloud apps folder in the'
					. ' Nextcloud folder. You can also configure the location in the config.php file.', $path['path']));
			}
		}

		// set the right include path
		set_include_path(
			implode(PATH_SEPARATOR, $paths)
		);
	}

	public static function checkConfig(): void {
		$l = Server::get(\OCP\L10N\IFactory::class)->get('lib');

		// Create config if it does not already exist
		$configFilePath = self::$configDir .'/config.php';
		if (!file_exists($configFilePath)) {
			@touch($configFilePath);
		}

		// Check if config is writable
		$configFileWritable = is_writable($configFilePath);
		if (!$configFileWritable && !OC_Helper::isReadOnlyConfigEnabled()
			|| !$configFileWritable && \OCP\Util::needUpgrade()) {
			$urlGenerator = Server::get(IURLGenerator::class);

			if (self::$CLI) {
				echo $l->t('Cannot write into "config" directory!')."\n";
				echo $l->t('This can usually be fixed by giving the web server write access to the config directory.')."\n";
				echo "\n";
				echo $l->t('But, if you prefer to keep config.php file read only, set the option "config_is_read_only" to true in it.')."\n";
				echo $l->t('See %s', [ $urlGenerator->linkToDocs('admin-config') ])."\n";
				exit;
			} else {
				OC_Template::printErrorPage(
					$l->t('Cannot write into "config" directory!'),
					$l->t('This can usually be fixed by giving the web server write access to the config directory.') . ' '
					. $l->t('But, if you prefer to keep config.php file read only, set the option "config_is_read_only" to true in it.') . ' '
					. $l->t('See %s', [ $urlGenerator->linkToDocs('admin-config') ]),
					503
				);
			}
		}
	}

	public static function checkInstalled(\OC\SystemConfig $systemConfig): void {
		if (defined('OC_CONSOLE')) {
			return;
		}
		// Redirect to installer if not installed
		if (!$systemConfig->getValue('installed', false) && OC::$SUBURI !== '/index.php' && OC::$SUBURI !== '/status.php') {
			if (OC::$CLI) {
				throw new Exception('Not installed');
			} else {
				$url = OC::$WEBROOT . '/index.php';
				header('Location: ' . $url);
			}
			exit();
		}
	}

	public static function checkMaintenanceMode(\OC\SystemConfig $systemConfig): void {
		// Allow ajax update script to execute without being stopped
		if (((bool) $systemConfig->getValue('maintenance', false)) && OC::$SUBURI != '/core/ajax/update.php') {
			// send http status 503
			http_response_code(503);
			header('X-Nextcloud-Maintenance-Mode: 1');
			header('Retry-After: 120');

			// render error page
			$template = new OC_Template('', 'update.user', 'guest');
			\OCP\Util::addScript('core', 'maintenance');
			\OCP\Util::addStyle('core', 'guest');
			$template->printPage();
			die();
		}
	}

	/**
	 * Prints the upgrade page
	 */
	private static function printUpgradePage(\OC\SystemConfig $systemConfig): void {
		$cliUpgradeLink = $systemConfig->getValue('upgrade.cli-upgrade-link', '');
		$disableWebUpdater = $systemConfig->getValue('upgrade.disable-web', false);
		$tooBig = false;
		if (!$disableWebUpdater) {
			$apps = Server::get(\OCP\App\IAppManager::class);
			if ($apps->isInstalled('user_ldap')) {
				$qb = Server::get(\OCP\IDBConnection::class)->getQueryBuilder();

				$result = $qb->select($qb->func()->count('*', 'user_count'))
					->from('ldap_user_mapping')
					->executeQuery();
				$row = $result->fetch();
				$result->closeCursor();

				$tooBig = ($row['user_count'] > 50);
			}
			if (!$tooBig && $apps->isInstalled('user_saml')) {
				$qb = Server::get(\OCP\IDBConnection::class)->getQueryBuilder();

				$result = $qb->select($qb->func()->count('*', 'user_count'))
					->from('user_saml_users')
					->executeQuery();
				$row = $result->fetch();
				$result->closeCursor();

				$tooBig = ($row['user_count'] > 50);
			}
			if (!$tooBig) {
				// count users
				$stats = Server::get(\OCP\IUserManager::class)->countUsers();
				$totalUsers = array_sum($stats);
				$tooBig = ($totalUsers > 50);
			}
		}
		$ignoreTooBigWarning = isset($_GET['IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup']) &&
			$_GET['IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup'] === 'IAmSuperSureToDoThis';

		if ($disableWebUpdater || ($tooBig && !$ignoreTooBigWarning)) {
			// send http status 503
			http_response_code(503);
			header('Retry-After: 120');

			// render error page
			$template = new OC_Template('', 'update.use-cli', 'guest');
			$template->assign('productName', 'nextcloud'); // for now
			$template->assign('version', OC_Util::getVersionString());
			$template->assign('tooBig', $tooBig);
			$template->assign('cliUpgradeLink', $cliUpgradeLink);

			$template->printPage();
			die();
		}

		// check whether this is a core update or apps update
		$installedVersion = $systemConfig->getValue('version', '0.0.0');
		$currentVersion = implode('.', \OCP\Util::getVersion());

		// if not a core upgrade, then it's apps upgrade
		$isAppsOnlyUpgrade = version_compare($currentVersion, $installedVersion, '=');

		$oldTheme = $systemConfig->getValue('theme');
		$systemConfig->setValue('theme', '');
		\OCP\Util::addScript('core', 'common');
		\OCP\Util::addScript('core', 'main');
		\OCP\Util::addTranslations('core');
		\OCP\Util::addScript('core', 'update');

		/** @var \OC\App\AppManager $appManager */
		$appManager = Server::get(\OCP\App\IAppManager::class);

		$tmpl = new OC_Template('', 'update.admin', 'guest');
		$tmpl->assign('version', OC_Util::getVersionString());
		$tmpl->assign('isAppsOnlyUpgrade', $isAppsOnlyUpgrade);

		// get third party apps
		$ocVersion = \OCP\Util::getVersion();
		$ocVersion = implode('.', $ocVersion);
		$incompatibleApps = $appManager->getIncompatibleApps($ocVersion);
		$incompatibleShippedApps = [];
		foreach ($incompatibleApps as $appInfo) {
			if ($appManager->isShipped($appInfo['id'])) {
				$incompatibleShippedApps[] = $appInfo['name'] . ' (' . $appInfo['id'] . ')';
			}
		}

		if (!empty($incompatibleShippedApps)) {
			$l = Server::get(\OCP\L10N\IFactory::class)->get('core');
			$hint = $l->t('Application %1$s is not present or has a non-compatible version with this server. Please check the apps directory.', [implode(', ', $incompatibleShippedApps)]);
			throw new \OCP\HintException('Application ' . implode(', ', $incompatibleShippedApps) . ' is not present or has a non-compatible version with this server. Please check the apps directory.', $hint);
		}

		$tmpl->assign('appsToUpgrade', $appManager->getAppsNeedingUpgrade($ocVersion));
		$tmpl->assign('incompatibleAppsList', $incompatibleApps);
		try {
			$defaults = new \OC_Defaults();
			$tmpl->assign('productName', $defaults->getName());
		} catch (Throwable $error) {
			$tmpl->assign('productName', 'Nextcloud');
		}
		$tmpl->assign('oldTheme', $oldTheme);
		$tmpl->printPage();
	}

	public static function initSession(): void {
		$request = Server::get(IRequest::class);

		// TODO: Temporary disabled again to solve issues with CalDAV/CardDAV clients like DAVx5 that use cookies
		// TODO: See https://github.com/nextcloud/server/issues/37277#issuecomment-1476366147 and the other comments
		// TODO: for further information.
		// $isDavRequest = strpos($request->getRequestUri(), '/remote.php/dav') === 0 || strpos($request->getRequestUri(), '/remote.php/webdav') === 0;
		// if ($request->getHeader('Authorization') !== '' && is_null($request->getCookie('cookie_test')) && $isDavRequest && !isset($_COOKIE['nc_session_id'])) {
		// setcookie('cookie_test', 'test', time() + 3600);
		// // Do not initialize the session if a request is authenticated directly
		// // unless there is a session cookie already sent along
		// return;
		// }

		if ($request->getServerProtocol() === 'https') {
			ini_set('session.cookie_secure', 'true');
		}

		// prevents javascript from accessing php session cookies
		ini_set('session.cookie_httponly', 'true');

		// set the cookie path to the Nextcloud directory
		$cookie_path = OC::$WEBROOT ? : '/';
		ini_set('session.cookie_path', $cookie_path);

		// Let the session name be changed in the initSession Hook
		$sessionName = OC_Util::getInstanceId();

		try {
			// set the session name to the instance id - which is unique
			$session = new \OC\Session\Internal($sessionName);

			$cryptoWrapper = Server::get(\OC\Session\CryptoWrapper::class);
			$session = $cryptoWrapper->wrapSession($session);
			self::$server->setSession($session);

			// if session can't be started break with http 500 error
		} catch (Exception $e) {
			Server::get(LoggerInterface::class)->error($e->getMessage(), ['app' => 'base','exception' => $e]);
			//show the user a detailed error page
			OC_Template::printExceptionErrorPage($e, 500);
			die();
		}

		//try to set the session lifetime
		$sessionLifeTime = self::getSessionLifeTime();
		@ini_set('gc_maxlifetime', (string)$sessionLifeTime);

		// session timeout
		if ($session->exists('LAST_ACTIVITY') && (time() - $session->get('LAST_ACTIVITY') > $sessionLifeTime)) {
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', -1, self::$WEBROOT ? : '/');
			}
			Server::get(IUserSession::class)->logout();
		}

		if (!self::hasSessionRelaxedExpiry()) {
			$session->set('LAST_ACTIVITY', time());
		}
		$session->close();
	}

	private static function getSessionLifeTime(): int {
		return Server::get(\OC\AllConfig::class)->getSystemValueInt('session_lifetime', 60 * 60 * 24);
	}

	/**
	 * @return bool true if the session expiry should only be done by gc instead of an explicit timeout
	 */
	public static function hasSessionRelaxedExpiry(): bool {
		return Server::get(\OC\AllConfig::class)->getSystemValueBool('session_relaxed_expiry', false);
	}

	/**
	 * Try to set some values to the required Nextcloud default
	 */
	public static function setRequiredIniValues(): void {
		@ini_set('default_charset', 'UTF-8');
		@ini_set('gd.jpeg_ignore_warning', '1');
	}

	/**
	 * Send the same site cookies
	 */
	private static function sendSameSiteCookies(): void {
		$cookieParams = session_get_cookie_params();
		$secureCookie = ($cookieParams['secure'] === true) ? 'secure; ' : '';
		$policies = [
			'lax',
			'strict',
		];

		// Append __Host to the cookie if it meets the requirements
		$cookiePrefix = '';
		if ($cookieParams['secure'] === true && $cookieParams['path'] === '/') {
			$cookiePrefix = '__Host-';
		}

		foreach ($policies as $policy) {
			header(
				sprintf(
					'Set-Cookie: %snc_sameSiteCookie%s=true; path=%s; httponly;' . $secureCookie . 'expires=Fri, 31-Dec-2100 23:59:59 GMT; SameSite=%s',
					$cookiePrefix,
					$policy,
					$cookieParams['path'],
					$policy
				),
				false
			);
		}
	}

	/**
	 * Same Site cookie to further mitigate CSRF attacks. This cookie has to
	 * be set in every request if cookies are sent to add a second level of
	 * defense against CSRF.
	 *
	 * If the cookie is not sent this will set the cookie and reload the page.
	 * We use an additional cookie since we want to protect logout CSRF and
	 * also we can't directly interfere with PHP's session mechanism.
	 */
	private static function performSameSiteCookieProtection(\OCP\IConfig $config): void {
		$request = Server::get(IRequest::class);

		// Some user agents are notorious and don't really properly follow HTTP
		// specifications. For those, have an automated opt-out. Since the protection
		// for remote.php is applied in base.php as starting point we need to opt out
		// here.
		$incompatibleUserAgents = $config->getSystemValue('csrf.optout');

		// Fallback, if csrf.optout is unset
		if (!is_array($incompatibleUserAgents)) {
			$incompatibleUserAgents = [
				// OS X Finder
				'/^WebDAVFS/',
				// Windows webdav drive
				'/^Microsoft-WebDAV-MiniRedir/',
			];
		}

		if ($request->isUserAgent($incompatibleUserAgents)) {
			return;
		}

		if (count($_COOKIE) > 0) {
			$requestUri = $request->getScriptName();
			$processingScript = explode('/', $requestUri);
			$processingScript = $processingScript[count($processingScript) - 1];

			// index.php routes are handled in the middleware
			if ($processingScript === 'index.php') {
				return;
			}

			// All other endpoints require the lax and the strict cookie
			if (!$request->passesStrictCookieCheck()) {
				logger('core')->warning('Request does not pass strict cookie check');
				self::sendSameSiteCookies();
				// Debug mode gets access to the resources without strict cookie
				// due to the fact that the SabreDAV browser also lives there.
				if (!$config->getSystemValueBool('debug', false)) {
					http_response_code(\OCP\AppFramework\Http::STATUS_PRECONDITION_FAILED);
					header('Content-Type: application/json');
					echo json_encode(['error' => 'Strict Cookie has not been found in request']);
					exit();
				}
			}
		} elseif (!isset($_COOKIE['nc_sameSiteCookielax']) || !isset($_COOKIE['nc_sameSiteCookiestrict'])) {
			self::sendSameSiteCookies();
		}
	}

	public static function init(): void {
		// prevent any XML processing from loading external entities
		libxml_set_external_entity_loader(static function () {
			return null;
		});

		// calculate the root directories
		OC::$SERVERROOT = str_replace("\\", '/', substr(__DIR__, 0, -4));

		// register autoloader
		$loaderStart = microtime(true);
		require_once __DIR__ . '/autoloader.php';
		self::$loader = new \OC\Autoloader([
			OC::$SERVERROOT . '/lib/private/legacy',
		]);
		if (defined('PHPUNIT_RUN')) {
			self::$loader->addValidRoot(OC::$SERVERROOT . '/tests');
		}
		spl_autoload_register([self::$loader, 'load']);
		$loaderEnd = microtime(true);

		self::$CLI = (php_sapi_name() == 'cli');

		// Add default composer PSR-4 autoloader, ensure apcu to be disabled
		self::$composerAutoloader = require_once OC::$SERVERROOT . '/lib/composer/autoload.php';
		self::$composerAutoloader->setApcuPrefix(null);

		try {
			self::initPaths();
			// setup 3rdparty autoloader
			$vendorAutoLoad = OC::$SERVERROOT. '/3rdparty/autoload.php';
			if (!file_exists($vendorAutoLoad)) {
				throw new \RuntimeException('Composer autoloader not found, unable to continue. Check the folder "3rdparty". Running "git submodule update --init" will initialize the git submodule that handles the subfolder "3rdparty".');
			}
			require_once $vendorAutoLoad;
		} catch (\RuntimeException $e) {
			if (!self::$CLI) {
				http_response_code(503);
			}
			// we can't use the template error page here, because this needs the
			// DI container which isn't available yet
			print($e->getMessage());
			exit();
		}

		// setup the basic server
		self::$server = new \OC\Server(\OC::$WEBROOT, self::$config);
		self::$server->boot();

		$eventLogger = Server::get(\OCP\Diagnostics\IEventLogger::class);
		$eventLogger->log('autoloader', 'Autoloader', $loaderStart, $loaderEnd);
		$eventLogger->start('boot', 'Initialize');

		// Override php.ini and log everything if we're troubleshooting
		if (self::$config->getValue('loglevel') === ILogger::DEBUG) {
			error_reporting(E_ALL);
		}

		// Don't display errors and log them
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '1');

		if (!date_default_timezone_set('UTC')) {
			throw new \RuntimeException('Could not set timezone to UTC');
		}


		//try to configure php to enable big file uploads.
		//this doesn´t work always depending on the webserver and php configuration.
		//Let´s try to overwrite some defaults if they are smaller than 1 hour

		if (intval(@ini_get('max_execution_time') ?: 0) < 3600) {
			@ini_set('max_execution_time', strval(3600));
		}

		if (intval(@ini_get('max_input_time') ?: 0) < 3600) {
			@ini_set('max_input_time', strval(3600));
		}

		//try to set the maximum execution time to the largest time limit we have
		if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
			@set_time_limit(max(intval(@ini_get('max_execution_time')), intval(@ini_get('max_input_time'))));
		}

		self::setRequiredIniValues();
		self::handleAuthHeaders();
		$systemConfig = Server::get(\OC\SystemConfig::class);
		self::registerAutoloaderCache($systemConfig);

		// initialize intl fallback if necessary
		OC_Util::isSetLocaleWorking();

		$config = Server::get(\OCP\IConfig::class);
		if (!defined('PHPUNIT_RUN')) {
			$errorHandler = new OC\Log\ErrorHandler(
				\OCP\Server::get(\Psr\Log\LoggerInterface::class),
			);
			$exceptionHandler = [$errorHandler, 'onException'];
			if ($config->getSystemValueBool('debug', false)) {
				set_error_handler([$errorHandler, 'onAll'], E_ALL);
				if (\OC::$CLI) {
					$exceptionHandler = ['OC_Template', 'printExceptionErrorPage'];
				}
			} else {
				set_error_handler([$errorHandler, 'onError']);
			}
			register_shutdown_function([$errorHandler, 'onShutdown']);
			set_exception_handler($exceptionHandler);
		}

		/** @var \OC\AppFramework\Bootstrap\Coordinator $bootstrapCoordinator */
		$bootstrapCoordinator = Server::get(\OC\AppFramework\Bootstrap\Coordinator::class);
		$bootstrapCoordinator->runInitialRegistration();

		$eventLogger->start('init_session', 'Initialize session');
		OC_App::loadApps(['session']);
		if (!self::$CLI) {
			self::initSession();
		}
		$eventLogger->end('init_session');
		self::checkConfig();
		self::checkInstalled($systemConfig);

		OC_Response::addSecurityHeaders();

		self::performSameSiteCookieProtection($config);

		if (!defined('OC_CONSOLE')) {
			$errors = OC_Util::checkServer($systemConfig);
			if (count($errors) > 0) {
				if (!self::$CLI) {
					http_response_code(503);
					OC_Util::addStyle('guest');
					try {
						OC_Template::printGuestPage('', 'error', ['errors' => $errors]);
						exit;
					} catch (\Exception $e) {
						// In case any error happens when showing the error page, we simply fall back to posting the text.
						// This might be the case when e.g. the data directory is broken and we can not load/write SCSS to/from it.
					}
				}

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
					$config->setAppValue('core', 'cronErrors', json_encode($staticErrors));
				} catch (\Exception $e) {
					echo('Writing to database failed');
				}
				exit(1);
			} elseif (self::$CLI && $config->getSystemValueBool('installed', false)) {
				$config->deleteAppValue('core', 'cronErrors');
			}
		}

		// User and Groups
		if (!$systemConfig->getValue("installed", false)) {
			self::$server->getSession()->set('user_id', '');
		}

		OC_User::useBackend(new \OC\User\Database());
		Server::get(\OCP\IGroupManager::class)->addBackend(new \OC\Group\Database());

		// Subscribe to the hook
		\OCP\Util::connectHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			'\OC\User\Database',
			'preLoginNameUsedAsUserName'
		);

		//setup extra user backends
		if (!\OCP\Util::needUpgrade()) {
			OC_User::setupBackends();
		} else {
			// Run upgrades in incognito mode
			OC_User::setIncognitoMode(true);
		}

		self::registerCleanupHooks($systemConfig);
		self::registerShareHooks($systemConfig);
		self::registerEncryptionWrapperAndHooks();
		self::registerAccountHooks();
		self::registerResourceCollectionHooks();
		self::registerFileReferenceEventListener();
		self::registerRenderReferenceEventListener();
		self::registerAppRestrictionsHooks();

		// Make sure that the application class is not loaded before the database is setup
		if ($systemConfig->getValue("installed", false)) {
			OC_App::loadApp('settings');
			/* Build core application to make sure that listeners are registered */
			Server::get(\OC\Core\Application::class);
		}

		//make sure temporary files are cleaned up
		$tmpManager = Server::get(\OCP\ITempManager::class);
		register_shutdown_function([$tmpManager, 'clean']);
		$lockProvider = Server::get(\OCP\Lock\ILockingProvider::class);
		register_shutdown_function([$lockProvider, 'releaseAll']);

		// Check whether the sample configuration has been copied
		if ($systemConfig->getValue('copied_sample_config', false)) {
			$l = Server::get(\OCP\L10N\IFactory::class)->get('lib');
			OC_Template::printErrorPage(
				$l->t('Sample configuration detected'),
				$l->t('It has been detected that the sample configuration has been copied. This can break your installation and is unsupported. Please read the documentation before performing changes on config.php'),
				503
			);
			return;
		}

		$request = Server::get(IRequest::class);
		$host = $request->getInsecureServerHost();
		/**
		 * if the host passed in headers isn't trusted
		 * FIXME: Should not be in here at all :see_no_evil:
		 */
		if (!OC::$CLI
			&& !Server::get(\OC\Security\TrustedDomainHelper::class)->isTrustedDomain($host)
			&& $config->getSystemValueBool('installed', false)
		) {
			// Allow access to CSS resources
			$isScssRequest = false;
			if (strpos($request->getPathInfo() ?: '', '/css/') === 0) {
				$isScssRequest = true;
			}

			if (substr($request->getRequestUri(), -11) === '/status.php') {
				http_response_code(400);
				header('Content-Type: application/json');
				echo '{"error": "Trusted domain error.", "code": 15}';
				exit();
			}

			if (!$isScssRequest) {
				http_response_code(400);
				Server::get(LoggerInterface::class)->info(
					'Trusted domain error. "{remoteAddress}" tried to access using "{host}" as host.',
					[
						'app' => 'core',
						'remoteAddress' => $request->getRemoteAddress(),
						'host' => $host,
					]
				);

				$tmpl = new OCP\Template('core', 'untrustedDomain', 'guest');
				$tmpl->assign('docUrl', Server::get(IURLGenerator::class)->linkToDocs('admin-trusted-domains'));
				$tmpl->printPage();

				exit();
			}
		}
		$eventLogger->end('boot');
		$eventLogger->log('init', 'OC::init', $loaderStart, microtime(true));
		$eventLogger->start('runtime', 'Runtime');
		$eventLogger->start('request', 'Full request after boot');
		register_shutdown_function(function () use ($eventLogger) {
			$eventLogger->end('request');
		});
	}

	/**
	 * register hooks for the cleanup of cache and bruteforce protection
	 */
	public static function registerCleanupHooks(\OC\SystemConfig $systemConfig): void {
		//don't try to do this before we are properly setup
		if ($systemConfig->getValue('installed', false) && !\OCP\Util::needUpgrade()) {
			// NOTE: This will be replaced to use OCP
			$userSession = Server::get(\OC\User\Session::class);
			$userSession->listen('\OC\User', 'postLogin', function () use ($userSession) {
				if (!defined('PHPUNIT_RUN') && $userSession->isLoggedIn()) {
					// reset brute force delay for this IP address and username
					$uid = $userSession->getUser()->getUID();
					$request = Server::get(IRequest::class);
					$throttler = Server::get(IThrottler::class);
					$throttler->resetDelay($request->getRemoteAddress(), 'login', ['user' => $uid]);
				}

				try {
					$cache = new \OC\Cache\File();
					$cache->gc();
				} catch (\OC\ServerNotAvailableException $e) {
					// not a GC exception, pass it on
					throw $e;
				} catch (\OC\ForbiddenException $e) {
					// filesystem blocked for this request, ignore
				} catch (\Exception $e) {
					// a GC exception should not prevent users from using OC,
					// so log the exception
					Server::get(LoggerInterface::class)->warning('Exception when running cache gc.', [
						'app' => 'core',
						'exception' => $e,
					]);
				}
			});
		}
	}

	private static function registerEncryptionWrapperAndHooks(): void {
		$manager = Server::get(\OCP\Encryption\IManager::class);
		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', $manager, 'setupStorage');

		$enabled = $manager->isEnabled();
		if ($enabled) {
			\OCP\Util::connectHook(Share::class, 'post_shared', HookManager::class, 'postShared');
			\OCP\Util::connectHook(Share::class, 'post_unshare', HookManager::class, 'postUnshared');
			\OCP\Util::connectHook('OC_Filesystem', 'post_rename', HookManager::class, 'postRename');
			\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', HookManager::class, 'postRestore');
		}
	}

	private static function registerAccountHooks(): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = Server::get(IEventDispatcher::class);
		$dispatcher->addServiceListener(UserChangedEvent::class, \OC\Accounts\Hooks::class);
	}

	private static function registerAppRestrictionsHooks(): void {
		/** @var \OC\Group\Manager $groupManager */
		$groupManager = Server::get(\OCP\IGroupManager::class);
		$groupManager->listen('\OC\Group', 'postDelete', function (\OCP\IGroup $group) {
			$appManager = Server::get(\OCP\App\IAppManager::class);
			$apps = $appManager->getEnabledAppsForGroup($group);
			foreach ($apps as $appId) {
				$restrictions = $appManager->getAppRestriction($appId);
				if (empty($restrictions)) {
					continue;
				}
				$key = array_search($group->getGID(), $restrictions);
				unset($restrictions[$key]);
				$restrictions = array_values($restrictions);
				if (empty($restrictions)) {
					$appManager->disableApp($appId);
				} else {
					$appManager->enableAppForGroups($appId, $restrictions);
				}
			}
		});
	}

	private static function registerResourceCollectionHooks(): void {
		\OC\Collaboration\Resources\Listener::register(Server::get(IEventDispatcher::class));
	}

	private static function registerFileReferenceEventListener(): void {
		\OC\Collaboration\Reference\File\FileReferenceEventListener::register(Server::get(IEventDispatcher::class));
	}

	private static function registerRenderReferenceEventListener() {
		\OC\Collaboration\Reference\RenderReferenceEventListener::register(Server::get(IEventDispatcher::class));
	}

	/**
	 * register hooks for sharing
	 */
	public static function registerShareHooks(\OC\SystemConfig $systemConfig): void {
		if ($systemConfig->getValue('installed')) {
			OC_Hook::connect('OC_User', 'post_deleteUser', Hooks::class, 'post_deleteUser');
			OC_Hook::connect('OC_User', 'post_deleteGroup', Hooks::class, 'post_deleteGroup');

			/** @var IEventDispatcher $dispatcher */
			$dispatcher = Server::get(IEventDispatcher::class);
			$dispatcher->addServiceListener(UserRemovedEvent::class, \OC\Share20\UserRemovedListener::class);
		}
	}

	protected static function registerAutoloaderCache(\OC\SystemConfig $systemConfig): void {
		// The class loader takes an optional low-latency cache, which MUST be
		// namespaced. The instanceid is used for namespacing, but might be
		// unavailable at this point. Furthermore, it might not be possible to
		// generate an instanceid via \OC_Util::getInstanceId() because the
		// config file may not be writable. As such, we only register a class
		// loader cache if instanceid is available without trying to create one.
		$instanceId = $systemConfig->getValue('instanceid', null);
		if ($instanceId) {
			try {
				$memcacheFactory = Server::get(\OCP\ICacheFactory::class);
				self::$loader->setMemoryCache($memcacheFactory->createLocal('Autoloader'));
			} catch (\Exception $ex) {
			}
		}
	}

	/**
	 * Handle the request
	 */
	public static function handleRequest(): void {
		Server::get(\OCP\Diagnostics\IEventLogger::class)->start('handle_request', 'Handle request');
		$systemConfig = Server::get(\OC\SystemConfig::class);

		// Check if Nextcloud is installed or in maintenance (update) mode
		if (!$systemConfig->getValue('installed', false)) {
			\OC::$server->getSession()->clear();
			$logger = Server::get(\Psr\Log\LoggerInterface::class);
			$setupHelper = new OC\Setup(
				$systemConfig,
				Server::get(\bantu\IniGetWrapper\IniGetWrapper::class),
				Server::get(\OCP\L10N\IFactory::class)->get('lib'),
				Server::get(\OCP\Defaults::class),
				$logger,
				Server::get(\OCP\Security\ISecureRandom::class),
				Server::get(\OC\Installer::class)
			);
			$controller = new OC\Core\Controller\SetupController($setupHelper, $logger);
			$controller->run($_POST);
			exit();
		}

		$request = Server::get(IRequest::class);
		$requestPath = $request->getRawPathInfo();
		if ($requestPath === '/heartbeat') {
			return;
		}
		if (substr($requestPath, -3) !== '.js') { // we need these files during the upgrade
			self::checkMaintenanceMode($systemConfig);

			if (\OCP\Util::needUpgrade()) {
				if (function_exists('opcache_reset')) {
					opcache_reset();
				}
				if (!((bool) $systemConfig->getValue('maintenance', false))) {
					self::printUpgradePage($systemConfig);
					exit();
				}
			}
		}

		// emergency app disabling
		if ($requestPath === '/disableapp'
			&& $request->getMethod() === 'POST'
		) {
			\OC_JSON::callCheck();
			\OC_JSON::checkAdminUser();
			$appIds = (array)$request->getParam('appid');
			foreach ($appIds as $appId) {
				$appId = \OC_App::cleanAppId($appId);
				Server::get(\OCP\App\IAppManager::class)->disableApp($appId);
			}
			\OC_JSON::success();
			exit();
		}

		// Always load authentication apps
		OC_App::loadApps(['authentication']);
		OC_App::loadApps(['extended_authentication']);

		// Load minimum set of apps
		if (!\OCP\Util::needUpgrade()
			&& !((bool) $systemConfig->getValue('maintenance', false))) {
			// For logged-in users: Load everything
			if (Server::get(IUserSession::class)->isLoggedIn()) {
				OC_App::loadApps();
			} else {
				// For guests: Load only filesystem and logging
				OC_App::loadApps(['filesystem', 'logging']);

				// Don't try to login when a client is trying to get a OAuth token.
				// OAuth needs to support basic auth too, so the login is not valid
				// inside Nextcloud and the Login exception would ruin it.
				if ($request->getRawPathInfo() !== '/apps/oauth2/api/v1/token') {
					self::handleLogin($request);
				}
			}
		}

		if (!self::$CLI) {
			try {
				if (!((bool) $systemConfig->getValue('maintenance', false)) && !\OCP\Util::needUpgrade()) {
					OC_App::loadApps(['filesystem', 'logging']);
					OC_App::loadApps();
				}
				Server::get(\OC\Route\Router::class)->match($request->getRawPathInfo());
				return;
			} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
				//header('HTTP/1.0 404 Not Found');
			} catch (Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
				http_response_code(405);
				return;
			}
		}

		// Handle WebDAV
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PROPFIND') {
			// not allowed any more to prevent people
			// mounting this root directly.
			// Users need to mount remote.php/webdav instead.
			http_response_code(405);
			return;
		}

		// Handle requests for JSON or XML
		$acceptHeader = $request->getHeader('Accept');
		if (in_array($acceptHeader, ['application/json', 'application/xml'], true)) {
			http_response_code(404);
			return;
		}

		// Handle resources that can't be found
		// This prevents browsers from redirecting to the default page and then
		// attempting to parse HTML as CSS and similar.
		$destinationHeader = $request->getHeader('Sec-Fetch-Dest');
		if (in_array($destinationHeader, ['font', 'script', 'style'])) {
			http_response_code(404);
			return;
		}

		// Redirect to the default app or login only as an entry point
		if ($requestPath === '') {
			// Someone is logged in
			if (Server::get(IUserSession::class)->isLoggedIn()) {
				header('Location: ' . Server::get(IURLGenerator::class)->linkToDefaultPageUrl());
			} else {
				// Not handled and not logged in
				header('Location: ' . Server::get(IURLGenerator::class)->linkToRouteAbsolute('core.login.showLoginForm'));
			}
			return;
		}

		try {
			Server::get(\OC\Route\Router::class)->match('/error/404');
		} catch (\Exception $e) {
			if (!$e instanceof MethodNotAllowedException) {
				logger('core')->emergency($e->getMessage(), ['exception' => $e]);
			}
			$l = Server::get(\OCP\L10N\IFactory::class)->get('lib');
			OC_Template::printErrorPage(
				'404',
				$l->t('The page could not be found on the server.'),
				404
			);
		}
	}

	/**
	 * Check login: apache auth, auth token, basic auth
	 */
	public static function handleLogin(OCP\IRequest $request): bool {
		if ($request->getHeader('X-Nextcloud-Federation')) {
			return false;
		}
		$userSession = Server::get(\OC\User\Session::class);
		if (OC_User::handleApacheAuth()) {
			return true;
		}
		if (self::tryAppAPILogin($request)) {
			return true;
		}
		if ($userSession->tryTokenLogin($request)) {
			return true;
		}
		if (isset($_COOKIE['nc_username'])
			&& isset($_COOKIE['nc_token'])
			&& isset($_COOKIE['nc_session_id'])
			&& $userSession->loginWithCookie($_COOKIE['nc_username'], $_COOKIE['nc_token'], $_COOKIE['nc_session_id'])) {
			return true;
		}
		if ($userSession->tryBasicAuthLogin($request, Server::get(IThrottler::class))) {
			return true;
		}
		return false;
	}

	protected static function handleAuthHeaders(): void {
		//copy http auth headers for apache+php-fcgid work around
		if (isset($_SERVER['HTTP_XAUTHORIZATION']) && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_XAUTHORIZATION'];
		}

		// Extract PHP_AUTH_USER/PHP_AUTH_PW from other headers if necessary.
		$vars = [
			'HTTP_AUTHORIZATION', // apache+php-cgi work around
			'REDIRECT_HTTP_AUTHORIZATION', // apache+php-cgi alternative
		];
		foreach ($vars as $var) {
			if (isset($_SERVER[$var]) && is_string($_SERVER[$var]) && preg_match('/Basic\s+(.*)$/i', $_SERVER[$var], $matches)) {
				$credentials = explode(':', base64_decode($matches[1]), 2);
				if (count($credentials) === 2) {
					$_SERVER['PHP_AUTH_USER'] = $credentials[0];
					$_SERVER['PHP_AUTH_PW'] = $credentials[1];
					break;
				}
			}
		}
	}

	protected static function tryAppAPILogin(OCP\IRequest $request): bool {
		$appManager = Server::get(OCP\App\IAppManager::class);
		if (!$request->getHeader('AUTHORIZATION-APP-API')) {
			return false;
		}
		if (!$appManager->isInstalled('app_api')) {
			return false;
		}
		try {
			$appAPIService = Server::get(OCA\AppAPI\Service\AppAPIService::class);
			return $appAPIService->validateExAppRequestToNC($request);
		} catch (\Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface $e) {
			return false;
		}
	}
}

OC::init();
