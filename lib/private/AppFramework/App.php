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

/**
 * Entry point for every request in your app. You can consider this as your
 * public static void main() method
 *
 * Handles all the dependency injection, controllers and output flow
 */
class App {
	/** @var string[] */
	private static $nameSpaceCache = [];

	/**
	 * Turns an app id into a namespace by either reading the appinfo.xml's
	 * namespace tag or uppercasing the appid's first letter
	 * @param string $appId the app id
	 * @param string $topNamespace the namespace which should be prepended to
	 *                             the transformed app id, defaults to OCA\
	 * @return string the starting namespace for the app
	 */
	public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {
		// Hit the cache!
		if (isset(self::$nameSpaceCache[$appId])) {
			return $topNamespace . self::$nameSpaceCache[$appId];
		}

		$appInfo = \OCP\Server::get(IAppManager::class)->getAppInfo($appId);
		if (isset($appInfo['namespace'])) {
			self::$nameSpaceCache[$appId] = trim($appInfo['namespace']);
		} else {
			// if the tag is not found, fall back to uppercasing the first letter
			self::$nameSpaceCache[$appId] = ucfirst($appId);
		}

		return $topNamespace . self::$nameSpaceCache[$appId];
	}

	public static function getAppIdForClass(string $className, string $topNamespace = 'OCA\\'): ?string {
		if (!str_starts_with($className, $topNamespace)) {
			return null;
		}

		foreach (self::$nameSpaceCache as $appId => $namespace) {
			if (str_starts_with($className, $topNamespace . $namespace . '\\')) {
				return $appId;
			}
		}

		return null;
	}

	/**
	 * Shortcut for calling a controller method and printing the result
	 *
	 * @param string $controllerName the name of the controller under which it is
	 *                               stored in the DI container
	 * @param string $methodName the method that you want to call
	 * @param DIContainer $container an instance of a pimple container.
	 * @param array $urlParams list of URL parameters (optional)
	 * @throws HintException
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
			\OC::$server->get(IEventLogger::class)->activate();
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
				$container->getServer()->getRequest()->getServerProtocol() === 'https',
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
