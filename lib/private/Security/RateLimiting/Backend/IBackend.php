<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\RateLimiting\Backend;

/**
 * Interface IBackend defines a storage backend for the rate limiting data. It
 * should be noted that writing and reading rate limiting data is an expensive
 * operation and one should thus make sure to only use sufficient fast backends.
 *
 * @package OC\Security\RateLimiting\Backend
 */
interface IBackend {
	/**
	 * Gets the number of attempts for the specified method
	 *
	 * @param string $methodIdentifier Identifier for the method
	 * @param string $userIdentifier Identifier for the user
	 */
	public function getAttempts(
		string $methodIdentifier,
		string $userIdentifier,
	): int;

	/**
	 * Registers an attempt
	 *
	 * @param string $methodIdentifier Identifier for the method
	 * @param string $userIdentifier Identifier for the user
	 * @param int $period Period in seconds how long this attempt should be stored
	 */
	public function registerAttempt(
		string $methodIdentifier,
		string $userIdentifier,
		int $period,
	);
}
