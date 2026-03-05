<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @param string $gid The group id of the user we want to search
	 * @param string $search The part of the display name or user id of the users we
	 *                       want to search. This can be empty to get all the users.
	 * @param int $limit The limit of results
	 * @param int $offset The offset of the results
	 * @return array<string,IUser> Users indexed by uid
	 * @since 27.0.0
	 */
	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array;
}
