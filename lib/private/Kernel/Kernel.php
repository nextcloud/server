<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use Composer\Autoload\ClassLoader;
use OC\AllConfig;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\RequestId;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Config;
use OC\Core\Listener\BeforeMessageLoggedEventListener;
use OC\Log\ErrorHandler;
use OC\Profiler\BuiltInProfiler;
use OC\Security\SecureRandom;
use OC\Server;
use OC\Share20\GroupDeletedListener;
use OC\Share20\UserDeletedListener;
use OC\Share20\UserRemovedListener;
use OC\SystemConfig;
use OC_User;
use OC_Util;
use OCP\App\IAppManager;
use OCP\Console\ReservedOptions;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\HintException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Template\ITemplateManager;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use function OCP\Log\logger;

require_once __DIR__ . '/../../base.php';

abstract class Kernel {
	protected ClassLoader $composerAutoloader;
	protected Config $config;
	protected SystemConfig $systemConfig;
	protected IEventLogger $eventLogger;
	protected string $serverRoot;
	protected string $webRoot;
	protected string $configDir;
	protected string $subUri;
	protected array $appsRoots = [];
	public Server $server;
	static Kernel $kernel;

	public function __construct(
	) {
		$this->serverRoot = str_replace('\\', '/', substr(__DIR__, 0, -18));
		\OC::$SERVERROOT = $this->serverRoot;
		if (isset(self::$kernel)) {
			throw new RuntimeException("Kernel is already initialized");
		}
		self::$kernel = $this;
	}

	public static function getInstance(): self {
		return self::$kernel;
	}

	public function getServer(): Server {
		return $this->server;
	}

	public function boot(): self {
		$this->setRequiredIniValues();

		$this->setupPhpDefault();

		[$loaderStart, $loaderEnd] = $this->setupAutoloader();

		$this->setupServerContainer();

		$this->eventLogger = $this->server->get(IEventLogger::class);
		$this->eventLogger->log('autoloader', 'Autoloader', $loaderStart, $loaderEnd);
		$this->eventLogger->start('boot', 'Initialize');

		$this->setupLogging();

		// initialize intl fallback if necessary
		OC_Util::isSetLocaleWorking();

		$this->setupErrorHandler();

		/** @var Coordinator $bootstrapCoordinator */
		$bootstrapCoordinator = $this->server->get(Coordinator::class);
		$bootstrapCoordinator->runInitialRegistration();

		$this->setupSession($this->server->get(IRequest::class), $this->eventLogger);

		if (!$this->checkConfig()) {
			// TODO
		}
		$this->checkInstalled($this->systemConfig);

		// User and Groups
		if (!$this->systemConfig->getValue('installed', false)) {
			$this->server->get(ISession::class)->set('user_id', '');
		}

		$this->eventLogger->start('setup_backends', 'Setup group and user backends');
		$this->server->get(\OCP\IUserManager::class)->registerBackend(new \OC\User\Database());
		$this->server->get(\OCP\IGroupManager::class)->addBackend(new \OC\Group\Database());

		// Subscribe to the hook
		Util::connectHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			'\OC\User\Database',
			'preLoginNameUsedAsUserName'
		);

		//setup extra user backends
		if (!Util::needUpgrade()) {
			OC_User::setupBackends();
		} else {
			// Run upgrades in incognito mode
			OC_User::setIncognitoMode(true);
		}
		$this->eventLogger->end('setup_backends');

		self::registerCleanupHooks($this->systemConfig);
		self::registerShareHooks($this->systemConfig);
		self::registerEncryptionWrapperAndHooks();
		self::registerAccountHooks();
		self::registerResourceCollectionHooks();
		self::registerFileReferenceEventListener();
		self::registerRenderReferenceEventListener();
		self::registerAppRestrictionsHooks();

		// Make sure that the application class is not loaded before the database is set up
		if ($this->systemConfig->getValue('installed', false)) {
			$appManager = $this->server->get(IAppManager::class);
			$appManager->loadApp('settings');
		}

		//make sure temporary files are cleaned up
		$tmpManager = $this->server->get(\OCP\ITempManager::class);
		register_shutdown_function([$tmpManager, 'clean']);
		$lockProvider = $this->server->get(\OCP\Lock\ILockingProvider::class);
		register_shutdown_function([$lockProvider, 'releaseAll']);

		// Check whether the sample configuration has been copied
		if ($this->systemConfig->getValue('copied_sample_config', false)) {
			$l = $this->server->get(\OCP\L10N\IFactory::class)->get('lib');
			$this->server->get(ITemplateManager::class)->printErrorPage(
				$l->t('Sample configuration detected'),
				$l->t('It has been detected that the sample configuration has been copied. This can break your installation and is unsupported. Please read the documentation before performing changes on config.php'),
				503
			);
			return $this;
		}

		$request = $this->server->get(IRequest::class);
		$host = $request->getInsecureServerHost();
		/**
		 * if the host passed in headers isn't trusted
		 * FIXME: Should not be in here at all :see_no_evil:
		 */
		if (!$this->isCli()
			&& !$this->server->get(\OC\Security\TrustedDomainHelper::class)->isTrustedDomain($host)
			&& $this->systemConfig->getValue('installed', false)
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
				$this->server->get(LoggerInterface::class)->info(
					'Trusted domain error. "{remoteAddress}" tried to access using "{host}" as host.',
					[
						'app' => 'core',
						'remoteAddress' => $request->getRemoteAddress(),
						'host' => $host,
					]
				);

				$tmpl = $this->server->get(ITemplateManager::class)->getTemplate('core', 'untrustedDomain', 'guest');
				$tmpl->assign('docUrl', $this->server->get(IURLGenerator::class)->linkToDocs('admin-trusted-domains'));
				$tmpl->printPage();

				exit();
			}
		}
		$this->eventLogger->end('boot');
		$this->eventLogger->log('init', 'OC::init', $loaderStart, microtime(true));
		$this->eventLogger->start('runtime', 'Runtime');
		$this->eventLogger->start('request', 'Full request after boot');
		register_shutdown_function(function () {
			$this->eventLogger->end('request');
		});

		register_shutdown_function(function () {
			$memoryPeak = memory_get_peak_usage();
			$debugModeEnabled = $this->systemConfig->getValue('debug', false);
			$memoryLimit = null;

			if (!$debugModeEnabled) {
				// Use the memory helper to get the real memory limit in bytes if debug mode is disabled
				try {
					$memoryInfo = new \OC\MemoryInfo();
					$memoryLimit = $memoryInfo->getMemoryLimit();
				} catch (Throwable $e) {
					// Ignore any errors and fall back to hardcoded thresholds
				}
			}

			// Check if a memory limit is configured and can be retrieved and determine log level if debug mode is disabled
			if (!$debugModeEnabled && $memoryLimit !== null && $memoryLimit !== -1) {
				$logLevel = match (true) {
					$memoryPeak > $memoryLimit * 0.9 => ILogger::FATAL,
					$memoryPeak > $memoryLimit * 0.75 => ILogger::ERROR,
					$memoryPeak > $memoryLimit * 0.5 => ILogger::WARN,
					default => null,
				};

				$memoryLimitIni = @ini_get('memory_limit');
				$message = 'Request used ' . Util::humanFileSize($memoryPeak) . ' of memory. Memory limit: ' . ($memoryLimitIni ?: 'unknown');
			} else {
				// Fall back to hardcoded thresholds if memory_limit cannot be determined or if debug mode is enabled
				$logLevel = match (true) {
					$memoryPeak > 500_000_000 => ILogger::FATAL,
					$memoryPeak > 400_000_000 => ILogger::ERROR,
					$memoryPeak > 300_000_000 => ILogger::WARN,
					default => null,
				};

				$message = 'Request used more than 300 MB of RAM: ' . Util::humanFileSize($memoryPeak);
			}

			// Log the message
			if ($logLevel !== null) {
				$logger = $this->server->get(LoggerInterface::class);
				$logger->log($logLevel, $message, ['app' => 'core']);
			}
		});

		return $this;
	}

	public function isCli(): bool {
		return php_sapi_name() === 'cli';
	}

	public function getServerRoot(): string {
		return $this->serverRoot;
	}

	/**
	 * Try to set some values to the required Nextcloud default
	 */
	private function setRequiredIniValues(): void {
		// Don't display errors and log them
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '1');

		// Try to configure php to enable big file uploads.
		// This doesn't work always depending on the webserver and php configuration.
		// Let's try to overwrite some defaults if they are smaller than 1 hour

		if (intval(@ini_get('max_execution_time') ?: 0) < 3600) {
			@ini_set('max_execution_time', strval(3600));
		}

		if (intval(@ini_get('max_input_time') ?: 0) < 3600) {
			@ini_set('max_input_time', strval(3600));
		}

		// Try to set the maximum execution time to the largest time limit we have
		if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
			@set_time_limit(max(intval(@ini_get('max_execution_time')), intval(@ini_get('max_input_time'))));
		}

		@ini_set('default_charset', 'UTF-8');
		@ini_set('gd.jpeg_ignore_warning', '1');
	}

	/**
	 * @throws \RuntimeException when the 3rdparty directory is missing or
	 *                           the app path list is empty or contains an invalid path
	 */
	public function initPaths(): void {
		if (defined('PHPUNIT_CONFIG_DIR')) {
			$this->configDir = $this->getServerRoot() . '/' . PHPUNIT_CONFIG_DIR . '/';
		} elseif (defined('PHPUNIT_RUN') && PHPUNIT_RUN && is_dir($this->getServerRoot() . '/tests/config/')) {
			$this->configDir = $this->getServerRoot() . '/tests/config/';
		} elseif ($dir = getenv('NEXTCLOUD_CONFIG_DIR')) {
			$this->configDir = rtrim($dir, '/') . '/';
		} else {
			$this->configDir = $this->getServerRoot() . '/config/';
		}
		\OC::$configDir = $this->configDir;
		$this->config = new Config($this->configDir);
		$this->systemConfig = new SystemConfig($this->config);

		$this->subUri = str_replace('\\', '/', substr(realpath($_SERVER['SCRIPT_FILENAME'] ?? ''), strlen($this->getServerRoot())));
		/**
		 * FIXME: The following lines are required because we can't yet instantiate
		 *        $this->server->get(\OCP\IRequest::class) since \OC::$server does not yet exist.
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
		$fakeRequest = new Request(
			$params,
			new RequestId($_SERVER['UNIQUE_ID'] ?? '', new SecureRandom()),
			new AllConfig($this->systemConfig)
		);
		$scriptName = $fakeRequest->getScriptName();
		if (substr($scriptName, -1) == '/') {
			$scriptName .= 'index.php';
			//make sure suburi follows the same rules as scriptName
			if (substr($this->subUri, -9) != 'index.php') {
				if (substr($this->subUri, -1) != '/') {
					$this->subUri = $this->subUri . '/';
				}
				$this->subUri = $this->subUri . 'index.php';
			}
		}

		if ($this->isCli()) {
			$this->webRoot = $this->config->getValue('overwritewebroot', '');
		} else {
			if (substr($scriptName, 0 - strlen($this->subUri)) === $this->subUri) {
				$this->webRoot = substr($scriptName, 0, 0 - strlen($this->subUri));

				if ($this->webRoot != '' && $this->webRoot[0] !== '/') {
					$this->webRoot = '/' . $this->webRoot;
				}
			} else {
				// The scriptName is not ending with Kernel::subUri
				// This most likely means that we are calling from CLI.
				// However, some cron jobs still need to generate
				// a web URL, so we use overwritewebroot as a fallback.
				$this->webRoot = $this->config->getValue('overwritewebroot', '');
			}

			\OC::$WEBROOT = $this->webRoot;

			// Resolve /nextcloud to /nextcloud/ to ensure to always have a trailing
			// slash which is required by URL generation.
			if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === $this->webRoot
				&& substr($_SERVER['REQUEST_URI'], -1) !== '/') {
				header('Location: ' . $this->webRoot . '/');
				exit();
			}
		}

		// search the apps folder
		$config_paths = $this->config->getValue('apps_paths', []);
		if (!empty($config_paths)) {
			foreach ($config_paths as $paths) {
				if (isset($paths['url']) && isset($paths['path'])) {
					$paths['url'] = rtrim($paths['url'], '/');
					$paths['path'] = rtrim($paths['path'], '/');
					$this->appsRoots[] = $paths;
				}
			}
		} elseif (file_exists($this->serverRoot . '/apps')) {
			$this->appsRoots[] = ['path' => $this->serverRoot . '/apps', 'url' => '/apps', 'writable' => true];
		}

		if ($this->appsRoots === []) {
			throw new \RuntimeException('apps directory not found! Please put the Nextcloud apps folder in the Nextcloud folder'
				. '. You can also configure the location in the config.php file.');
		}
		\OC::$APPSROOTS = $this->appsRoots;
		$paths = [];
		foreach ($this->appsRoots as $path) {
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

	public function getConfigDir(): string {
		return $this->configDir;
	}

	public function getAppsRoots(): array {
		return $this->appsRoots;
	}

	public function addPsr4(string $prefix, string $path, bool $prepend = false): void {
		$this->composerAutoloader->addPsr4($prefix, $path, $prepend);
	}

	public function getWebRoot(): string {
		return $this->webRoot;
	}

	public function getContainer(): ContainerInterface {
		return $this->server;
	}

	protected function getSystemConfig(): SystemConfig {
		return $this->systemConfig;
	}

	protected function getSubUri(): string {
		return $this->subUri;
	}

	public function checkConfig(): bool {
		// Create config if it does not already exist
		$configFilePath = $this->configDir . '/config.php';
		if (!file_exists($configFilePath)) {
			@touch($configFilePath);
		}

		// Check if config is writable
		$configFileWritable = is_writable($configFilePath);
		$configReadOnly = $this->config->getValue('config_is_read_only', false);

		return !(!$configFileWritable && !$configReadOnly || !$configFileWritable && Util::needUpgrade());
	}

	/**
	 * @return array{float, float}
	 */
	protected function setupAutoloader(): array {
		// register autoloader
		$loaderStart = microtime(true);

		// Add default composer PSR-4 autoloader, ensure apcu to be disabled
		$this->composerAutoloader = require_once $this->getServerRoot() . '/lib/composer/autoload.php';
		$this->composerAutoloader->setApcuPrefix(null);

		try {
			$this->initPaths();
			// setup 3rdparty autoloader
			$vendorAutoLoad = $this->getServerRoot() . '/3rdparty/autoload.php';
			if (!file_exists($vendorAutoLoad)) {
				throw new \RuntimeException('Composer autoloader not found, unable to continue. Check the folder "3rdparty". Running "git submodule update --init" will initialize the git submodule that handles the subfolder "3rdparty".');
			}
			require_once $vendorAutoLoad;
		} catch (\RuntimeException $e) {
			if (!$this->isCli()) {
				http_response_code(503);
			}
			// we can't use the template error page here, because this needs the
			// DI container which isn't available yet
			print($e->getMessage());
			exit();
		}
		$loaderEnd = microtime(true);
		return [$loaderStart, $loaderEnd];
	}

	protected function setupErrorHandler(): void {
		$config = $this->server->get(IConfig::class);
		if (!defined('PHPUNIT_RUN')) {
			$errorHandler = new ErrorHandler(
				$this->server->get(LoggerInterface::class),
			);
			$exceptionHandler = $errorHandler->onException(...);
			if ($config->getSystemValueBool('debug', false)) {
				set_error_handler($errorHandler->onAll(...), E_ALL);
				if ($this->isCli()) {
					$exceptionHandler = $this->server->get(ITemplateManager::class)->printExceptionErrorPage(...);
				}
			} else {
				set_error_handler($errorHandler->onError(...));
			}
			register_shutdown_function($errorHandler->onShutdown(...));
			set_exception_handler($exceptionHandler);
		}
	}

	protected function setupServerContainer(): void {
		// Enable lazy loading if activated
		SimpleContainer::$useLazyObjects = (bool)$this->config->getValue('enable_lazy_objects', true);

		$this->server = new Server($this->webRoot, $this->config, $this);
		$this->server->boot();
		\OC::$server = $this->server;

		try {
			$profiler = new BuiltInProfiler(
				$this->server->get(IConfig::class),
				$this->server->get(IRequest::class),
			);
			$profiler->start();
		} catch (\Throwable $e) {
			logger('core')->error('Failed to start profiler: ' . $e->getMessage(), ['app' => 'base']);
		}
	}

	protected function setupLogging(): void {
		if ($this->isCli() && in_array('--' . ReservedOptions::DEBUG_LOG, $_SERVER['argv'])) {
			BeforeMessageLoggedEventListener::setup();
		}

		// Override php.ini and log everything if we're troubleshooting
		if ($this->config->getValue('loglevel') === ILogger::DEBUG) {
			error_reporting(E_ALL);
		}
	}

	private function setupPhpDefault(): void {
		// prevent any XML processing from loading external entities
		libxml_set_external_entity_loader(static function () {
			return null;
		});

		// Set default timezone before the Server object is booted
		if (!date_default_timezone_set('UTC')) {
			throw new \RuntimeException('Could not set timezone to UTC');
		}

		// Check for PHP SimpleXML extension earlier since we need it before our other checks and want to provide a useful hint for web users
		// see https://github.com/nextcloud/server/pull/2619
		if (!function_exists('simplexml_load_file')) {
			throw new HintException('The PHP SimpleXML/PHP-XML extension is not installed.', 'Install the extension or make sure it is enabled.');
		}
	}

	abstract protected function setupSession(IRequest $request, IEventLogger $eventLogger): void;

	abstract public function checkInstalled(\OC\SystemConfig $systemConfig): void;

	/**
	 * register hooks for the cleanup of cache and bruteforce protection
	 */
	public function registerCleanupHooks(\OC\SystemConfig $systemConfig): void {
		//don't try to do this before we are properly setup
		if ($systemConfig->getValue('installed', false) && !Util::needUpgrade()) {
			// NOTE: This will be replaced to use OCP
			$userSession = $this->server->get(\OC\User\Session::class);
			$userSession->listen('\OC\User', 'postLogin', function () use ($userSession) {
				if (!defined('PHPUNIT_RUN') && $userSession->isLoggedIn()) {
					// reset brute force delay for this IP address and username
					$uid = $userSession->getUser()->getUID();
					$request = $this->server->get(IRequest::class);
					$throttler = $this->server->get(IThrottler::class);
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
					$this->server->get(LoggerInterface::class)->warning('Exception when running cache gc.', [
						'app' => 'core',
						'exception' => $e,
					]);
				}
			});
		}
	}

	private function registerEncryptionWrapperAndHooks(): void {
		/** @var \OC\Encryption\Manager */
		$manager = $this->server->get(\OCP\Encryption\IManager::class);
		$this->server->get(IEventDispatcher::class)->addListener(
			BeforeFileSystemSetupEvent::class,
			$manager->setupStorage(...),
		);

		$enabled = $manager->isEnabled();
		if ($enabled) {
			\OC\Encryption\EncryptionEventListener::register($this->server->get(IEventDispatcher::class));
		}
	}

	private function registerAccountHooks(): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $this->server->get(IEventDispatcher::class);
		$dispatcher->addServiceListener(UserChangedEvent::class, \OC\Accounts\Hooks::class);
	}

	private function registerAppRestrictionsHooks(): void {
		/** @var \OC\Group\Manager $groupManager */
		$groupManager = $this->server->get(\OCP\IGroupManager::class);
		$groupManager->listen('\OC\Group', 'postDelete', function (\OCP\IGroup $group) {
			$appManager = $this->server->get(\OCP\App\IAppManager::class);
			$apps = $appManager->getEnabledAppsForGroup($group);
			foreach ($apps as $appId) {
				$restrictions = $appManager->getAppRestriction($appId);
				if (empty($restrictions)) {
					continue;
				}
				$key = array_search($group->getGID(), $restrictions, true);
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

	private function registerResourceCollectionHooks(): void {
		\OC\Collaboration\Resources\Listener::register($this->server->get(IEventDispatcher::class));
	}

	private function registerFileReferenceEventListener(): void {
		\OC\Collaboration\Reference\File\FileReferenceEventListener::register($this->server->get(IEventDispatcher::class));
	}

	private function registerRenderReferenceEventListener() {
		\OC\Collaboration\Reference\RenderReferenceEventListener::register($this->server->get(IEventDispatcher::class));
	}

	/**
	 * register hooks for sharing
	 */
	public function registerShareHooks(\OC\SystemConfig $systemConfig): void {
		if ($systemConfig->getValue('installed')) {

			$dispatcher = $this->server->get(IEventDispatcher::class);
			$dispatcher->addServiceListener(UserRemovedEvent::class, UserRemovedListener::class);
			$dispatcher->addServiceListener(GroupDeletedEvent::class, GroupDeletedListener::class);
			$dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
		}
	}
}
