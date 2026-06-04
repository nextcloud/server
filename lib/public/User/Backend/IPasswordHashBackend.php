<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

use InvalidArgumentException;

/**
 * @since 30.0.0
 */
interface IPasswordHashBackend {
	/**
	 * @return ?string the password hash hashed by `\OCP\Security\IHasher::hash()`
	 * @since 30.0.0
	 */
	public function getPasswordHash(string $userId): ?string;

	/**
	 * @param string $passwordHash the password hash hashed by `\OCP\Security\IHasher::hash()`
	 * @throws InvalidArgumentException when `$passwordHash` is not a valid hash
	 * @since 30.0.0
	 */
	public function setPasswordHash(string $userId, string $passwordHash): bool;
}
