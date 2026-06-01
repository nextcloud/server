<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework;

use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Http\Dispatcher;
use OC\AppFramework\Http\Request;
use OC\Profiler\RoutingDataCollector;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\ICallbackResponse;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\QueryException;
use OCP\Diagnostics\IEventLogger;
use OCP\HintException;
use OCP\IRequest;
use OCP\Profiler\IProfiler;
use OCP\Server;

/**
 * Internal entry point for AppFramework request execution.
 *
 * Resolves controllers from the app container, runs the dispatcher and
 * middleware stack, and writes headers, cookies, and the response body to
 * the output layer.
 *
 * This class is internal to the server and not part of the public app API.
 * App code should use OCP\AppFramework\App instead.
 */
class App {
	/**
	 * Returns the app namespace for the given app ID, optionally rewritten to a
	 * different top-level namespace.
	 *
	 * @param string $appId the app ID
	 * @param string $topNamespace the namespace prefix to substitute for OCA\
	 * @return string the app namespace
	 * @deprecated 34.0.0 use IAppManager::getAppNamespace
	 */
	public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {
		$appManager = Server::get(IAppManager::class);
		$namespace = $appManager->getAppNamespace($appId);
		if ($topNamespace !== 'OCA\\') {
			return $topNamespace . substr($namespace, strlen('OCA\\'));
		}
		return $namespace;
	}

	/**
	 * Returns the app ID for the given class namespace.
	 *
	 * @param string $className the fully qualified class name
	 * @param string $topNamespace unused legacy namespace prefix parameter
	 * @return string|null the app ID, or null if the class does not belong to an app namespace
	 * @deprecated 34.0.0 use IAppManager::getAppFromNamespace
	 */
	public static function getAppIdForClass(string $className, string $topNamespace = 'OCA\\'): ?string {
		return Server::get(IAppManager::class)->getAppFromNamespace($className);
	}

	/**
	 * Executes an AppFramework controller action and emits the HTTP response.
	 *
	 * Resolves the controller from the app container, dispatches the requested
	 * method, and forwards headers, cookies, and body output to the output layer.
	 *
	 * The controller is first resolved as provided and then, if necessary, as
	 * <AppNamespace>\Controller\<ControllerName>.
	 *
	 * @param string $controllerName Controller service name or controller class name
	 * @param string $methodName Controller method to invoke
	 * @param DIContainer $container App dependency injection container
	 * @param array|null $urlParams Route parameters to inject into the request
	 * @throws HintException If a controller from a globally registered route
	 *                       belongs to an app that is not enabled
	 */
	public static function main(
		string $controllerName,
		string $methodName,
		DIContainer $container,
		?array $urlParams = null,
	): void {
		/** @var IProfiler $profiler */
		$profiler = $container->get(IProfiler::class);
		$eventLogger = $container->get(IEventLogger::class);
		// Disable profiler on the profiler UI
		$profiler->setEnabled($profiler->isEnabled() && !is_null($urlParams) && isset($urlParams['_route']) && !str_starts_with($urlParams['_route'], 'profiler.'));
		if ($profiler->isEnabled()) {
			Server::get(IEventLogger::class)->activate();
			$profiler->add(new RoutingDataCollector($container['appName'], $controllerName, $methodName));
		}

		$eventLogger->start('app:controller:params', 'Gather controller parameters');

		if (!is_null($urlParams)) {
			/** @var Request $request */
			$request = $container->get(IRequest::class);
			$request->setUrlParameters($urlParams);
		} elseif (isset($container['urlParams']) && !is_null($container['urlParams'])) {
			/** @var Request $request */
			$request = $container->get(IRequest::class);
			$request->setUrlParameters($container['urlParams']);
		}
		$appName = $container['appName'];

		$eventLogger->end('app:controller:params');

		$eventLogger->start('app:controller:load', 'Load app controller');

		// first try $controllerName then go for \OCA\AppName\Controller\$controllerName
		try {
			$controller = $container->get($controllerName);
		} catch (QueryException $e) {
			if (str_contains($controllerName, '\\Controller\\')) {
				// This is from a global registered app route that is not enabled.
				[/*OC(A)*/, $app, /* Controller/Name*/] = explode('\\', $controllerName, 3);
				throw new HintException('App ' . strtolower($app) . ' is not enabled');
			}

			if ($appName === 'core') {
				$appNameSpace = 'OC\\Core';
			} else {
				$appNameSpace = self::buildAppNamespace($appName);
			}
			$controllerName = $appNameSpace . '\\Controller\\' . $controllerName;
			$controller = $container->query($controllerName);
		}

		$eventLogger->end('app:controller:load');

		$eventLogger->start('app:controller:dispatcher', 'Initialize dispatcher and pre-middleware');

		// initialize the dispatcher and run all the middleware before the controller
		$dispatcher = $container->get(Dispatcher::class);

		$eventLogger->end('app:controller:dispatcher');

		$eventLogger->start('app:controller:run', 'Run app controller');

		[
			$httpHeaders,
			$responseHeaders,
			$responseCookies,
			$output,
			$response
		] = $dispatcher->dispatch($controller, $methodName);

		$eventLogger->end('app:controller:run');

		$io = $container[IOutput::class];

		if ($profiler->isEnabled()) {
			$eventLogger->end('runtime');
			$profile = $profiler->collect($container->get(IRequest::class), $response);
			$profiler->saveProfile($profile);
			$io->setHeader('X-Debug-Token:' . $profile->getToken());
			$io->setHeader('Server-Timing: token;desc="' . $profile->getToken() . '"');
		}

		if (!is_null($httpHeaders)) {
			$io->setHeader($httpHeaders);
		}

		foreach ($responseHeaders as $name => $value) {
			$io->setHeader($name . ': ' . $value);
		}

		foreach ($responseCookies as $name => $value) {
			$expireDate = null;
			if ($value['expireDate'] instanceof \DateTime) {
				$expireDate = $value['expireDate']->getTimestamp();
			}
			$sameSite = $value['sameSite'] ?? 'Lax';

			$io->setCookie(
				$name,
				$value['value'],
				$expireDate,
				$container->getServer()->getWebRoot(),
				null,
				$container->getServer()->get(IRequest::class)->getServerProtocol() === 'https',
				true,
				$sameSite
			);
		}

		/*
		 * Status 204 does not have a body and no Content Length
		 * Status 304 does not have a body and does not need a Content Length
		 * https://tools.ietf.org/html/rfc7230#section-3.3
		 * https://tools.ietf.org/html/rfc7230#section-3.3.2
		 */
		$emptyResponse = false;
		if (preg_match('/^HTTP\/\d\.\d (\d{3}) .*$/', $httpHeaders, $matches)) {
			$status = (int)$matches[1];
			if ($status === Http::STATUS_NO_CONTENT || $status === Http::STATUS_NOT_MODIFIED) {
				$emptyResponse = true;
			}
		}

		if (!$emptyResponse) {
			if ($response instanceof ICallbackResponse) {
				$response->callback($io);
			} elseif (!is_null($output)) {
				$io->setHeader('Content-Length: ' . strlen($output));
				$io->setOutput($output);
			}
		}
	}
}
