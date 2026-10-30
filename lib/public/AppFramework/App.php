<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\AppFramework;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Utility\SimpleContainer;
use OC\ServerContainer;
use OCP\IConfig;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class App
 *
 * Any application must inherit this call - all controller instances to be used are
 * to be registered using IContainer::registerService
 * @since 6.0.0
 */
class App {
	private DIContainer $container;

	/**
	 * Turns an app id into a namespace by convention. The id is split at the
	 * underscores, all parts are CamelCased and reassembled. e.g.:
	 * some_app_id -> OCA\SomeAppId
	 * @param string $appId the app id
	 * @param string $topNamespace the namespace which should be prepended to
	 *                             the transformed app id, defaults to OCA\
	 * @return string the starting namespace for the app
	 * @since 8.0.0
	 * @deprecated 34.0.0 use IAppManager::getAppNamespace
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
		$runIsSetupDirectly = Server::get(IConfig::class)->getSystemValueBool('debug')
			&& !ini_get('zend.exception_ignore_args');

		if ($runIsSetupDirectly) {
			$applicationClassName = get_class($this);
			$e = new \RuntimeException('App class ' . $applicationClassName . ' is not setup via query() but directly');
			$setUpViaQuery = false;

			$classNameParts = explode('\\', trim($applicationClassName, '\\'));
			foreach ($e->getTrace() as $step) {
				if (isset($step['class'], $step['function'], $step['args'][0])
					&& $step['class'] === ServerContainer::class
					&& $step['function'] === 'query'
					&& $step['args'][0] === $applicationClassName) {
					$setUpViaQuery = true;
					break;
				} elseif (isset($step['class'], $step['function'], $step['args'][0])
					&& $step['class'] === ServerContainer::class
					&& $step['function'] === 'getAppContainer'
					&& $step['args'][0] === $classNameParts[0] . '\\' . $classNameParts[1]) {
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

			if (!$setUpViaQuery && $applicationClassName !== \OCP\AppFramework\App::class) {
				Server::get(LoggerInterface::class)->error($e->getMessage(), [
					'app' => $appName,
					'exception' => $e,
				]);
			}
		}

		try {
			$this->container = \OC::$server->getRegisteredAppContainer($appName);
		} catch (ContainerExceptionInterface $e) {
			$this->container = new DIContainer($appName, $urlParams);
		}
	}

	/**
	 * @return ContainerInterface
	 * @since 6.0.0
	 * @since 35.0.0 Typed as returning a ContainerInterface instead of the deprecated IAppContainer
	 */
	public function getContainer(): ContainerInterface {
		return $this->container;
	}

	/**
	 * This function is called by the routing component to fire up the frameworks dispatch mechanism.
	 *
	 * @param string $controllerName the name of the controller under which it is
	 *                               stored in the DI container
	 * @param string $methodName the method that you want to call
	 * @since 6.0.0
	 */
	public function dispatch(string $controllerName, string $methodName): void {
		\OC\AppFramework\App::main($controllerName, $methodName, $this->container);
	}
}
