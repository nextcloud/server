<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		$this->cache = $cacheFactory->createDistributed(__CLASS__);
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
		$existingAttempts[] = $timestamp . '#' . $this->hash($action) . '#' .  $this->hash($metadata);

		$this->cache->set($identifier, json_encode($existingAttempts), 12 * 3600);
	}
}
