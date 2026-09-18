<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Utility;

use OCP\AppFramework\Utility\IPersistentServiceInvalidator;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IMemcache;

class PersistentServiceInvalidator implements IPersistentServiceInvalidator {
	private const CACHE_PREFIX = 'persistent_service_gen';

	private ?ICache $cache = null;

	public function __construct(
		private ICacheFactory $cacheFactory,
	) {
	}

	private function getCache(): ICache {
		return $this->cache ??= $this->cacheFactory->createDistributed(self::CACHE_PREFIX);
	}

	#[\Override]
	public function invalidate(string|PersistentServiceGroup $group): void {
		$key = $group instanceof PersistentServiceGroup ? $group->value : $group;
		$cache = $this->getCache();
		if ($cache instanceof IMemcache) {
			$cache->inc($key);
			return;
		}
		// Every current ICacheFactory::createDistributed() backend implements IMemcache, so this
		// non-atomic path is currently unreachable; a lost increment here would still be caught
		// on the next invalidation since generations are compared for equality, not counted.
		$cache->set($key, ((int)$cache->get($key)) + 1);
	}

	/**
	 * @internal used by {@see SimpleContainer} to check whether a persisted instance is still valid
	 */
	public function getGeneration(string|PersistentServiceGroup $group): int {
		$key = $group instanceof PersistentServiceGroup ? $group->value : $group;
		return (int)$this->getCache()->get($key);
	}
}
