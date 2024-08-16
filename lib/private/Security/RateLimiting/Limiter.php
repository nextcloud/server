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
	): void {
		$existingAttempts = $this->backend->getAttempts($methodIdentifier, $userIdentifier);
		if ($existingAttempts >= $limit) {
			throw new RateLimitExceededException();
		}

		$this->backend->registerAttempt($methodIdentifier, $userIdentifier, $period);
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
	): void {
		$ipSubnet = (new IpAddress($ip))->getSubnet();

		$anonHashIdentifier = hash('sha512', 'anon::' . $identifier . $ipSubnet);
		$this->register($identifier, $anonHashIdentifier, $anonPeriod, $anonLimit);
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
	): void {
		$userHashIdentifier = hash('sha512', 'user::' . $identifier . $user->getUID());
		$this->register($identifier, $userHashIdentifier, $userPeriod, $userLimit);
	}
}
