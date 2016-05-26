<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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

/**
 * Public interface of ownCloud for apps to use.
 * AppFramework/App class
 */

namespace OCP\AppFramework;
use OC\AppFramework\Routing\RouteConfig;


/**
 * Class App
 * @package OCP\AppFramework
 *
 * Any application must inherit this call - all controller instances to be used are
 * to be registered using IContainer::registerService
 * @since 6.0.0
 */
class App {


	/**
	 * Turns an app id into a namespace by convetion. The id is split at the
	 * underscores, all parts are camelcased and reassembled. e.g.:
	 * some_app_id -> OCA\SomeAppId
	 * @param string $appId the app id
	 * @param string $topNamespace the namespace which should be prepended to
	 * the transformed app id, defaults to OCA\
	 * @return string the starting namespace for the app
	 * @since 8.0.0
	 */
	public static function buildAppNamespace($appId, $topNamespace='OCA\\') {
		return \OC\AppFramework\App::buildAppNamespace($appId, $topNamespace);
	}


	/**
	 * @param array $urlParams an array with variables extracted from the routes
	 * @since 6.0.0
	 */
	public function __construct($appName, $urlParams = array()) {
		$this->container = new \OC\AppFramework\DependencyInjection\DIContainer($appName, $urlParams);
	}

	private $container;

	/**
	 * @return IAppContainer
	 * @since 6.0.0
	 */
	public function getContainer() {
		return $this->container;
	}

	/**
	 * This function is to be called to create single routes and restful routes based on the given $routes array.
	 *
	 * Example code in routes.php of tasks app (it will register two restful resources):
	 * $routes = array(
	 *		'resources' => array(
	 *		'lists' => array('url' => '/tasklists'),
	 *		'tasks' => array('url' => '/tasklists/{listId}/tasks')
	 *	)
	 *	);
	 *
	 * $a = new TasksApp();
	 * $a->registerRoutes($this, $routes);
	 *
	 * @param \OCP\Route\IRouter $router
	 * @param array $routes
	 * @since 6.0.0
	 */
	public function registerRoutes($router, $routes) {
		$routeConfig = new RouteConfig($this->container, $router, $routes);
		$routeConfig->register();
	}

	/**
	 * This function is called by the routing component to fire up the frameworks dispatch mechanism.
	 *
	 * Example code in routes.php of the task app:
	 * $this->create('tasks_index', '/')->get()->action(
	 *		function($params){
	 *			$app = new TaskApp($params);
	 *			$app->dispatch('PageController', 'index');
	 *		}
	 *	);
	 *
	 *
	 * Example for for TaskApp implementation:
	 * class TaskApp extends \OCP\AppFramework\App {
	 *
	 *		public function __construct($params){
	 *			parent::__construct('tasks', $params);
	 *
	 *			$this->getContainer()->registerService('PageController', function(IAppContainer $c){
	 *				$a = $c->query('API');
	 *				$r = $c->query('Request');
	 *				return new PageController($a, $r);
	 *			});
	 *		}
	 *	}
	 *
	 * @param string $controllerName the name of the controller under which it is
	 *                               stored in the DI container
	 * @param string $methodName the method that you want to call
	 * @since 6.0.0
	 */
	public function dispatch($controllerName, $methodName) {
		\OC\AppFramework\App::main($controllerName, $methodName, $this->container);
	}
}
