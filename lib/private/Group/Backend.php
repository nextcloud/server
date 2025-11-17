<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

use OCP\GroupInterface;

/**
 * Abstract base class for user management
 */
abstract class Backend implements GroupInterface {
	/**
	 * error code for functions not provided by the group backend
	 */
	public const NOT_IMPLEMENTED = -501;

	protected $possibleActions = [
		self::CREATE_GROUP => 'createGroup',
		self::DELETE_GROUP => 'deleteGroup',
		self::ADD_TO_GROUP => 'addToGroup',
		self::REMOVE_FROM_GOUP => 'removeFromGroup',
		self::COUNT_USERS => 'countUsersInGroup',
		self::GROUP_DETAILS => 'getGroupDetails',
		self::IS_ADMIN => 'isAdmin',
	];

	/**
	 * Get all supported actions
	 * @return int bitwise-or'ed actions
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\Group\Backend::CREATE_GROUP etc.
	 */
	public function getSupportedActions() {
		$actions = 0;
		foreach ($this->possibleActions as $action => $methodName) {
			if (method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}

	/**
	 * Check if backend implements actions
	 * @param int $actions bitwise-or'ed actions
	 * @return bool
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\Group\Backend::CREATE_GROUP etc.
	 */
	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		return in_array($gid, $this->getUserGroups($uid));
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return list<string> an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		return [];
	}

	/**
	 * get a list of all groups
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 *
	 * Returns a list with all groups
	 */

	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return [];
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		return in_array($gid, $this->getGroups($gid, 1));
	}

	/**
	 * get a list of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array<int,string> an array of user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		return [];
	}
}
