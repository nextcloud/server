<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Patrick Paysant <ppaysant@linagora.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\AppFramework\Routing;

use OC\AppFramework\DependencyInjection\DIContainer;
use OCP\Route\IRouter;

/**
 * Class RouteConfig
 * @package OC\AppFramework\routing
 */
class RouteConfig {
	private $container;
	private $router;
	private $routes;
	private $appName;

	/**
	 * @param \OC\AppFramework\DependencyInjection\DIContainer $container
	 * @param \OCP\Route\IRouter $router
	 * @internal param $appName
	 */
	public function __construct(DIContainer $container, IRouter $router, $routes) {
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
		$this->processSimpleRoutes($this->routes);

		// parse resources
		$this->processResources($this->routes);
	}

	/**
	 * Creates one route base on the give configuration
	 * @param array $routes
	 * @throws \UnexpectedValueException
	 */
	private function processSimpleRoutes($routes)
	{
		$simpleRoutes = isset($routes['routes']) ? $routes['routes'] : array();
		foreach ($simpleRoutes as $simpleRoute) {
			$name = $simpleRoute['name'];
			$postfix = '';

			if (isset($simpleRoute['postfix'])) {
				$postfix = $simpleRoute['postfix'];
			}

			$url = $simpleRoute['url'];
			$verb = isset($simpleRoute['verb']) ? strtoupper($simpleRoute['verb']) : 'GET';

			$split = explode('#', $name, 2);
			if (count($split) != 2) {
				throw new \UnexpectedValueException('Invalid route name');
			}
			$controller = $split[0];
			$action = $split[1];

			$controllerName = $this->buildControllerName($controller);
			$actionName = $this->buildActionName($action);

			// register the route
			$handler = new RouteActionHandler($this->container, $controllerName, $actionName);
			$router = $this->router->create($this->appName.'.'.$controller.'.'.$action . $postfix, $url)
							->method($verb)
							->action($handler);

			// optionally register requirements for route. This is used to
			// tell the route parser how url parameters should be matched
			if(array_key_exists('requirements', $simpleRoute)) {
				$router->requirements($simpleRoute['requirements']);
			}

			// optionally register defaults for route. This is used to
			// tell the route parser how url parameters should be default valued
			if(array_key_exists('defaults', $simpleRoute)) {
				$router->defaults($simpleRoute['defaults']);
			}
		}
	}

	/**
	 * For a given name and url restful routes are created:
	 *  - index
	 *  - show
	 *  - new
	 *  - create
	 *  - update
	 *  - destroy
	 *
	 * @param array $routes
	 */
	private function processResources($routes)
	{
		// declaration of all restful actions
		$actions = array(
			array('name' => 'index', 'verb' => 'GET', 'on-collection' => true),
			array('name' => 'show', 'verb' => 'GET'),
			array('name' => 'create', 'verb' => 'POST', 'on-collection' => true),
			array('name' => 'update', 'verb' => 'PUT'),
			array('name' => 'destroy', 'verb' => 'DELETE'),
		);

		$resources = isset($routes['resources']) ? $routes['resources'] : array();
		foreach ($resources as $resource => $config) {

			// the url parameter used as id to the resource
			foreach($actions as $action) {
				$url = $config['url'];
				$method = $action['name'];
				$verb = isset($action['verb']) ? strtoupper($action['verb']) : 'GET';
				$collectionAction = isset($action['on-collection']) ? $action['on-collection'] : false;
				if (!$collectionAction) {
					$url = $url . '/{id}';
				}
				if (isset($action['url-postfix'])) {
					$url = $url . '/' . $action['url-postfix'];
				}

				$controller = $resource;

				$controllerName = $this->buildControllerName($controller);
				$actionName = $this->buildActionName($method);

				$routeName = $this->appName . '.' . strtolower($resource) . '.' . strtolower($method);

				$this->router->create($routeName, $url)->method($verb)->action(
					new RouteActionHandler($this->container, $controllerName, $actionName)
				);
			}
		}
	}

	/**
	 * Based on a given route name the controller name is generated
	 * @param string $controller
	 * @return string
	 */
	private function buildControllerName($controller)
	{
		return $this->underScoreToCamelCase(ucfirst($controller)) . 'Controller';
	}

	/**
	 * Based on the action part of the route name the controller method name is generated
	 * @param string $action
	 * @return string
	 */
	private function buildActionName($action) {
		return $this->underScoreToCamelCase($action);
	}

	/**
	 * Underscored strings are converted to camel case strings
	 * @param string $str
	 * @return string
	 */
	private function underScoreToCamelCase($str) {
		$pattern = "/_[a-z]?/";
		return preg_replace_callback(
			$pattern,
			function ($matches) {
				return strtoupper(ltrim($matches[0], "_"));
			},
			$str);
	}
}
