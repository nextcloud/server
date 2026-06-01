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
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Public base class for AppFramework applications.
 *
 * Provides access to the app container and a convenience dispatch() method
 * for routing requests to controllers.
 *
 * Apps using the AppFramework typically extend this class to register
 * services and dispatch controller actions.
 *
 * @see https://docs.nextcloud.com/server/latest/developer_manual/app_development/bootstrap.html
 *
 * @since 6.0.0
 */
class App {
	/** @var IAppContainer */
	private $container;

	/**
	 * Returns the app namespace for the given app ID.
	 *
	 * @param string $appId the app ID
	 * @param string $topNamespace the namespace prefix to substitute for OCA\
	 * @return string the app namespace
	 * @since 8.0.0
	 * @deprecated 34.0.0 use IAppManager::getAppNamespace
	 */
	public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {
		return \OC\AppFramework\App::buildAppNamespace($appId, $topNamespace);
	}

	/**
	 * Creates or retrieves the app container for the given app.
	 *
	 * @param string $appName the app ID
	 * @param array $urlParams route parameters to make available in the request container
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
			$this->container = new \OC\AppFramework\DependencyInjection\DIContainer($appName, $urlParams);
		}
	}

	/**
	 * Returns the app container.
	 *
	 * @return IAppContainer
	 * @since 6.0.0
	 */
	public function getContainer(): IAppContainer {
		return $this->container;
	}

	/**
	 * Dispatches a controller action through the AppFramework runtime.
	 *
	 * This is a convenience wrapper around the internal AppFramework request
	 * execution for the current app container.
	 *
	 * @param string $controllerName controller service name or controller class basename
	 * @param string $methodName controller method to invoke
	 * @since 6.0.0
	 */
	public function dispatch(string $controllerName, string $methodName) {
		\OC\AppFramework\App::main($controllerName, $methodName, $this->container);
	}
}
