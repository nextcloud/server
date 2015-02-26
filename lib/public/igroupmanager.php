<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\Group:
 * - preAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preDelete(\OC\Group\Group $group)
 * - postDelete(\OC\Group\Group $group)
 * - preCreate(string $groupId)
 * - postCreate(\OC\Group\Group $group)
 *
 * @package OC\Group
 */
interface IGroupManager {
	/**
	 * @param \OCP\UserInterface $backend
	 */
	public function addBackend($backend);

	public function clearBackends();

	/**
	 * @param string $gid
	 * @return \OCP\IGroup
	 */
	public function get($gid);

	/**
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid);

	/**
	 * @param string $gid
	 * @return \OCP\IGroup
	 */
	public function createGroup($gid);

	/**
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\IGroup[]
	 */
	public function search($search, $limit = null, $offset = null);

	/**
	 * @param \OCP\IUser $user
	 * @return \OCP\IGroup[]
	 */
	public function getUserGroups($user);

	/**
	 * @param \OCP\IUser $user
	 * @return array with group names
	 */
	public function getUserGroupIds($user);

	/**
	 * get a list of all display names in a group
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of display names (value) and user ids (key)
	 */
	public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0);

	/**
	 * Checks if a userId is in the admin group
	 * @param string $userId
	 * @return bool if admin
	 */
	public function isAdmin($userId);

	/**
	 * Checks if a userId is in a group
	 * @param string $userId
	 * @param group $group
	 * @return bool if in group
	 */
	public function isInGroup($userId, $group);
}
