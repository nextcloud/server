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
	 * @return list<IGroup>
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
	 * @return list<string> with group ids
	 * @since 8.0.0
	 */
	public function getUserGroupIds(IUser $user): array;

	/**
	 * Get the effective group ids a user belongs to, including every group
	 * reachable transitively via nested-group (group-in-group) edges.
	 *
	 * Use this for permission checks, share recipient expansion, and anywhere
	 * "the user is effectively a member of G" is the intended semantic.
	 * {@see getUserGroupIds()} returns only direct memberships reported by
	 * backends and does not expand nested groups.
	 *
	 * @return list<string>
	 * @since 34.0.0
	 */
	public function getUserEffectiveGroupIds(IUser $user): array;

	/**
	 * Add $child as a direct subgroup of $parent.
	 *
	 * Users who are transitively members of $child become effective members
	 * of $parent and will be reflected by {@see getUserEffectiveGroupIds()}.
	 * A {@see \OCP\Group\Events\SubGroupAddedEvent} is dispatched, followed
	 * by a best-effort batch of {@see \OCP\Group\Events\UserAddedEvent}s for
	 * every user who gains effective membership of $parent (subject to an
	 * internal synthesis cap; see the Manager implementation).
	 *
	 * @return bool true if the edge was inserted, false if it already existed
	 * @throws \OCP\Group\Exception\CycleDetectedException if the edge would create a cycle
	 * @throws \OCP\Group\Exception\NestedGroupsNotSupportedException if no
	 *                                                                nested-group-capable backend is registered
	 * @since 34.0.0
	 */
	public function addSubGroup(IGroup $parent, IGroup $child): bool;

	/**
	 * Remove the direct edge $parent -> $child.
	 *
	 * A {@see \OCP\Group\Events\SubGroupRemovedEvent} is dispatched, followed
	 * by a best-effort batch of {@see \OCP\Group\Events\UserRemovedEvent}s
	 * for every user who loses effective membership of $parent.
	 *
	 * @return bool true if an edge was removed
	 * @since 34.0.0
	 */
	public function removeSubGroup(IGroup $parent, IGroup $child): bool;

	/**
	 * List direct child group ids of $gid (one level deep).
	 *
	 * Unlike the effective-membership helpers this does not walk the
	 * hierarchy; it is intended for admin UIs that need to render and
	 * mutate the immediate nesting edges.
	 *
	 * @return list<string>
	 * @since 34.0.0
	 */
	public function getDirectChildGroupIds(string $gid): array;

	/**
	 * List direct parent group ids of $gid (one level deep).
	 *
	 * @return list<string>
	 * @since 34.0.0
	 */
	public function getDirectParentGroupIds(string $gid): array;

	/**
	 * Return the gids of $group itself plus every transitive descendant,
	 * following parent -> child edges in the nested-group hierarchy.
	 *
	 * Intended for callers that need to enumerate "everything under" a group
	 * (e.g. effective member resolution, sub-admin delegation listings).
	 * Implementations may memoize the result per request.
	 *
	 * @return list<string>
	 * @since 34.0.0
	 */
	public function getGroupEffectiveDescendantIds(IGroup $group): array;

	/**
	 * Return the gids of $group itself plus every transitive ancestor,
	 * following child -> parent edges in the nested-group hierarchy.
	 *
	 * @return list<string>
	 * @since 34.0.0
	 */
	public function getGroupEffectiveAncestorIds(IGroup $group): array;

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
