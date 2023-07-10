<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
		$this->cache = $cacheFactory->createDistributed(__CLASS__);
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
