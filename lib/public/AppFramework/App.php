<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use OC\AppFramework\Routing\RouteConfig;
use OC\Route\Router;
use OC\ServerContainer;
use OCP\Route\IRouter;
use Psr\Log\LoggerInterface;

/**
 * Class App
 *
 * Any application must inherit this call - all controller instances to be used are
 * to be registered using IContainer::registerService
 * @since 6.0.0
 */
class App {
	/** @var IAppContainer */
	private $container;

	/**
	 * Turns an app id into a namespace by convention. The id is split at the
	 * underscores, all parts are CamelCased and reassembled. e.g.:
	 * some_app_id -> OCA\SomeAppId
	 * @param string $appId the app id
	 * @param string $topNamespace the namespace which should be prepended to
	 * the transformed app id, defaults to OCA\
	 * @return string the starting namespace for the app
	 * @since 8.0.0
	 */
	public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {
		return \OC\AppFramework\App::buildAppNamespace($appId, $topNamespace);
	}


	/**
	 * @param string $appName
	 * @param array $urlParams an array with variables extracted from the routes
	 * @since 6.0.0
	 */
	public function __construct(string $appName, array $urlParams = []) {
		$runIsSetupDirectly = \OC::$server->getConfig()->getSystemValueBool('debug')
			&& !ini_get('zend.exception_ignore_args');

		if ($runIsSetupDirectly) {
			$applicationClassName = get_class($this);
			$e = new \RuntimeException('App class ' . $applicationClassName . ' is not setup via query() but directly');
			$setUpViaQuery = false;

			$classNameParts = explode('\\', trim($applicationClassName, '\\'));

			foreach ($e->getTrace() as $step) {
				if (isset($step['class'], $step['function'], $step['args'][0]) &&
					$step['class'] === ServerContainer::class &&
					$step['function'] === 'query' &&
					$step['args'][0] === $applicationClassName) {
					$setUpViaQuery = true;
					break;
				} elseif (isset($step['class'], $step['function'], $step['args'][0]) &&
					$step['class'] === ServerContainer::class &&
					$step['function'] === 'getAppContainer' &&
					$step['args'][1] === $classNameParts[1]) {
					$setUpViaQuery = true;
					break;
				}
			}

			if (!$setUpViaQuery && $applicationClassName !== \OCP\AppFramework\App::class) {
				\OCP\Server::get(LoggerInterface::class)->error($e->getMessage(), [
					'app' => $appName,
					'exception' => $e,
				]);
			}
		}

		try {
			$this->container = \OC::$server->getRegisteredAppContainer($appName);
		} catch (QueryException $e) {
			$this->container = new \OC\AppFramework\DependencyInjection\DIContainer($appName, $urlParams);
		}
	}

	/**
	 * @return IAppContainer
	 * @since 6.0.0
	 */
	public function getContainer(): IAppContainer {
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
	 * @suppress PhanAccessMethodInternal
	 * @deprecated 20.0.0 Just return an array from your routes.php
	 */
	public function registerRoutes(IRouter $router, array $routes) {
		if (!($router instanceof Router)) {
			throw new \RuntimeException('Can only setup routes with real router');
		}

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
	public function dispatch(string $controllerName, string $methodName) {
		\OC\AppFramework\App::main($controllerName, $methodName, $this->container);
	}
}
