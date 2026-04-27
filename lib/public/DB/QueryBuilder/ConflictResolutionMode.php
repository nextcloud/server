<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\QueryBuilder;

/**
 * Conflict resolution mode for "FOR UPDATE" select queries.
 *
 * @since 32.0.7
 */
enum ConflictResolutionMode {
	/**
	 * Wait for the row to be unlocked.
	 *
	 * @since 32.0.7
	 */
	case Ordinary;
	/**
	 * Skip the row if it is locked.
	 *
	 * @since 32.0.7
	 */
	case SkipLocked;
}
