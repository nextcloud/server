<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\RateLimiting;

use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\IUser;

/**
 * Programmatic rate limiter for web requests that are not handled by an app framework controller
 *
 * @see AnonRateLimit
 * @see UserRateLimit
 *
 * @since 28.0.0
 */
interface ILimiter {
	/**
	 * Registers attempt for an anonymous request
	 *
	 * @param string $identifier
	 * @param int $anonLimit
	 * @param int $anonPeriod in seconds
	 * @param string $ip
	 * @throws IRateLimitExceededException if limits are reached, which should cause a HTTP 429 response
	 * @since 28.0.0
	 *
	 */
	public function registerAnonRequest(string $identifier,
		int $anonLimit,
		int $anonPeriod,
		string $ip): void;

	/**
	 * Registers attempt for an authenticated request
	 *
	 * @param string $identifier
	 * @param int $userLimit
	 * @param int $userPeriod in seconds
	 * @param IUser $user the acting user
	 * @throws IRateLimitExceededException if limits are reached, which should cause a HTTP 429 response
	 * @since 28.0.0
	 *
	 */
	public function registerUserRequest(string $identifier,
		int $userLimit,
		int $userPeriod,
		IUser $user): void;
}
