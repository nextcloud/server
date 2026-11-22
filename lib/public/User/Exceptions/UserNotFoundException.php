<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Exceptions;

use OCP\AppFramework\Attribute\Catchable;
use OCP\AppFramework\Attribute\Throwable;

/**
 * The class UserNotFoundException is thrown when no user is found.
 *
 * @since 35.0.0
 */
#[Throwable(since: '35.0.0')]
#[Catchable(since: '35.0.0')]
class UserNotFoundException extends \Exception {
	/**
	 * Return a UserNotFoundException with a standard error message.
	 *
	 * @since 35.0.0
	 */
	public static function createForUser(string $userId, ?\Exception $previous = null): self {
		return new UserNotFoundException('User with ' . $userId . ' not found.', previous: $previous);
	}
}
