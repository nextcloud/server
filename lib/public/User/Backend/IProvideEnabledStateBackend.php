<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	 * @since 28.0.7 $search parameter added
	 *
	 * @return string[]
	 */
	public function getDisabledUserList(?int $limit = null, int $offset = 0, string $search = ''): array;
}
