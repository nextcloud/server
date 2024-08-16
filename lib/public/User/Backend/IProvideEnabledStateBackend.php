<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

/**
 * @since 28.0.0
 */
interface IProvideEnabledStateBackend {
	/**
	 * @since 28.0.0
	 *
	 * @param callable():bool $queryDatabaseValue A callable to query the enabled state from database
	 */
	public function isUserEnabled(string $uid, callable $queryDatabaseValue): bool;

	/**
	 * @since 28.0.0
	 *
	 * @param callable():bool $queryDatabaseValue A callable to query the enabled state from database
	 * @param callable(bool):void $setDatabaseValue A callable to set the enabled state in the database.
	 */
	public function setUserEnabled(string $uid, bool $enabled, callable $queryDatabaseValue, callable $setDatabaseValue): bool;

	/**
	 * Get the list of disabled users, to merge with the ones disabled in database
	 *
	 * @since 28.0.0
	 * @since 30.0.0 $search parameter added
	 *
	 * @return string[]
	 */
	public function getDisabledUserList(?int $limit = null, int $offset = 0, string $search = ''): array;
}
