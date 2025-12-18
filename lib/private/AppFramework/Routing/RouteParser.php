<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Routing;

use OC\Route\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteParser {
	/** @var string[] */
	private $controllerNameCache = [];

	private const rootUrlApps = [
		'cloud_federation_api',
		'core',
		'files_sharing',
		'files',
		'globalsiteselector',
		'profile',
		'settings',
		'spreed',
	];

	public function parseDefaultRoutes(array $routes, string $appName): RouteCollection {
		$collection = $this->processIndexRoutes($routes, $appName);
		$collection->addCollection($this->processIndexResources($routes, $appName));

		return $collection;
	}

	public function parseOCSRoutes(array $routes, string $appName): RouteCollection {
		$collection = $this->processOCS($routes, $appName);
		$collection->addCollection($this->processOCSResources($routes, $appName));

		return $collection;
	}

	private function processOCS(array $routes, string $appName): RouteCollection {
		$collection = new RouteCollection();
		$ocsRoutes = $routes['ocs'] ?? [];
		foreach ($ocsRoutes as $ocsRoute) {
			$result = $this->processRoute($ocsRoute, $appName, 'ocs.');

			$collection->add($result[0], $result[1]);
		}

		return $collection;
	}

	/**
	 * Creates one route base on the give configuration
	 * @param array $routes
	 * @throws \UnexpectedValueException
	 */
	private function processIndexRoutes(array $routes, string $appName): RouteCollection {
		$collection = new RouteCollection();
		$simpleRoutes = $routes['routes'] ?? [];
		foreach ($simpleRoutes as $simpleRoute) {
			$result = $this->processRoute($simpleRoute, $appName);

			$collection->add($result[0], $result[1]);
		}

		return $collection;
	}

	private function processRoute(array $route, string $appName, string $routeNamePrefix = ''): array {
		$name = $route['name'];
		$postfix = $route['postfix'] ?? '';
		$root = $this->buildRootPrefix($route, $appName, $routeNamePrefix);

		$url = $root . '/' . ltrim($route['url'], '/');

		$split = explode('#', $name, 3);
		if (count($split) !== 2) {
			throw new \UnexpectedValueException('Invalid route name: use the format foo#bar to reference FooController::bar');
		}
		[$controller, $action] = $split;

		$controllerName = $this->buildControllerName($controller);
		$actionName = $this->buildActionName($action);

		/*
		 * The route name has to be lowercase, for symfony to match it correctly.
		 * This is required because symfony allows mixed casing for controller names in the routes.
		 * To avoid breaking all the existing route names, registering and matching will only use the lowercase names.
		 * This is also safe on the PHP side because class and method names collide regardless of the casing.
		 */
		$routeName = strtolower($routeNamePrefix . $appName . '.' . $controller . '.' . $action . $postfix);

		$routeObject = new Route($url);
		$routeObject->method((array)($route['verb'] ?? ['GET']));

		// optionally register requirements for route. This is used to
		// tell the route parser how url parameters should be matched
		if (array_key_exists('requirements', $route)) {
			$routeObject->requirements($route['requirements']);
		}

		// optionally register defaults for route. This is used to
		// tell the route parser how url parameters should be default valued
		$defaults = [];
		if (array_key_exists('defaults', $route)) {
			$defaults = $route['defaults'];
		}

		$defaults['caller'] = [$appName, $controllerName, $actionName];
		$routeObject->defaults($defaults);

		return [$routeName, $routeObject];
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
	private function processOCSResources(array $routes, string $appName): RouteCollection {
		return $this->processResources($routes['ocs-resources'] ?? [], $appName, 'ocs.');
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
	private function processIndexResources(array $routes, string $appName): RouteCollection {
		return $this->processResources($routes['resources'] ?? [], $appName);
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
	private function processResources(array $resources, string $appName, string $routeNamePrefix = ''): RouteCollection {
		// declaration of all restful actions
		$actions = [
			['name' => 'index', 'verb' => 'GET', 'on-collection' => true],
			['name' => 'show', 'verb' => 'GET'],
			['name' => 'create', 'verb' => 'POST', 'on-collection' => true],
			['name' => 'update', 'verb' => 'PUT'],
			['name' => 'destroy', 'verb' => 'DELETE'],
		];

		$collection = new RouteCollection();
		foreach ($resources as $resource => $config) {
			$root = $this->buildRootPrefix($config, $appName, $routeNamePrefix);

			// the url parameter used as id to the resource
			foreach ($actions as $action) {
				$url = $root . '/' . ltrim($config['url'], '/');
				$method = $action['name'];

				$collectionAction = $action['on-collection'] ?? false;
				if (!$collectionAction) {
					$url .= '/{id}';
				}

				$controller = $resource;

				$controllerName = $this->buildControllerName($controller);
				$actionName = $this->buildActionName($method);

				$routeName = $routeNamePrefix . $appName . '.' . strtolower($resource) . '.' . $method;

				$route = new Route($url);
				$route->method((array)($action['verb'] ?? ['GET']));

				$route->defaults(['caller' => [$appName, $controllerName, $actionName]]);

				$collection->add($routeName, $route);
			}
		}

		return $collection;
	}

	private function buildRootPrefix(array $route, string $appName, string $routeNamePrefix): string {
		$defaultRoot = $appName === 'core' ? '' : '/apps/' . $appName;
		$root = $route['root'] ?? $defaultRoot;

		if ($routeNamePrefix !== '') {
			// In OCS all apps are whitelisted
			return $root;
		}

		if (!\in_array($appName, self::rootUrlApps, true)) {
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
