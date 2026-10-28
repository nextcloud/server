<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Keeps the successor of a rotated remember-me token available for a short period, so requests that were
 * sent before the rotating response arrived can continue with it instead of losing the remembered login.
 *
 * A successor is only handed out to a request that sends the same cookies as the rotating one did. Stored in
 * the distributed cache, so it covers every server; with only a local cache configured it works per server, and
 * without any memcache it has no effect.
 */
class RememberMeTokenGrace {
	/**
	 * Seconds a rotated remember-me token stays usable. Matches the time a browser needs for the requests it
	 * started before the rotating response arrived.
	 */
	public const GRACE_PERIOD = 60;

	private ?ICache $cache = null;

	public function __construct(
		private ICacheFactory $cacheFactory,
		private ICrypto $crypto,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Remember which remember-me token and session id replaced the cookies of the rotating request
	 */
	public function remember(
		#[\SensitiveParameter]
		string $rotatedToken,
		#[\SensitiveParameter]
		string $rotatedSessionId,
		#[\SensitiveParameter]
		string $successorToken,
		#[\SensitiveParameter]
		string $successorSessionId,
	): void {
		try {
			$successor = json_encode(['token' => $successorToken, 'sessionId' => $successorSessionId], JSON_THROW_ON_ERROR);
			$stored = $this->getCache()->set(
				$this->cacheKey($rotatedToken, $rotatedSessionId),
				// Only a request that knows the rotated token may read its successor
				$this->crypto->encrypt($successor, $rotatedToken),
				self::GRACE_PERIOD,
			);
		} catch (\Exception $e) {
			// A cookie that can't be encoded or an unreachable cache costs the grace period, it must not fail the login
			$this->logger->warning('Could not remember the successor of a rotated remember-me token', [
				'app' => 'core',
				'exception' => $e,
			]);
			return;
		}
		if (!$stored) {
			$this->logger->warning('The cache refused the successor of a rotated remember-me token', [
				'app' => 'core',
			]);
		}
	}

	/**
	 * @return ?array{token: string, sessionId: string} What replaced those cookies within the grace period
	 */
	public function resolve(
		#[\SensitiveParameter]
		string $rotatedToken,
		#[\SensitiveParameter]
		string $rotatedSessionId,
	): ?array {
		try {
			$successor = $this->getCache()->get($this->cacheKey($rotatedToken, $rotatedSessionId));
			if (!is_string($successor)) {
				return null;
			}
			$decoded = json_decode($this->crypto->decrypt($successor, $rotatedToken), true, flags: JSON_THROW_ON_ERROR);
		} catch (\Exception) {
			// An unreachable cache or a value this request cannot read means there is no successor
			return null;
		}
		if (!is_array($decoded) || !is_string($decoded['token'] ?? null) || !is_string($decoded['sessionId'] ?? null)) {
			return null;
		}
		return ['token' => $decoded['token'], 'sessionId' => $decoded['sessionId']];
	}

	private function getCache(): ICache {
		return $this->cache ??= $this->cacheFactory->createDistributed('rememberme-rotated');
	}

	private function cacheKey(
		#[\SensitiveParameter]
		string $rotatedToken,
		#[\SensitiveParameter]
		string $rotatedSessionId,
	): string {
		return bin2hex($this->crypto->calculateHMAC($rotatedToken . "\0" . $rotatedSessionId));
	}
}
