<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
 * @since 8.0.0
 */
interface IGroupManager {
	/**
	 * Checks whether a given backend is used
	 *
	 * @param string $backendClass Full classname including complete namespace
	 * @return bool
	 * @since 8.1.0
	 */
	public function isBackendUsed($backendClass);

	/**
	 * @param \OCP\GroupInterface $backend
	 * @since 8.0.0
	 */
	public function addBackend($backend);

	/**
	 * @since 8.0.0
	 */
	public function clearBackends();

	/**
	 * Get the active backends
	 * @return \OCP\GroupInterface[]
	 * @since 13.0.0
	 */
	public function getBackends();

	/**
	 * @param string $gid
	 * @return \OCP\IGroup|null
	 * @since 8.0.0
	 */
	public function get($gid);

	/**
	 * @param string $gid
	 * @return bool
	 * @since 8.0.0
	 */
	public function groupExists($gid);

	/**
	 * @param string $gid
	 * @return \OCP\IGroup|null
	 * @since 8.0.0
	 */
	public function createGroup($gid);

	/**
	 * @param string $search
	 * @param ?int $limit
	 * @param ?int $offset
	 * @return \OCP\IGroup[]
	 * @since 8.0.0
	 */
	public function search(string $search, ?int $limit = null, ?int $offset = 0);

	/**
	 * @param \OCP\IUser|null $user
	 * @return \OCP\IGroup[]
	 * @since 8.0.0
	 */
	public function getUserGroups(?IUser $user = null);

	/**
	 * @param \OCP\IUser $user
	 * @return string[] with group names
	 * @since 8.0.0
	 */
	public function getUserGroupIds(IUser $user): array;

	/**
	 * get a list of all display names in a group
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of display names (value) and user ids (key)
	 * @since 8.0.0
	 */
	public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0);

	/**
	 * Checks if a userId is in the admin group
	 * @param string $userId
	 * @return bool if admin
	 * @since 8.0.0
	 */
	public function isAdmin($userId);

	/**
	 * Checks if a userId is eligible to users administration delegation
	 * @param string $userId
	 * @return bool if delegated admin
	 * @since 30.0.0
	 */
	public function isDelegatedAdmin(string $userId): bool;

	/**
	 * Checks if a userId is in a group
	 * @param string $userId
	 * @param string $group
	 * @return bool if in group
	 * @since 8.0.0
	 */
	public function isInGroup($userId, $group);

	/**
	 * Get the display name of a Nextcloud group
	 *
	 * @param string $groupId
	 * @return ?string display name, if any
	 *
	 * @since 26.0.0
	 */
	public function getDisplayName(string $groupId): ?string;
}
