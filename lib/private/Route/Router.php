<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Route;

use DirectoryIterator;
use OC\AppFramework\Routing\RouteParser;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Http\Attribute\Route as RouteAttribute;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Route\IRouter;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class Router implements IRouter {
	/** @var RouteCollection[] */
	protected $collections = [];
	/** @var null|RouteCollection */
	protected $collection = null;
	/** @var null|string */
	protected $collectionName = null;
	/** @var null|RouteCollection */
	protected $root = null;
	/** @var null|UrlGenerator */
	protected $generator = null;
	/** @var string[]|null */
	protected $routingFiles;
	/** @var bool */
	protected $loaded = false;
	/** @var array */
	protected $loadedApps = [];
	/** @var RequestContext */
	protected $context;

	public function __construct(
		protected LoggerInterface $logger,
		IRequest $request,
		protected IConfig $config,
		protected IEventLogger $eventLogger,
		private ContainerInterface $container,
		protected IAppManager $appManager,
	) {
		$baseUrl = \OC::$WEBROOT;
		if (!($config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true')) {
			$baseUrl .= '/index.php';
		}
		if (!\OC::$CLI && isset($_SERVER['REQUEST_METHOD'])) {
			$method = $_SERVER['REQUEST_METHOD'];
		} else {
			$method = 'GET';
		}
		$host = $request->getServerHost();
		$schema = $request->getServerProtocol();
		$this->context = new RequestContext($baseUrl, $method, $host, $schema);
		// TODO cache
		$this->root = $this->getCollection('root');
	}

	/**
	 * Get the files to load the routes from
	 *
	 * @return string[]
	 */
	public function getRoutingFiles() {
		if ($this->routingFiles === null) {
			$this->routingFiles = [];
			foreach ($this->appManager->getEnabledApps() as $app) {
				try {
					$appPath = $this->appManager->getAppPath($app);
					$file = $appPath . '/appinfo/routes.php';
					if (file_exists($file)) {
						$this->routingFiles[$app] = $file;
					}
				} catch (AppPathNotFoundException) {
					/* ignore */
				}
			}
		}
		return $this->routingFiles;
	}

	/**
	 * Loads the routes
	 *
	 * @param null|string $app
	 */
	public function loadRoutes($app = null) {
		if (is_string($app)) {
			$app = $this->appManager->cleanAppId($app);
		}

		$requestedApp = $app;
		if ($this->loaded) {
			return;
		}
		$this->eventLogger->start('route:load:' . $requestedApp, 'Loading Routes for ' . $requestedApp);
		if (is_null($app)) {
			$this->loaded = true;
			$routingFiles = $this->getRoutingFiles();

			$this->eventLogger->start('route:load:attributes', 'Loading Routes from attributes');
			foreach ($this->appManager->getEnabledApps() as $enabledApp) {
				$this->loadAttributeRoutes($enabledApp);
			}
			$this->eventLogger->end('route:load:attributes');
		} else {
			if (isset($this->loadedApps[$app])) {
				return;
			}
			try {
				$appPath = $this->appManager->getAppPath($app);
				$file = $appPath . '/appinfo/routes.php';
				if (file_exists($file)) {
					$routingFiles = [$app => $file];
				} else {
					$routingFiles = [];
				}
			} catch (AppPathNotFoundException) {
				$routingFiles = [];
			}

			if ($this->appManager->isEnabledForUser($app)) {
				$this->loadAttributeRoutes($app);
			}
		}

		$this->eventLogger->start('route:load:files', 'Loading Routes from files');
		foreach ($routingFiles as $app => $file) {
			if (!isset($this->loadedApps[$app])) {
				if (!$this->appManager->isAppLoaded($app)) {
					// app MUST be loaded before app routes
					// try again next time loadRoutes() is called
					$this->loaded = false;
					continue;
				}
				$this->loadedApps[$app] = true;
				$this->useCollection($app);
				$this->requireRouteFile($file, $app);
				$collection = $this->getCollection($app);
				$this->root->addCollection($collection);

				// Also add the OCS collection
				$collection = $this->getCollection($app . '.ocs');
				$collection->addPrefix('/ocsapp');
				$this->root->addCollection($collection);
			}
		}
		$this->eventLogger->end('route:load:files');

		if (!isset($this->loadedApps['core'])) {
			$this->loadedApps['core'] = true;
			$this->useCollection('root');
			$this->setupRoutes($this->getAttributeRoutes('core'), 'core');
			$this->requireRouteFile(__DIR__ . '/../../../core/routes.php', 'core');

			// Also add the OCS collection
			$collection = $this->getCollection('root.ocs');
			$collection->addPrefix('/ocsapp');
			$this->root->addCollection($collection);
		}
		if ($this->loaded) {
			$collection = $this->getCollection('ocs');
			$collection->addPrefix('/ocs');
			$this->root->addCollection($collection);
		}
		$this->eventLogger->end('route:load:' . $requestedApp);
	}

	/**
	 * @param string $name
	 * @return \Symfony\Component\Routing\RouteCollection
	 */
	protected function getCollection($name) {
		if (!isset($this->collections[$name])) {
			$this->collections[$name] = new RouteCollection();
		}
		return $this->collections[$name];
	}

	/**
	 * Sets the collection to use for adding routes
	 *
	 * @param string $name Name of the collection to use.
	 * @return void
	 */
	public function useCollection($name) {
		$this->collection = $this->getCollection($name);
		$this->collectionName = $name;
	}

	/**
	 * returns the current collection name in use for adding routes
	 *
	 * @return string the collection name
	 */
	public function getCurrentCollection() {
		return $this->collectionName;
	}


	/**
	 * Create a \OC\Route\Route.
	 *
	 * @param string $name Name of the route to create.
	 * @param string $pattern The pattern to match
	 * @param array $defaults An array of default parameter values
	 * @param array $requirements An array of requirements for parameters (regexes)
	 * @return \OC\Route\Route
	 */
	public function create($name,
		$pattern,
		array $defaults = [],
		array $requirements = []) {
		$route = new Route($pattern, $defaults, $requirements);
		$this->collection->add($name, $route);
		return $route;
	}

	/**
	 * Find the route matching $url
	 *
	 * @param string $url The url to find
	 * @throws \Exception
	 * @return array
	 */
	public function findMatchingRoute(string $url): array {
		$this->eventLogger->start('route:match', 'Match route');
		if (str_starts_with($url, '/apps/')) {
			// empty string / 'apps' / $app / rest of the route
			[, , $app,] = explode('/', $url, 4);

			$app = $this->appManager->cleanAppId($app);
			\OC::$REQUESTEDAPP = $app;
			$this->loadRoutes($app);
		} elseif (str_starts_with($url, '/ocsapp/apps/')) {
			// empty string / 'ocsapp' / 'apps' / $app / rest of the route
			[, , , $app,] = explode('/', $url, 5);

			$app = $this->appManager->cleanAppId($app);
			\OC::$REQUESTEDAPP = $app;
			$this->loadRoutes($app);
		} elseif (str_starts_with($url, '/settings/')) {
			$this->loadRoutes('settings');
		} elseif (str_starts_with($url, '/core/')) {
			\OC::$REQUESTEDAPP = $url;
			if ($this->config->getSystemValueBool('installed', false) && !Util::needUpgrade()) {
				$this->appManager->loadApps();
			}
			$this->loadRoutes('core');
		} else {
			$this->loadRoutes();
		}

		$this->eventLogger->start('route:url:match', 'Symfony url matcher call');
		$matcher = new UrlMatcher($this->root, $this->context);
		try {
			$parameters = $matcher->match($url);
		} catch (ResourceNotFoundException $e) {
			if (!str_ends_with($url, '/')) {
				// We allow links to apps/files? for backwards compatibility reasons
				// However, since Symfony does not allow empty route names, the route
				// we need to match is '/', so we need to append the '/' here.
				try {
					$parameters = $matcher->match($url . '/');
				} catch (ResourceNotFoundException $newException) {
					// If we still didn't match a route, we throw the original exception
					throw $e;
				}
			} else {
				throw $e;
			}
		}
		$this->eventLogger->end('route:url:match');

		$this->eventLogger->end('route:match');
		return $parameters;
	}

	/**
	 * Find and execute the route matching $url
	 *
	 * @param string $url The url to find
	 * @throws \Exception
	 * @return void
	 */
	public function match($url) {
		$parameters = $this->findMatchingRoute($url);

		$this->eventLogger->start('route:run', 'Run route');
		if (isset($parameters['caller'])) {
			$caller = $parameters['caller'];
			unset($parameters['caller']);
			unset($parameters['action']);
			$application = $this->getApplicationClass($caller[0]);
			\OC\AppFramework\App::main($caller[1], $caller[2], $application->getContainer(), $parameters);
		} elseif (isset($parameters['action'])) {
			$this->logger->warning('Deprecated action route used', ['parameters' => $parameters]);
			$this->callLegacyActionRoute($parameters);
		} elseif (isset($parameters['file'])) {
			$this->logger->debug('Deprecated file route used', ['parameters' => $parameters]);
			$this->includeLegacyFileRoute($parameters);
		} else {
			throw new \Exception('no action available');
		}
		$this->eventLogger->end('route:run');
	}

	/**
	 * @param array{file:mixed, ...} $parameters
	 */
	protected function includeLegacyFileRoute(array $parameters): void {
		$param = $parameters;
		unset($param['_route']);
		$_GET = array_merge($_GET, $param);
		unset($param);
		require_once $parameters['file'];
	}

	/**
	 * @param array{action:mixed, ...} $parameters
	 */
	protected function callLegacyActionRoute(array $parameters): void {
		$action = $parameters['action'];
		if (!is_callable($action)) {
			throw new \Exception('not a callable action');
		}
		unset($parameters['action']);
		unset($parameters['caller']);
		$this->eventLogger->start('route:run:call', 'Run callable route');
		call_user_func($action, $parameters);
		$this->eventLogger->end('route:run:call');
	}

	/**
	 * Get the url generator
	 *
	 * @return \Symfony\Component\Routing\Generator\UrlGenerator
	 *
	 */
	public function getGenerator() {
		if ($this->generator !== null) {
			return $this->generator;
		}

		return $this->generator = new UrlGenerator($this->root, $this->context);
	}

	/**
	 * Generate url based on $name and $parameters
	 *
	 * @param string $name Name of the route to use.
	 * @param array $parameters Parameters for the route
	 * @param bool $absolute
	 * @return string
	 */
	public function generate($name,
		$parameters = [],
		$absolute = false) {
		$referenceType = UrlGenerator::ABSOLUTE_URL;
		if ($absolute === false) {
			$referenceType = UrlGenerator::ABSOLUTE_PATH;
		}
		/*
		 * The route name has to be lowercase, for symfony to match it correctly.
		 * This is required because smyfony allows mixed casing for controller names in the routes.
		 * To avoid breaking all the existing route names, registering and matching will only use the lowercase names.
		 * This is also safe on the PHP side because class and method names collide regardless of the casing.
		 */
		$name = strtolower($name);
		$name = $this->fixLegacyRootName($name);
		if (str_contains($name, '.')) {
			[$appName, $other] = explode('.', $name, 3);
			// OCS routes are prefixed with "ocs."
			if ($appName === 'ocs') {
				$appName = $other;
			}
			$this->loadRoutes($appName);
			try {
				return $this->getGenerator()->generate($name, $parameters, $referenceType);
			} catch (RouteNotFoundException $e) {
			}
		}

		// Fallback load all routes
		$this->loadRoutes();
		try {
			return $this->getGenerator()->generate($name, $parameters, $referenceType);
		} catch (RouteNotFoundException $e) {
			$this->logger->info($e->getMessage(), ['exception' => $e]);
			return '';
		}
	}

	protected function fixLegacyRootName(string $routeName): string {
		if ($routeName === 'files.viewcontroller.showfile') {
			return 'files.view.showfile';
		}
		if ($routeName === 'files_sharing.sharecontroller.showshare') {
			return 'files_sharing.share.showshare';
		}
		if ($routeName === 'files_sharing.sharecontroller.showauthenticate') {
			return 'files_sharing.share.showauthenticate';
		}
		if ($routeName === 'files_sharing.sharecontroller.authenticate') {
			return 'files_sharing.share.authenticate';
		}
		if ($routeName === 'files_sharing.sharecontroller.downloadshare') {
			return 'files_sharing.share.downloadshare';
		}
		if ($routeName === 'files_sharing.publicpreview.directlink') {
			return 'files_sharing.publicpreview.directlink';
		}
		if ($routeName === 'cloud_federation_api.requesthandlercontroller.addshare') {
			return 'cloud_federation_api.requesthandler.addshare';
		}
		if ($routeName === 'cloud_federation_api.requesthandlercontroller.receivenotification') {
			return 'cloud_federation_api.requesthandler.receivenotification';
		}
		if ($routeName === 'core.ProfilePage.index') {
			return 'profile.ProfilePage.index';
		}
		return $routeName;
	}

	private function loadAttributeRoutes(string $app): void {
		$routes = $this->getAttributeRoutes($app);
		if (count($routes) === 0) {
			return;
		}

		$this->useCollection($app);
		$this->setupRoutes($routes, $app);
		$collection = $this->getCollection($app);
		$this->root->addCollection($collection);

		// Also add the OCS collection
		$collection = $this->getCollection($app . '.ocs');
		$collection->addPrefix('/ocsapp');
		$this->root->addCollection($collection);
	}

	/**
	 * @throws ReflectionException
	 */
	private function getAttributeRoutes(string $app): array {
		$routes = [];

		if ($app === 'core') {
			$appControllerPath = __DIR__ . '/../../../core/Controller';
			$appNameSpace = 'OC\\Core';
		} else {
			try {
				$appControllerPath = $this->appManager->getAppPath($app) . '/lib/Controller';
			} catch (AppPathNotFoundException) {
				return [];
			}
			$appNameSpace = App::buildAppNamespace($app);
		}

		if (!file_exists($appControllerPath)) {
			return [];
		}

		$dir = new DirectoryIterator($appControllerPath);
		foreach ($dir as $file) {
			if (!str_ends_with($file->getPathname(), 'Controller.php')) {
				continue;
			}

			$class = new ReflectionClass($appNameSpace . '\\Controller\\' . basename($file->getPathname(), '.php'));

			foreach ($class->getMethods() as $method) {
				foreach ($method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
					$route = $attribute->newInstance();

					$serializedRoute = $route->toArray();
					// Remove 'Controller' suffix
					$serializedRoute['name'] = substr($class->getShortName(), 0, -10) . '#' . $method->getName();

					$key = $route->getType();

					$routes[$key] ??= [];
					$routes[$key][] = $serializedRoute;
				}
			}
		}

		return $routes;
	}

	/**
	 * To isolate the variable scope used inside the $file it is required in it's own method
	 *
	 * @param string $file the route file location to include
	 * @param string $appName
	 */
	protected function requireRouteFile(string $file, string $appName): void {
		$this->setupRoutes(include $file, $appName);
	}


	/**
	 * If a routes.php file returns an array, try to set up the application and
	 * register the routes for the app. The application class will be chosen by
	 * camelcasing the appname, e.g.: my_app will be turned into
	 * \OCA\MyApp\AppInfo\Application. If that class does not exist, a default
	 * App will be initialized. This makes it optional to ship an
	 * appinfo/application.php by using the built in query resolver
	 *
	 * @param array $routes the application routes
	 * @param string $appName the name of the app.
	 */
	private function setupRoutes($routes, $appName) {
		if (is_array($routes)) {
			$routeParser = new RouteParser();

			$defaultRoutes = $routeParser->parseDefaultRoutes($routes, $appName);
			$ocsRoutes = $routeParser->parseOCSRoutes($routes, $appName);

			$this->root->addCollection($defaultRoutes);
			$ocsRoutes->addPrefix('/ocsapp');
			$this->root->addCollection($ocsRoutes);
		}
	}

	private function getApplicationClass(string $appName) {
		$appNameSpace = App::buildAppNamespace($appName);

		$applicationClassName = $appNameSpace . '\\AppInfo\\Application';

		if (class_exists($applicationClassName)) {
			$application = $this->container->get($applicationClassName);
		} else {
			$application = new App($appName);
		}

		return $application;
	}
}
