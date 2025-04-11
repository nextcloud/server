<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Routing;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\Route\Router;

/**
 * Class RouteConfig
 * @package OC\AppFramework\routing
 */
class RouteConfig {
	/** @var DIContainer */
	private $container;

	/** @var Router */
	private $router;

	/** @var array */
	private $routes;

	/** @var string */
	private $appName;

	/** @var string[] */
	private $controllerNameCache = [];

	protected $rootUrlApps = [
		'cloud_federation_api',
		'core',
		'files_sharing',
		'files',
		'profile',
		'settings',
		'spreed',
	];

	/**
	 * @param \OC\AppFramework\DependencyInjection\DIContainer $container
	 * @param \OC\Route\Router $router
	 * @param array $routes
	 * @internal param $appName
	 */
	public function __construct(DIContainer $container, Router $router, $routes) {
		$this->routes = $routes;
		$this->container = $container;
		$this->router = $router;
		$this->appName = $container['AppName'];
	}

	/**
	 * The routes and resource will be registered to the \OCP\Route\IRouter
	 */
	public function register() {
		// parse simple
		$this->processIndexRoutes($this->routes);

		// parse resources
		$this->processIndexResources($this->routes);

		/*
		 * OCS routes go into a different collection
		 */
		$oldCollection = $this->router->getCurrentCollection();
		$this->router->useCollection($oldCollection . '.ocs');

		// parse ocs simple routes
		$this->processOCS($this->routes);

		// parse ocs simple routes
		$this->processOCSResources($this->routes);

		$this->router->useCollection($oldCollection);
	}

	private function processOCS(array $routes): void {
		$ocsRoutes = $routes['ocs'] ?? [];
		foreach ($ocsRoutes as $ocsRoute) {
			$this->processRoute($ocsRoute, 'ocs.');
		}
	}

	/**
	 * Creates one route base on the give configuration
	 * @param array $routes
	 * @throws \UnexpectedValueException
	 */
	private function processIndexRoutes(array $routes): void {
		$simpleRoutes = $routes['routes'] ?? [];
		foreach ($simpleRoutes as $simpleRoute) {
			$this->processRoute($simpleRoute);
		}
	}

	protected function processRoute(array $route, string $routeNamePrefix = ''): void {
		$name = $route['name'];
		$postfix = $route['postfix'] ?? '';
		$root = $this->buildRootPrefix($route, $routeNamePrefix);

		$url = $root . '/' . ltrim($route['url'], '/');
		$verb = strtoupper($route['verb'] ?? 'GET');

		$split = explode('#', $name, 2);
		if (count($split) !== 2) {
			throw new \UnexpectedValueException('Invalid route name: use the format foo#bar to reference FooController::bar');
		}
		[$controller, $action] = $split;

		$controllerName = $this->buildControllerName($controller);
		$actionName = $this->buildActionName($action);

		/*
		 * The route name has to be lowercase, for symfony to match it correctly.
		 * This is required because smyfony allows mixed casing for controller names in the routes.
		 * To avoid breaking all the existing route names, registering and matching will only use the lowercase names.
		 * This is also safe on the PHP side because class and method names collide regardless of the casing.
		 */
		$routeName = strtolower($routeNamePrefix . $this->appName . '.' . $controller . '.' . $action . $postfix);

		$router = $this->router->create($routeName, $url)
			->method($verb);

		// optionally register requirements for route. This is used to
		// tell the route parser how url parameters should be matched
		if (array_key_exists('requirements', $route)) {
			$router->requirements($route['requirements']);
		}

		// optionally register defaults for route. This is used to
		// tell the route parser how url parameters should be default valued
		$defaults = [];
		if (array_key_exists('defaults', $route)) {
			$defaults = $route['defaults'];
		}

		$defaults['caller'] = [$this->appName, $controllerName, $actionName];
		$router->defaults($defaults);
	}

	/**
	 * For a given name and url restful OCS routes are created:
	 *  - index
	 *  - show
	 *  - create
	 *  - update
	 *  - destroy
	 *
	 * @param array $routes
	 */
	private function processOCSResources(array $routes): void {
		$this->processResources($routes['ocs-resources'] ?? [], 'ocs.');
	}

	/**
	 * For a given name and url restful routes are created:
	 *  - index
	 *  - show
	 *  - create
	 *  - update
	 *  - destroy
	 *
	 * @param array $routes
	 */
	private function processIndexResources(array $routes): void {
		$this->processResources($routes['resources'] ?? []);
	}

	/**
	 * For a given name and url restful routes are created:
	 *  - index
	 *  - show
	 *  - create
	 *  - update
	 *  - destroy
	 *
	 * @param array $resources
	 * @param string $routeNamePrefix
	 */
	protected function processResources(array $resources, string $routeNamePrefix = ''): void {
		// declaration of all restful actions
		$actions = [
			['name' => 'index', 'verb' => 'GET', 'on-collection' => true],
			['name' => 'show', 'verb' => 'GET'],
			['name' => 'create', 'verb' => 'POST', 'on-collection' => true],
			['name' => 'update', 'verb' => 'PUT'],
			['name' => 'destroy', 'verb' => 'DELETE'],
		];

		foreach ($resources as $resource => $config) {
			$root = $this->buildRootPrefix($config, $routeNamePrefix);

			// the url parameter used as id to the resource
			foreach ($actions as $action) {
				$url = $root . '/' . ltrim($config['url'], '/');
				$method = $action['name'];

				$verb = strtoupper($action['verb'] ?? 'GET');
				$collectionAction = $action['on-collection'] ?? false;
				if (!$collectionAction) {
					$url .= '/{id}';
				}
				if (isset($action['url-postfix'])) {
					$url .= '/' . $action['url-postfix'];
				}

				$controller = $resource;

				$controllerName = $this->buildControllerName($controller);
				$actionName = $this->buildActionName($method);

				$routeName = $routeNamePrefix . $this->appName . '.' . strtolower($resource) . '.' . $method;

				$route = $this->router->create($routeName, $url)
					->method($verb);

				$route->defaults(['caller' => [$this->appName, $controllerName, $actionName]]);
			}
		}
	}

	private function buildRootPrefix(array $route, string $routeNamePrefix): string {
		$defaultRoot = $this->appName === 'core' ? '' : '/apps/' . $this->appName;
		$root = $route['root'] ?? $defaultRoot;

		if ($routeNamePrefix !== '') {
			// In OCS all apps are whitelisted
			return $root;
		}

		if (!\in_array($this->appName, $this->rootUrlApps, true)) {
			// Only allow root URLS for some apps
			return  $defaultRoot;
		}

		return $root;
	}

	/**
	 * Based on a given route name the controller name is generated
	 * @param string $controller
	 * @return string
	 */
	private function buildControllerName(string $controller): string {
		if (!isset($this->controllerNameCache[$controller])) {
			$this->controllerNameCache[$controller] = $this->underScoreToCamelCase(ucfirst($controller)) . 'Controller';
		}
		return $this->controllerNameCache[$controller];
	}

	/**
	 * Based on the action part of the route name the controller method name is generated
	 * @param string $action
	 * @return string
	 */
	private function buildActionName(string $action): string {
		return $this->underScoreToCamelCase($action);
	}

	/**
	 * Underscored strings are converted to camel case strings
	 * @param string $str
	 * @return string
	 */
	private function underScoreToCamelCase(string $str): string {
		$pattern = '/_[a-z]?/';
		return preg_replace_callback(
			$pattern,
			function ($matches) {
				return strtoupper(ltrim($matches[0], '_'));
			},
			$str);
	}
}
