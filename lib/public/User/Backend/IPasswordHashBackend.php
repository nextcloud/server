<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

/**
 * @since 30.0.0
 */
interface IPasswordHashBackend {
	/**
	 * @since 30.0.0
	 */
	public function getPasswordHash(string $userId): ?string;

	/**
	 * @since 30.0.0
	 */
	public function setPasswordHash(string $userId, string $passwordHash): bool;
}
