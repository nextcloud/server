<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Security;

/**
 * Store and retrieve credentials for external services
 *
 * @since 8.2.0
 */
interface ICredentialsManager {
	/**
	 * Store a set of credentials
	 *
	 * @param string $userId empty string for system-wide credentials
	 * @param string $identifier
	 * @param mixed $credentials
	 * @since 8.2.0
	 */
	public function store(string $userId, string $identifier, $credentials): void;

	/**
	 * Retrieve a set of credentials
	 *
	 * @param string $userId empty string for system-wide credentials
	 * @param string $identifier
	 * @return mixed
	 * @since 8.2.0
	 */
	public function retrieve(string $userId, string $identifier);

	/**
	 * Delete a set of credentials
	 *
	 * @param string $userId empty string for system-wide credentials
	 * @param string $identifier
	 * @return int rows removed
	 * @since 8.2.0
	 */
	public function delete(string $userId, string $identifier): int;

	/**
	 * Erase all credentials stored for a user
	 *
	 * @param string $userId
	 * @return int rows removed
	 * @since 8.2.0
	 */
	public function erase(string $userId): int;
}
