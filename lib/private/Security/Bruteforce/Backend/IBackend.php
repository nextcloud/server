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

/**
 * Interface IBackend defines a storage backend for the bruteforce data. It
 * should be noted that writing and reading brute force data is an expensive
 * operation and one should thus make sure to only use sufficient fast backends.
 */
interface IBackend {
	/**
	 * Gets the number of attempts for the specified subnet (and further filters)
	 *
	 * @param string $ipSubnet
	 * @param int $maxAgeTimestamp
	 * @param ?string $action Optional action to further limit attempts
	 * @param ?array $metadata Optional metadata stored to further limit attempts (Only considered when $action is set)
	 * @return int
	 * @since 28.0.0
	 */
	public function getAttempts(
		string $ipSubnet,
		int $maxAgeTimestamp,
		?string $action = null,
		?array $metadata = null,
	): int;

	/**
	 * Reset the attempts for the specified subnet (and further filters)
	 *
	 * @param string $ipSubnet
	 * @param ?string $action Optional action to further limit attempts
	 * @param ?array $metadata Optional metadata stored to further limit attempts(Only considered when $action is set)
	 * @since 28.0.0
	 */
	public function resetAttempts(
		string $ipSubnet,
		?string $action = null,
		?array $metadata = null,
	): void;

	/**
	 * Register a failed attempt to bruteforce a security control
	 *
	 * @param string $ip
	 * @param string $ipSubnet
	 * @param int $timestamp
	 * @param string $action
	 * @param array $metadata Optional metadata stored to further limit attempts when getting
	 * @since 28.0.0
	 */
	public function registerAttempt(
		string $ip,
		string $ipSubnet,
		int $timestamp,
		string $action,
		array $metadata = [],
	): void;
}
