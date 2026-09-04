<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

use OCP\AppFramework\Attribute\Implementable;

/**
 * TODO actually this is a IGroupBackend
 *
 * @since 4.5.0
 */
#[Implementable(since: '4.5.0')]
interface GroupInterface {
	/**
	 * actions that user backends can define
	 *
	 * @since 12.0.0
	 */
	public const CREATE_GROUP = 0x00000001;

	/**
	 * @since 12.0.0
	 */
	public const DELETE_GROUP = 0x00000010;

	/**
	 * @since 12.0.0
	 */
	public const ADD_TO_GROUP = 0x00000100;

	/**
	 * @since 12.0.0
	 */
	public const REMOVE_FROM_GOUP = 0x00001000; // oops

	/**
	 * @since 12.0.0
	 */
	public const REMOVE_FROM_GROUP = 0x00001000;

	/**
	 * @since 12.0.0
	 */
	public const COUNT_USERS = 0x00100000;

	/**
	 * @since 12.0.0
	 */
	public const GROUP_DETAILS = 0x01000000;

	/**
	 * @since 13.0.0
	 */
	public const IS_ADMIN = 0x10000000;

	/**
	 * Check if backend implements actions
	 *
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 * @since 4.5.0
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC_Group_Backend::CREATE_GROUP etc.
	 *
	 * TODO: the LDAP backend use a plugin mechanism to determine which actions is available. This is not compatible with instanceof checks.
	 */
	public function implementsActions(int $actions);

	/**
	 * Checks whether the user is member of a group.
	 *
	 * @param non-empty-string $uid uid of the user
	 * @param non-empty-string $gid gid of the group
	 * @return bool
	 * @since 4.5.0
	 */
	public function inGroup(string $uid, string $gid);

	/**
	 * Get all groups a user belongs to.
	 *
	 * @note This does not check if the user exists at all.
	 * @param non-empty-string $uid Name of the user
	 * @return list<string> an array of group names
	 * @since 4.5.0
	 *
	 */
	public function getUserGroups(string $uid);

	/**
	 * Get a list of all groups.
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 * @since 4.5.0
	 */
	public function getGroups(string $search = '', int $limit = -1, int $offset = 0);

	/**
	 * Check if a group exists
	 *
	 * @param non-empty-string $gid
	 * @return bool
	 * @since 4.5.0
	 */
	public function groupExists(string $gid);

	/**
	 * Get a list of user ids in a group matching the given search parameters.
	 *
	 * @param non-empty-string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array<int,string> an array of user ids
	 * @since 4.5.0
	 * @deprecated 27.0.0 Use searchInGroup instead, for performance reasons
	 */
	public function usersInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0);
}
