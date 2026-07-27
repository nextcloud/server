<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

use OCP\AppFramework\Attribute\Consumable;

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
#[Consumable(since: '8.0.0')]
interface IGroupManager {
	/**
	 * Checks whether a given backend is used
	 *
	 * @param class-string<GroupInterface> $backendClass Full classname including complete namespace
	 * @since 8.1.0
	 */
	public function isBackendUsed(string $backendClass): bool;

	/**
	 * @since 8.0.0
	 */
	public function addBackend(GroupInterface $backend): void;

	/**
	 * @since 34.0.0
	 */
	public function removeBackend(GroupInterface $backend): void;

	/**
	 * @since 8.0.0
	 */
	public function clearBackends(): void;

	/**
	 * Get the active backends
	 * @return list<\OCP\GroupInterface>
	 * @since 13.0.0
	 */
	public function getBackends(): array;

	/**
	 * @param string $gid
	 * @return \OCP\IGroup|null
	 * @since 8.0.0
	 */
	public function get(string $gid): ?IGroup;

	/**
	 * @param string $gid
	 * @return bool
	 * @since 8.0.0
	 */
	public function groupExists(string $gid): bool;

	/**
	 * @param string $gid
	 * @return \OCP\IGroup|null
	 * @since 8.0.0
	 */
	public function createGroup(string $gid): ?IGroup;

	/**
	 * @param string $search
	 * @param ?int $limit
	 * @param ?int $offset
	 * @return list<IGroup>
	 * @since 8.0.0
	 */
	public function search(string $search, ?int $limit = null, ?int $offset = 0): array;

	/**
	 * @param \OCP\IUser|null $user
	 * @return \OCP\IGroup[]
	 * @since 8.0.0
	 */
	public function getUserGroups(?IUser $user = null): array;

	/**
	 * @param \OCP\IUser $user
	 * @return list<string> with group ids
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
	 * @return array<string, string> ['user id' => 'display name']
	 * @since 8.0.0
	 */
	public function displayNamesInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array;

	/**
	 * Checks if a userId is in the admin group
	 * @param string $userId
	 * @return bool if admin
	 * @since 8.0.0
	 */
	public function isAdmin(string $userId): bool;

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
	public function isInGroup(string $userId, string $group): bool;

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
