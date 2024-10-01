<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Util\Group;

use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IAddToGroupBackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\ICreateGroupBackend;
use OCP\Group\Backend\IDeleteGroupBackend;
use OCP\Group\Backend\IRemoveFromGroupBackend;
use Test\Util\User\Dummy as DummyUser;

/**
 * Dummy group backend, does not keep state, only for testing use
 */
class Dummy extends ABackend implements ICreateGroupBackend, IDeleteGroupBackend, IAddToGroupBackend, IRemoveFromGroupBackend, ICountUsersBackend {
	private $groups = [];
	/**
	 * Try to create a new group
	 * @param string $gid The name of the group to create
	 * @return bool
	 *
	 * Tries to create a new group. If the group name already exists, false will
	 * be returned.
	 */
	public function createGroup(string $gid): bool {
		if (!isset($this->groups[$gid])) {
			$this->groups[$gid] = [];
			return true;
		} else {
			return false;
		}
	}

	/**
	 * delete a group
	 * @param string $gid gid of the group to delete
	 * @return bool
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public function deleteGroup(string $gid): bool {
		if (isset($this->groups[$gid])) {
			unset($this->groups[$gid]);
			return true;
		} else {
			return false;
		}
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
		if (isset($this->groups[$gid])) {
			return (array_search($uid, $this->groups[$gid]) !== false);
		} else {
			return false;
		}
	}

	/**
	 * Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup(string $uid, string $gid): bool {
		if (isset($this->groups[$gid])) {
			if (array_search($uid, $this->groups[$gid]) === false) {
				$this->groups[$gid][] = $uid;
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Removes a user from a group
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup(string $uid, string $gid): bool {
		if (isset($this->groups[$gid])) {
			if (($index = array_search($uid, $this->groups[$gid])) !== false) {
				unset($this->groups[$gid][$index]);
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		$groups = [];
		$allGroups = array_keys($this->groups);
		foreach ($allGroups as $group) {
			if ($this->inGroup($uid, $group)) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * Get a list of all groups
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of group names
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		if (empty($search)) {
			return array_keys($this->groups);
		}
		$result = [];
		foreach (array_keys($this->groups) as $group) {
			if (stripos($group, $search) !== false) {
				$result[] = $group;
			}
		}
		return $result;
	}

	/**
	 * Get a list of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of user IDs
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if (isset($this->groups[$gid])) {
			if (empty($search)) {
				$length = $limit < 0 ? null : $limit;
				return array_slice($this->groups[$gid], $offset, $length);
			}
			$result = [];
			foreach ($this->groups[$gid] as $user) {
				if (stripos($user, $search) !== false) {
					$result[] = $user;
				}
			}
			return $result;
		} else {
			return [];
		}
	}

	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		if (isset($this->groups[$gid])) {
			if (empty($search)) {
				$length = $limit < 0 ? null : $limit;
				$users = array_slice($this->groups[$gid], $offset, $length);
				return array_map(fn ($user) => new DummyUser($user, ''));
			}
			$result = [];
			foreach ($this->groups[$gid] as $user) {
				if (stripos($user, $search) !== false) {
					$result[$user] = new DummyUser($user, '');
				}
			}
			return $result;
		} else {
			return [];
		}
	}

	/**
	 * get the number of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return int
	 */
	public function countUsersInGroup(string $gid, string $search = ''): int {
		if (isset($this->groups[$gid])) {
			if (empty($search)) {
				return count($this->groups[$gid]);
			}
			$count = 0;
			foreach ($this->groups[$gid] as $user) {
				if (stripos($user, $search) !== false) {
					$count++;
				}
			}
			return $count;
		}
		return 0;
	}

	public function groupExists($gid) {
		return isset($this->groups[$gid]);
	}
}
