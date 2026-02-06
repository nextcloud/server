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
 * @since 34.0.0
 */
enum ConflictResolutionMode {
	/**
	 * Wait for the row to be unlocked.
	 */
	case Ordinary;
	/**
	 * Skip the row if it is locked.
	 */
	case SkipLocked;
}
