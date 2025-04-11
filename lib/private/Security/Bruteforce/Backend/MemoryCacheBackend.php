<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Bruteforce\Backend;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;

class MemoryCacheBackend implements IBackend {
	private ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private ITimeFactory $timeFactory,
	) {
		$this->cache = $cacheFactory->createDistributed(self::class);
	}

	private function hash(
		null|string|array $data,
	): ?string {
		if ($data === null) {
			return null;
		}
		if (!is_string($data)) {
			$data = json_encode($data);
		}
		return hash('sha1', $data);
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
		string $ipSubnet,
		int $maxAgeTimestamp,
		?string $action = null,
		?array $metadata = null,
	): int {
		$identifier = $this->hash($ipSubnet);
		$actionHash = $this->hash($action);
		$metadataHash = $this->hash($metadata);
		$existingAttempts = $this->getExistingAttempts($identifier);

		$count = 0;
		foreach ($existingAttempts as $info) {
			[$occurredTime, $attemptAction, $attemptMetadata] = explode('#', $info, 3);
			if ($action === null || $attemptAction === $actionHash) {
				if ($metadata === null || $attemptMetadata === $metadataHash) {
					if ($occurredTime > $maxAgeTimestamp) {
						$count++;
					}
				}
			}
		}

		return $count;
	}

	/**
	 * {@inheritDoc}
	 */
	public function resetAttempts(
		string $ipSubnet,
		?string $action = null,
		?array $metadata = null,
	): void {
		$identifier = $this->hash($ipSubnet);
		if ($action === null) {
			$this->cache->remove($identifier);
		} else {
			$actionHash = $this->hash($action);
			$metadataHash = $this->hash($metadata);
			$existingAttempts = $this->getExistingAttempts($identifier);
			$maxAgeTimestamp = $this->timeFactory->getTime() - 12 * 3600;

			foreach ($existingAttempts as $key => $info) {
				[$occurredTime, $attemptAction, $attemptMetadata] = explode('#', $info, 3);
				if ($attemptAction === $actionHash) {
					if ($metadata === null || $attemptMetadata === $metadataHash) {
						unset($existingAttempts[$key]);
					} elseif ($occurredTime < $maxAgeTimestamp) {
						unset($existingAttempts[$key]);
					}
				}
			}

			if (!empty($existingAttempts)) {
				$this->cache->set($identifier, json_encode($existingAttempts), 12 * 3600);
			} else {
				$this->cache->remove($identifier);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerAttempt(
		string $ip,
		string $ipSubnet,
		int $timestamp,
		string $action,
		array $metadata = [],
	): void {
		$identifier = $this->hash($ipSubnet);
		$existingAttempts = $this->getExistingAttempts($identifier);
		$maxAgeTimestamp = $this->timeFactory->getTime() - 12 * 3600;

		// Unset all attempts that are already expired
		foreach ($existingAttempts as $key => $info) {
			[$occurredTime,] = explode('#', $info, 3);
			if ($occurredTime < $maxAgeTimestamp) {
				unset($existingAttempts[$key]);
			}
		}
		$existingAttempts = array_values($existingAttempts);

		// Store the new attempt
		$existingAttempts[] = $timestamp . '#' . $this->hash($action) . '#' . $this->hash($metadata);

		$this->cache->set($identifier, json_encode($existingAttempts), 12 * 3600);
	}
}
