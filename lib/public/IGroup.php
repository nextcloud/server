<?php
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
 * This interface makes it possible to interact with groups and abstract the
 * various group backends. You should use it directly in your application and
 * not subclass it.
 *
 * <code>
 * // Get the admin group
 * $adminGroup = $groupManager->get('admin');
 * </code>
 *
 * @since 8.0.0
 */
interface IGroup {
	/**
	 * @brief Get the group id of the group.
	 *
	 * The group id is a unique identifier for the group. If multiple group
	 * backend use the same group identifier, the users of the various backends
	 * will be considered to be in the same group.
	 *
	 * @since 8.0.0
	 */
	public function getGID(): string;

	/**
	 * @brief Get the display name of the group.
	 *
	 * Depending on the internal backend, this might be the same as @see getGID
	 *
	 * @since 12.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * @brief Set the group display name
	 *
	 * @param string $displayName
	 * @return bool
	 * @since 18.0.0
	 */
	public function setDisplayName(string $displayName): bool;

	/**
	 * @brief Get all the users in the group
	 *
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function getUsers(): array;

	/**
	 * @brief Check if a user is in the group.
	 *
	 * @since 8.0.0
	 */
	public function inGroup(IUser $user): bool;

	/**
	 * @brief Add a user to this group
	 *
	 * @since 8.0.0
	 */
	public function addUser(IUser $user): void;

	/**
	 * @brief Remove a user from the group
	 *
	 * @since 8.0.0
	 */
	public function removeUser(IUser $user): void;

	/**
	 * @brief Search for users in the group by userid or display name
	 *
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function searchUsers(string $search, ?int $limit = null, ?int $offset = null): array;

	/**
	 * @brief Count the number of users matching the search string
	 *
	 * @return int|false
	 * @since 8.0.0
	 */
	public function count(string $search = '');

	/**
	 * @brief Get the number of disabled users in the group
	 *
	 * @return int|false
	 * @since 14.0.0
	 */
	public function countDisabled();

	/**
	 * Search for users in the group by display name
	 *
	 * @return IUser[]
	 * @since 8.0.0
	 * @depreacted 25.0.0 Use searchUsers instead, the implementation is the same
	 */
	public function searchDisplayName(string $search, ?int $limit = null, ?int $offset = null): array;

	/**
	 * Get the names of the backends the group is connected to
	 *
	 * @return string[]
	 * @since 22.0.0
	 */
	public function getBackendNames(): array;

	/**
	 * @brief Delete the group
	 *
	 * This is not possible for the admin group.
	 *
	 * @since 8.0.0
	 */
	public function delete(): bool;

	/**
	 * @brief Check if the group is allowed to remove users
	 *
	 * @since 14.0.0
	 */
	public function canRemoveUser(): bool;

	/**
	 * @brief Check if the group is allowed to add users
	 *
	 * @since 14.0.0
	 */
	public function canAddUser(): bool;

	/**
	 * @brief Check if the group is hidden from collaboration
	 *
	 * @since 16.0.0
	 */
	public function hideFromCollaboration(): bool;
}
