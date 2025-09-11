<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use OC\AppFramework\Utility\SimpleContainer;
use OC\ServerContainer;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class App
 *
 * Any application must inherit this call - all controller instances to be used are
 * to be registered using IContainer::registerService
 * @since 6.0.0
 */
class App {
	private IAppContainer $container;

	/**
	 * @param string $appName
	 * @param array $urlParams an array with variables extracted from the routes
	 * @since 6.0.0
	 */
	public function __construct(
		private string $appName,
		array $urlParams = []
	) {
		$debugMode = Server::get(IConfig::class)->getSystemValueBool('debug');
		$excludeArgsFromTraces = ini_get('zend.exception_ignore_args');
		// Check if application class was setup incorrectly (and log it) when in debug mode
		if ($debugMode && !$excludeArgsFromTraces) {
			$this->checkIfSetupDirectly();
		}

		try {
			$this->container = \OC::$server->getRegisteredAppContainer($appName);
		} catch (QueryException $e) {
			$this->container = new \OC\AppFramework\DependencyInjection\DIContainer($appName, $urlParams);
		}
	}

	/**
	 * Turns an app id into a namespace by convention. The id is split at the
	 * underscores, all parts are CamelCased and reassembled. e.g.:
	 * some_app_id -> OCA\SomeAppId
	 * @param string $appId the app id
	 * @param string $topNamespace the namespace which should be prepended to
	 *                             the transformed app id, defaults to OCA\
	 * @return string the starting namespace for the app
	 * @since 8.0.0
	 */
	public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {
		return \OC\AppFramework\App::buildAppNamespace($appId, $topNamespace);
	}

	/**
	 * @return IAppContainer
	 * @since 6.0.0
	 */
	public function getContainer(): IAppContainer {
		return $this->container;
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
	public function dispatch(string $controllerName, string $methodName): void {
		\OC\AppFramework\App::main($controllerName, $methodName, $this->container);
	}

	/**
	 * Log an error when the application class was setup incorrectly.
	 */
	protected function checkIfSetupDirectly(): void {
		$appClassName = get_class($this);
		$setUpViaQuery = false;
		$classNameParts = explode('\\', trim($appClassName, '\\'));

		$e = new \RuntimeException('App class ' . $appClassName . ' is not setup via query() but directly');
		foreach ($e->getTrace() as $step) {
			if (isset($step['class'], $step['function'], $step['args'][0])
				&& $step['class'] === ServerContainer::class
				&& $step['function'] === 'query'
				&& $step['args'][0] === $appClassName) {
				$setUpViaQuery = true;
				break;
			} elseif (isset($step['class'], $step['function'], $step['args'][0])
				&& $step['class'] === ServerContainer::class
				&& $step['function'] === 'getAppContainer'
				&& $step['args'][1] === $classNameParts[1]) {
				$setUpViaQuery = true;
				break;
			} elseif (isset($step['class'], $step['function'], $step['args'][0])
				&& $step['class'] === SimpleContainer::class
				&& preg_match('/{closure:OC\\\\AppFramework\\\\Utility\\\\SimpleContainer::buildClass\\(\\):\\d+}/', $step['function'])
				&& $step['args'][0] === $this) {
				/* We are setup through a lazy ghost, fine */
				$setUpViaQuery = true;
				break;
			}
		}
		if (!$setUpViaQuery && $appClassName !== \OCP\AppFramework\App::class) {
			// log as an exception but continue on
			Server::get(LoggerInterface::class)->error($e->getMessage(), [
				'app' => $this->appName,
				'exception' => $e,
			]);
		}
	}
}
