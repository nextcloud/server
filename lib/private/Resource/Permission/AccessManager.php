<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Resource\Permission;

use InvalidArgumentException;
use NCU\Resource\Permission\AccessEntry;
use NCU\Resource\Permission\IAccessManager;
use NCU\Resource\Permission\IPermissionManager;
use NCU\Resource\ResourceReference;
use OCP\Actor\Actor;
use OCP\Cache\CappedMemoryCache;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Asks the resource types which access an actor has to resources.
 */
final class AccessManager implements IAccessManager {
	/** @var CappedMemoryCache<list<AccessEntry>> access entries by actor key and resource key */
	private CappedMemoryCache $cache;

	public function __construct(
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
		$this->cache = new CappedMemoryCache(2048);
	}

	#[\Override]
	public function getAccess(Actor $actor, array $resources): array {
		return array_filter($this->resolve($actor, $resources));
	}

	/**
	 * Get the access entries of the actor for every resource, the resources can be of different types.
	 *
	 * @param list<ResourceReference> $resources
	 * @return array<non-empty-string, ?list<AccessEntry>> keyed by {@see ResourceReference::key},
	 *                                                     an empty list if the actor has no access and null if the resource type failed
	 * @throws InvalidArgumentException if a resource type is not registered
	 */
	public function resolve(Actor $actor, array $resources): array {
		// Resolved lazily: the permission manager depends on this class.
		$permissionManager = $this->container->get(IPermissionManager::class);
		$actorKey = $actor->key();

		$results = [];
		/** @var array<non-empty-string, list<ResourceReference>> $misses */
		$misses = [];
		foreach ($resources as $resource) {
			if ($permissionManager->getResourceType($resource->type) === null) {
				throw new InvalidArgumentException('The resource type ' . $resource->type . ' is not registered');
			}

			$resourceKey = $resource->key();
			if (array_key_exists($resourceKey, $results)) {
				continue;
			}

			$cached = $this->cache->get($actorKey . "\0" . $resourceKey);
			$results[$resourceKey] = $cached;
			if ($cached === null) {
				$misses[$resource->type][] = $resource;
			}
		}

		foreach ($misses as $type => $typeResources) {
			$resourceType = $permissionManager->getResourceType($type);
			try {
				$access = $resourceType->getAccess($actor, $typeResources);
			} catch (Throwable $e) {
				// Not cached: the resource type may succeed on the next call.
				$this->logger->error('The resource type {type} failed to get the access, no access is assumed', ['type' => $type, 'exception' => $e]);
				continue;
			}

			foreach ($typeResources as $resource) {
				$resourceKey = $resource->key();
				$entries = array_values($access[$resourceKey] ?? []);
				$this->cache->set($actorKey . "\0" . $resourceKey, $entries);
				$results[$resourceKey] = $entries;
			}
		}

		return $results;
	}

	public function clearCache(): void {
		$this->cache->clear();
	}
}
