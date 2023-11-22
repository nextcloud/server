<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\Route;

use OC\AppFramework\Routing\RouteParser;
use OCP\AppFramework\App;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Route\IRouter;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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
	protected LoggerInterface $logger;
	/** @var RequestContext */
	protected $context;
	private IEventLogger $eventLogger;
	private IConfig $config;
	private ContainerInterface $container;

	public function __construct(
		LoggerInterface $logger,
		IRequest $request,
		IConfig $config,
		IEventLogger $eventLogger,
		ContainerInterface $container
	) {
		$this->logger = $logger;
		$this->config = $config;
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
		$this->eventLogger = $eventLogger;
		$this->container = $container;
	}

	/**
	 * Get the files to load the routes from
	 *
	 * @return string[]
	 */
	public function getRoutingFiles() {
		if ($this->routingFiles === null) {
			$this->routingFiles = [];
			foreach (\OC_APP::getEnabledApps() as $app) {
				$appPath = \OC_App::getAppPath($app);
				if ($appPath !== false) {
					$file = $appPath . '/appinfo/routes.php';
					if (file_exists($file)) {
						$this->routingFiles[$app] = $file;
					}
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
			$app = \OC_App::cleanAppId($app);
		}

		$requestedApp = $app;
		if ($this->loaded) {
			return;
		}
		if (is_null($app)) {
			$this->loaded = true;
			$routingFiles = $this->getRoutingFiles();
		} else {
			if (isset($this->loadedApps[$app])) {
				return;
			}
			$appPath = \OC_App::getAppPath($app);
			$file = $appPath . '/appinfo/routes.php';
			if ($appPath !== false && file_exists($file)) {
				$routingFiles = [$app => $file];
			} else {
				$routingFiles = [];
			}
		}
		$this->eventLogger->start('route:load:' . $requestedApp, 'Loading Routes for ' . $requestedApp);
		foreach ($routingFiles as $app => $file) {
			if (!isset($this->loadedApps[$app])) {
				if (!\OC_App::isAppLoaded($app)) {
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
				$collection = $this->getCollection($app.'.ocs');
				$collection->addPrefix('/ocsapp');
				$this->root->addCollection($collection);
			}
		}
		if (!isset($this->loadedApps['core'])) {
			$this->loadedApps['core'] = true;
			$this->useCollection('root');
			require_once __DIR__ . '/../../../core/routes.php';

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

			$app = \OC_App::cleanAppId($app);
			\OC::$REQUESTEDAPP = $app;
			$this->loadRoutes($app);
		} elseif (str_starts_with($url, '/ocsapp/apps/')) {
			// empty string / 'ocsapp' / 'apps' / $app / rest of the route
			[, , , $app,] = explode('/', $url, 5);

			$app = \OC_App::cleanAppId($app);
			\OC::$REQUESTEDAPP = $app;
			$this->loadRoutes($app);
		} elseif (str_starts_with($url, '/settings/')) {
			$this->loadRoutes('settings');
		} elseif (str_starts_with($url, '/core/')) {
			\OC::$REQUESTEDAPP = $url;
			if (!$this->config->getSystemValueBool('maintenance') && !Util::needUpgrade()) {
				\OC_App::loadApps();
			}
			$this->loadRoutes('core');
		} else {
			$this->loadRoutes();
		}

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
			$action = $parameters['action'];
			if (!is_callable($action)) {
				throw new \Exception('not a callable action');
			}
			unset($parameters['action']);
			unset($parameters['caller']);
			$this->eventLogger->start('route:run:call', 'Run callable route');
			call_user_func($action, $parameters);
			$this->eventLogger->end('route:run:call');
		} elseif (isset($parameters['file'])) {
			include $parameters['file'];
		} else {
			throw new \Exception('no action available');
		}
		$this->eventLogger->end('route:run');
	}

	/**
	 * Get the url generator
	 *
	 * @return \Symfony\Component\Routing\Generator\UrlGenerator
	 *
	 */
	public function getGenerator() {
		if (null !== $this->generator) {
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
		if ($routeName === 'files.viewcontroller.showFile') {
			return 'files.View.showFile';
		}
		if ($routeName === 'files_sharing.sharecontroller.showShare') {
			return 'files_sharing.Share.showShare';
		}
		if ($routeName === 'files_sharing.sharecontroller.showAuthenticate') {
			return 'files_sharing.Share.showAuthenticate';
		}
		if ($routeName === 'files_sharing.sharecontroller.authenticate') {
			return 'files_sharing.Share.authenticate';
		}
		if ($routeName === 'files_sharing.sharecontroller.downloadShare') {
			return 'files_sharing.Share.downloadShare';
		}
		if ($routeName === 'files_sharing.publicpreview.directLink') {
			return 'files_sharing.PublicPreview.directLink';
		}
		if ($routeName === 'cloud_federation_api.requesthandlercontroller.addShare') {
			return 'cloud_federation_api.RequestHandler.addShare';
		}
		if ($routeName === 'cloud_federation_api.requesthandlercontroller.receiveNotification') {
			return 'cloud_federation_api.RequestHandler.receiveNotification';
		}
		return $routeName;
	}

	/**
	 * To isolate the variable scope used inside the $file it is required in it's own method
	 *
	 * @param string $file the route file location to include
	 * @param string $appName
	 */
	private function requireRouteFile($file, $appName) {
		$this->setupRoutes(include_once $file, $appName);
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
