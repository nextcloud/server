<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * TODO actually this is a IGroupBackend
 *
 * @since 4.5.0
 */
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
	 * @deprecated 29.0.0
	 */
	public const REMOVE_FROM_GOUP = 0x00001000; // oops

	/**
	 * @since 12.0.0
	 */
	public const REMOVE_FROM_GROUP = 0x00001000;

	//OBSOLETE const GET_DISPLAYNAME	= 0x00010000;

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
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 * @since 4.5.0
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC_Group_Backend::CREATE_GROUP etc.
	 */
	public function implementsActions($actions);

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 * @since 4.5.0
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid);

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return list<string> an array of group names
	 * @since 4.5.0
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid);

	/**
	 * @brief Get a list of all groups
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 * @since 4.5.0
	 *
	 * Returns a list with all groups
	 */
	public function getGroups(string $search = '', int $limit = -1, int $offset = 0);

	/**
	 * @brief Check if a group exists
	 *
	 * @param string $gid
	 * @return bool
	 * @since 4.5.0
	 */
	public function groupExists($gid);

	/**
	 * @brief Get a list of user ids in a group matching the given search parameters.
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array<int,string> an array of user ids
	 * @since 4.5.0
	 * @deprecated 27.0.0 Use searchInGroup instead, for performance reasons
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0);
}
