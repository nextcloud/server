<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace NCU\User;

interface IUserBackend {
	/**
	 * Get backend ID
	 *
	 * @since 36.0.0
	 */
	public function getId(): int;

	/**
	 * Get backend display name
	 *
	 * @since 36.0.0
	 */
	public function getName(): string;

	/**
	 * Create a new user in backend
	 *
	 * @return int|false User ID if creation is supported, false otherwise
	 * @since 36.0.0
	 */
	public function createUser(): int|false;

	/**
	 * Update user password
	 *
	 * @return bool True if update supported, false otherwise
	 * @since 36.0.0
	 */
	public function setPassword(int $accountId, string $hashedPassword): bool;

	/**
	 * Update user display name in backend
	 *
	 * @return bool True if update supported, false otherwise
	 * @since 36.0.0
	 */
	public function setDisplayName(): bool;

	/**
	 * Count users in backend
	 *
	 * Count all users, including not synchronized users
	 *
	 * @return int|false Number of users if count is supported, false otherwise
	 * @since 36.0.0
	 */
	public function countUsers(): int|false;
}
