<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
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
 *
 */

namespace OCP\Security\Bruteforce;

/**
 * Class Throttler implements the bruteforce protection for security actions in
 * Nextcloud.
 *
 * It is working by logging invalid login attempts to the database and slowing
 * down all login attempts from the same subnet. The max delay is 30 seconds and
 * the starting delay are 200 milliseconds. (after the first failed login)
 *
 * This is based on Paragonie's AirBrake for Airship CMS. You can find the original
 * code at https://github.com/paragonie/airship/blob/7e5bad7e3c0fbbf324c11f963fd1f80e59762606/src/Engine/Security/AirBrake.php
 *
 * @package OC\Security\Bruteforce
 * @since 25.0.0
 */
interface IThrottler {
	/**
	 * @since 25.0.0
	 */
	public const MAX_DELAY = 25;

	/**
	 * @since 25.0.0
	 */
	public const MAX_DELAY_MS = 25000; // in milliseconds

	/**
	 * @since 25.0.0
	 */
	public const MAX_ATTEMPTS = 10;

	/**
	 * Register a failed attempt to bruteforce a security control
	 *
	 * @param string $action
	 * @param string $ip
	 * @param array $metadata Optional metadata logged to the database
	 * @since 25.0.0
	 */
	public function registerAttempt(string $action, string $ip, array $metadata = []): void;

	/**
	 * Get the throttling delay (in milliseconds)
	 *
	 * @param string $ip
	 * @param string $action optionally filter by action
	 * @param float $maxAgeHours
	 * @return int
	 * @since 25.0.0
	 */
	public function getAttempts(string $ip, string $action = '', float $maxAgeHours = 12): int;

	/**
	 * Get the throttling delay (in milliseconds)
	 *
	 * @param string $ip
	 * @param string $action optionally filter by action
	 * @return int
	 * @since 25.0.0
	 */
	public function getDelay(string $ip, string $action = ''): int;

	/**
	 * Reset the throttling delay for an IP address, action and metadata
	 *
	 * @param string $ip
	 * @param string $action
	 * @param array $metadata
	 * @since 25.0.0
	 */
	public function resetDelay(string $ip, string $action, array $metadata): void;

	/**
	 * Reset the throttling delay for an IP address
	 *
	 * @param string $ip
	 * @since 25.0.0
	 */
	public function resetDelayForIP(string $ip): void;

	/**
	 * Will sleep for the defined amount of time
	 *
	 * @param string $ip
	 * @param string $action optionally filter by action
	 * @return int the time spent sleeping
	 * @since 25.0.0
	 */
	public function sleepDelay(string $ip, string $action = ''): int;

	/**
	 * Will sleep for the defined amount of time unless maximum was reached in the last 30 minutes
	 * In this case a "429 Too Many Request" exception is thrown
	 *
	 * @param string $ip
	 * @param string $action optionally filter by action
	 * @return int the time spent sleeping
	 * @throws MaxDelayReached when reached the maximum
	 * @since 25.0.0
	 */
	public function sleepDelayOrThrowOnMax(string $ip, string $action = ''): int;
}
