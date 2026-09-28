<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\RateLimiting\Backend;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;

/**
 * Class MemoryCacheBackend uses the configured distributed memory cache for storing
 * rate limiting data.
 *
 * @package OC\Security\RateLimiting\Backend
 */
class MemoryCacheBackend implements IBackend {
	private ICache $cache;

	public function __construct(
		private IConfig $config,
		ICacheFactory $cacheFactory,
		private ITimeFactory $timeFactory,
	) {
		$this->cache = $cacheFactory->createDistributed(self::class);
	}

	private function hash(
		string $methodIdentifier,
		string $userIdentifier,
	): string {
		return hash('sha512', $methodIdentifier . $userIdentifier);
	}

	private function getExistingAttempts(string $identifier): array {
		$cachedAttempts = $this->cache->get($identifier);
		if ($cachedAttempts === null) {
			return [];
		}

		$cachedAttempts = json_decode($cachedAttempts, true);
		if (\is_array($cachedAttempts)) {
			return $cachedAttempts;
		}

		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttempts(
		string $methodIdentifier,
		string $userIdentifier,
	): int {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		$existingAttempts = $this->getExistingAttempts($identifier);

		$count = 0;
		$currentTime = $this->timeFactory->getTime();
		foreach ($existingAttempts as $expirationTime) {
			if ($expirationTime > $currentTime) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(
		string $methodIdentifier,
		string $userIdentifier,
		int $period,
	): void {
		$identifier = $this->hash($methodIdentifier, $userIdentifier);
		$existingAttempts = $this->getExistingAttempts($identifier);
		$currentTime = $this->timeFactory->getTime();

		// Unset all attempts that are already expired
		foreach ($existingAttempts as $key => $expirationTime) {
			if ($expirationTime < $currentTime) {
				unset($existingAttempts[$key]);
			}
		}
		$existingAttempts = array_values($existingAttempts);

		// Store the new attempt
		$existingAttempts[] = (string)($currentTime + $period);

		if (!$this->config->getSystemValueBool('ratelimit.protection.enabled', true)) {
			return;
		}

		$this->cache->set($identifier, json_encode($existingAttempts));
	}
}
