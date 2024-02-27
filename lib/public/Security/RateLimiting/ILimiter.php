<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		int    $anonLimit,
		int    $anonPeriod,
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
		int    $userLimit,
		int    $userPeriod,
		IUser  $user): void;
}
