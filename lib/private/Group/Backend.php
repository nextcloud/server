<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

/**
 * Abstract base class for user management
 */
abstract class Backend implements \OCP\GroupInterface {
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

	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}

	public function inGroup($uid, $gid) {
		return in_array($gid, $this->getUserGroups($uid));
	}

	public function getUserGroups($uid) {
		return [];
	}

	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return [];
	}

	public function groupExists($gid) {
		return in_array($gid, $this->getGroups($gid, 1));
	}

	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		return [];
	}
}
