<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Route;

use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

class CachingRouter extends Router {
	protected ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		LoggerInterface $logger,
		IRequest $request,
		IConfig $config,
		IEventLogger $eventLogger,
		ContainerInterface $container,
		IAppManager $appManager,
	) {
		$this->cache = $cacheFactory->createLocal('route');
		parent::__construct($logger, $request, $config, $eventLogger, $container, $appManager);
	}

	/**
	 * Generate url based on $name and $parameters
	 *
	 * @param string $name Name of the route to use.
	 * @param array $parameters Parameters for the route
	 * @param bool $absolute
	 * @return string
	 */
	public function generate($name, $parameters = [], $absolute = false) {
		asort($parameters);
		$key = $this->context->getHost() . '#' . $this->context->getBaseUrl() . $name . sha1(json_encode($parameters)) . (int)$absolute;
		$cachedKey = $this->cache->get($key);
		if ($cachedKey) {
			return $cachedKey;
		} else {
			$url = parent::generate($name, $parameters, $absolute);
			if ($url) {
				$this->cache->set($key, $url, 3600);
			}
			return $url;
		}
	}

	private function serializeRouteCollection(RouteCollection $collection): array {
		return array_map(
			fn (Route $route) => [$route->getPath(), $route->getDefaults(), $route->getRequirements(), $route->getOptions(), $route->getHost(), $route->getSchemes(), $route->getMethods(), $route->getCondition()],
			$collection->all(),
		);
	}

	private function unserializeRouteCollection(array $data): RouteCollection {
		$collection = new RouteCollection();
		foreach ($data as $name => $details) {
			$route = new Route(...$details);
			$collection->add($name, $route);
		}
		return $collection;
	}

	/**
	 * Loads the routes
	 *
	 * @param null|string $app
	 */
	public function loadRoutes($app = null): void {
		$this->eventLogger->start('cacheroute:load:' . $app, 'Loading Routes (using cache) for ' . $app);
		if (is_string($app)) {
			$app = $this->appManager->cleanAppId($app);
		}

		$requestedApp = $app;
		if ($this->loaded) {
			$this->eventLogger->end('cacheroute:load:' . $app);
			return;
		}
		if (is_null($app)) {
			$cachedRoutes = $this->cache->get('root:');
			if ($cachedRoutes) {
				$this->root = $this->unserializeRouteCollection($cachedRoutes);
				$this->loaded = true;
				$this->eventLogger->end('cacheroute:load:' . $app);
				return;
			}
		} else {
			if (isset($this->loadedApps[$app])) {
				$this->eventLogger->end('cacheroute:load:' . $app);
				return;
			}
			$cachedRoutes = $this->cache->get('root:' . $requestedApp);
			if ($cachedRoutes) {
				$this->root = $this->unserializeRouteCollection($cachedRoutes);
				$this->loadedApps[$app] = true;
				$this->eventLogger->end('cacheroute:load:' . $app);
				return;
			}
		}
		parent::loadRoutes($app);
		$this->cache->set('root:' . $requestedApp, $this->serializeRouteCollection($this->root), 3600);
		$this->eventLogger->end('cacheroute:load:' . $app);
	}
}
