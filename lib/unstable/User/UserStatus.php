<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace NCU\User;

/**
 * User account statuses
 *
 * @since 36.0.0
 */
enum UserStatus: int {
	/**
	 * Active account
	 *
	 * @since 36.0.0
	 */
	case ENABLED = 1;

	/**
	 * Inactive account
	 *
	 * @since 36.0.0
	 */
	case DISABLED = 2;
}
