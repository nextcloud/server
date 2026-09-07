<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Utility;

use OCP\AppFramework\Utility\IPersistentServiceInvalidator;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\ICacheFactory;
use OCP\IMemcache;

class PersistentServiceInvalidator implements IPersistentServiceInvalidator {
	private const CACHE_PREFIX = 'persistent_service_gen';

	public function __construct(
		private ICacheFactory $cacheFactory,
	) {
	}

	#[\Override]
	public function invalidate(string|PersistentServiceGroup $group): void {
		$key = $group instanceof PersistentServiceGroup ? $group->value : $group;
		$cache = $this->cacheFactory->createDistributed(self::CACHE_PREFIX);
		if ($cache instanceof IMemcache) {
			$cache->inc($key);
			return;
		}
		$cache->set($key, ((int)$cache->get($key)) + 1);
	}

	/**
	 * @internal used by {@see SimpleContainer} to check whether a persisted instance is still valid
	 */
	public function getGeneration(string|PersistentServiceGroup $group): int {
		$key = $group instanceof PersistentServiceGroup ? $group->value : $group;
		return (int)$this->cacheFactory->createDistributed(self::CACHE_PREFIX)->get($key);
	}
}
