<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
