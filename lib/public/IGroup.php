<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP;

/**
 * Interface IGroup
 *
 * @since 8.0.0
 */
interface IGroup {
	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function getGID(): string;

	/**
	 * Returns the group display name
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * Set the group display name
	 *
	 * @param string $displayName
	 * @return bool
	 * @since 18.0.0
	 */
	public function setDisplayName(string $displayName): bool;

	/**
	 * get all users in the group
	 *
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function getUsers(): array;

	/**
	 * check if a user is in the group
	 *
	 * @param IUser $user
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function inGroup(IUser $user): bool;

	/**
	 * add a user to the group
	 *
	 * @param IUser $user
	 *
	 * @since 8.0.0
	 */
	public function addUser(IUser $user): void;

	/**
	 * Remove a user from the group
	 *
	 * @param IUser $user
	 *
	 * @since 8.0.0
	 */
	public function removeUser(IUser $user): void;

	/**
	 * search for users in the group by userid
	 *
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 *
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function searchUsers(string $search, ?int $limit = null, ?int $offset = null): array;

	/**
	 * returns the number of users matching the search string
	 *
	 * @param string $search
	 * @return int|bool
	 * @since 8.0.0
	 */
	public function count(string $search = ''): int|bool;

	/**
	 * returns the number of disabled users
	 *
	 * @return int|bool
	 * @since 14.0.0
	 */
	public function countDisabled(): int|bool;

	/**
	 * Search for users in the group by displayname
	 *
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 *
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function searchDisplayName(string $search, int $limit = null, int $offset = null): array;

	/**
	 * Get the names of the backends the group is connected to
	 *
	 * @return string[]
	 * @since 22.0.0
	 */
	public function getBackendNames(): array;

	/**
	 * Delete the group
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function delete(): bool;

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function canRemoveUser(): bool;

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function canAddUser(): bool;

	/**
	 * @return bool
	 * @since 16.0.0
	 */
	public function hideFromCollaboration(): bool;
}
