<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\RateLimiting;

use OC\Security\Normalizer\IpAddress;
use OC\Security\RateLimiting\Backend\IBackend;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OCP\IUser;
use OCP\Security\RateLimiting\ILimiter;

class Limiter implements ILimiter {
	public function __construct(
		private IBackend $backend,
	) {
	}

	/**
	 * @param int $period in seconds
	 * @throws RateLimitExceededException
	 */
	private function register(
		string $methodIdentifier,
		string $userIdentifier,
		int $period,
		int $limit,
	): int {
		$existingAttempts = $this->backend->getAttempts($methodIdentifier, $userIdentifier);
		if ($existingAttempts >= $limit) {
			throw new RateLimitExceededException();
		}

		$this->backend->registerAttempt($methodIdentifier, $userIdentifier, $period);

		return $existingAttempts;
	}

	/**
	 * Registers attempt for an anonymous request
	 *
	 * @param int $anonPeriod in seconds
	 * @throws RateLimitExceededException
	 */
	public function registerAnonRequest(
		string $identifier,
		int $anonLimit,
		int $anonPeriod,
		string $ip,
	): int {
		$ipSubnet = (new IpAddress($ip))->getSubnet();

		$anonHashIdentifier = hash('sha512', 'anon::' . $identifier . $ipSubnet);
		return $this->register($identifier, $anonHashIdentifier, $anonPeriod, $anonLimit);
	}

	/**
	 * Registers attempt for an authenticated request
	 *
	 * @param int $userPeriod in seconds
	 * @throws RateLimitExceededException
	 */
	public function registerUserRequest(
		string $identifier,
		int $userLimit,
		int $userPeriod,
		IUser $user,
	): int {
		$userHashIdentifier = hash('sha512', 'user::' . $identifier . $user->getUID());
		return $this->register($identifier, $userHashIdentifier, $userPeriod, $userLimit);
	}
}
