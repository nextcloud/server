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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RouteCollection;

class CachingRouter extends Router {
	protected ICache $cache;

	protected array $legacyCreatedRoutes = [];

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
		$dumper = new CompiledUrlMatcherDumper($collection);
		return $dumper->getCompiledRoutes();
	}

	/**
	 * Find the route matching $url
	 *
	 * @param string $url The url to find
	 * @throws \Exception
	 * @return array
	 */
	public function findMatchingRoute(string $url): array {
		$this->eventLogger->start('cacheroute:match', 'Match route');
		$key = $this->context->getHost() . '#' . $this->context->getBaseUrl() . '#rootCollection';
		$cachedRoutes = $this->cache->get($key);
		if (!$cachedRoutes) {
			parent::loadRoutes();
			$cachedRoutes = $this->serializeRouteCollection($this->root);
			$this->cache->set($key, $cachedRoutes, ($this->config->getSystemValueBool('debug') ? 3 : 3600));
		}
		$matcher = new CompiledUrlMatcher($cachedRoutes, $this->context);
		$this->eventLogger->start('cacheroute:url:match', 'Symfony URL match call');
		try {
			$parameters = $matcher->match($url);
		} catch (ResourceNotFoundException $e) {
			if (!str_ends_with($url, '/')) {
				// We allow links to apps/files? for backwards compatibility reasons
				// However, since Symfony does not allow empty route names, the route
				// we need to match is '/', so we need to append the '/' here.
				try {
					$parameters = $matcher->match($url . '/');
				} catch (ResourceNotFoundException $newException) {
					// If we still didn't match a route, we throw the original exception
					throw $e;
				}
			} else {
				throw $e;
			}
		}
		$this->eventLogger->end('cacheroute:url:match');

		$this->eventLogger->end('cacheroute:match');
		return $parameters;
	}

	/**
	 * @param array{action:mixed, ...} $parameters
	 */
	protected function callLegacyActionRoute(array $parameters): void {
		/*
		 * Closures cannot be serialized to cache, so for legacy routes calling an action we have to include the routes.php file again
		 */
		$app = $parameters['app'];
		$this->useCollection($app);
		parent::requireRouteFile($parameters['route-file'], $app);
		$collection = $this->getCollection($app);
		$parameters['action'] = $collection->get($parameters['_route'])?->getDefault('action');
		parent::callLegacyActionRoute($parameters);
	}

	/**
	 * Create a \OC\Route\Route.
	 * Deprecated
	 *
	 * @param string $name Name of the route to create.
	 * @param string $pattern The pattern to match
	 * @param array $defaults An array of default parameter values
	 * @param array $requirements An array of requirements for parameters (regexes)
	 */
	public function create($name, $pattern, array $defaults = [], array $requirements = []): Route {
		$this->legacyCreatedRoutes[] = $name;
		return parent::create($name, $pattern, $defaults, $requirements);
	}

	/**
	 * Require a routes.php file
	 */
	protected function requireRouteFile(string $file, string $appName): void {
		$this->legacyCreatedRoutes = [];
		parent::requireRouteFile($file, $appName);
		foreach ($this->legacyCreatedRoutes as $routeName) {
			$route = $this->collection?->get($routeName);
			if ($route === null) {
				/* Should never happen */
				throw new \Exception("Could not find route $routeName");
			}
			if ($route->hasDefault('action')) {
				$route->setDefault('route-file', $file);
				$route->setDefault('app', $appName);
			}
		}
	}
}
