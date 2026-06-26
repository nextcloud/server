<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group;

use OCP\IGroup;
use OCP\IUser;

/**
 * @since 16.0.0
 */
interface ISubAdmin {
	/**
	 * add a SubAdmin
	 * @param IUser $user user to be SubAdmin
	 * @param IGroup $group group $user becomes subadmin of
	 *
	 * @since 16.0.0
	 */
	public function createSubAdmin(IUser $user, IGroup $group): void;

	/**
	 * delete a SubAdmin
	 * @param IUser $user the user that is the SubAdmin
	 * @param IGroup $group the group
	 *
	 * @since 16.0.0
	 */
	public function deleteSubAdmin(IUser $user, IGroup $group): void;

	/**
	 * get groups of a SubAdmin
	 * @param IUser $user the SubAdmin
	 * @return IGroup[]
	 *
	 * @since 16.0.0
	 */
	public function getSubAdminsGroups(IUser $user): array;

	/**
	 * get SubAdmins of a group
	 * @param IGroup $group the group
	 * @return IUser[]
	 *
	 * @since 16.0.0
	 */
	public function getGroupsSubAdmins(IGroup $group): array;

	/**
	 * checks if a user is a SubAdmin of a group
	 * @param IUser $user
	 * @param IGroup $group
	 * @return bool
	 *
	 * @since 16.0.0
	 */
	public function isSubAdminOfGroup(IUser $user, IGroup $group): bool;

	/**
	 * checks if a user is a SubAdmin
	 * @param IUser $user
	 * @return bool
	 *
	 * @since 16.0.0
	 */
	public function isSubAdmin(IUser $user): bool;

	/**
	 * checks if a user is a accessible by a subadmin
	 * @param IUser $subadmin
	 * @param IUser $user
	 * @return bool
	 *
	 * @since 16.0.0
	 */
	public function isUserAccessible(IUser $subadmin, IUser $user): bool;
}
