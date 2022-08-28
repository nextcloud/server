<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
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
namespace OCP\Group\Backend;

use OCP\IUser;

/**
 * @since 27.0.0
 */
interface ISearchableGroupBackend {
	/**
	 * @brief Get a list of users matching the given search parameters.
	 *
	 * Implementations of this method should return lazy evaluated user objects and
	 * preload if possible the display name.
	 *
	 * <code>
	 * $users = $groupBackend->searchInGroup('admin', 'John', 10, 0);
	 * </code>
	 *
	 * @param string $gid    The group id of the user we want to search
	 * @param string $search The part of the display name or user id of the users we
	 *                       want to search. This can be empty to get all the users.
	 * @param int $limit     The limit of results
	 * @param int $offset    The offset of the results
	 * @return array<string,IUser> Users indexed by uid
	 * @since 27.0.0
	 */
	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array;
}
