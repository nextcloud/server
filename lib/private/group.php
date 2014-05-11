<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class provides all methods needed for managing groups.
 *
 * Hooks provided:
 *   pre_createGroup(&run, gid)
 *   post_createGroup(gid)
 *   pre_deleteGroup(&run, gid)
 *   post_deleteGroup(gid)
 *   pre_addToGroup(&run, uid, gid)
 *   post_addToGroup(uid, gid)
 *   pre_removeFromGroup(&run, uid, gid)
 *   post_removeFromGroup(uid, gid)
 */
class OC_Group {
	/**
	 * @var \OC\Group\Manager $manager
	 */
	private static $manager;

	/**
	 * @var \OC\User\Manager
	 */
	private static $userManager;

	/**
	 * @return \OC\Group\Manager
	 */
	public static function getManager() {
		if (self::$manager) {
			return self::$manager;
		}
		self::$userManager = \OC_User::getManager();
		self::$manager = new \OC\Group\Manager(self::$userManager);
		return self::$manager;
	}

	/**
	 * @brief set the group backend
	 * @param  \OC_Group_Backend $backend  The backend to use for user managment
	 * @return bool
	 */
	public static function useBackend($backend) {
		self::getManager()->addBackend($backend);
		return true;
	}

	/**
	 * remove all used backends
	 */
	public static function clearBackends() {
		self::getManager()->clearBackends();
	}

	/**
	 * @brief Try to create a new group
	 * @param string $gid The name of the group to create
	 * @return bool
	 *
	 * Tries to create a new group. If the group name already exists, false will
	 * be returned. Basic checking of Group name
	 */
	public static function createGroup($gid) {
		OC_Hook::emit("OC_Group", "pre_createGroup", array("run" => true, "gid" => $gid));

		if (self::getManager()->createGroup($gid)) {
			OC_Hook::emit("OC_User", "post_createGroup", array("gid" => $gid));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief delete a group
	 * @param string $gid gid of the group to delete
	 * @return bool
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public static function deleteGroup($gid) {
		// Prevent users from deleting group admin
		if ($gid == "admin") {
			return false;
		}

		OC_Hook::emit("OC_Group", "pre_deleteGroup", array("run" => true, "gid" => $gid));

		$group = self::getManager()->get($gid);
		if ($group) {
			if ($group->delete()) {
				OC_Hook::emit("OC_User", "post_deleteGroup", array("gid" => $gid));
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public static function inGroup($uid, $gid) {
		$group = self::getManager()->get($gid);
		$user = self::$userManager->get($uid);
		if ($group and $user) {
			return $group->inGroup($user);
		}
		return false;
	}

	/**
	 * @brief Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public static function addToGroup($uid, $gid) {
		$group = self::getManager()->get($gid);
		$user = self::$userManager->get($uid);
		if ($group and $user) {
			OC_Hook::emit("OC_Group", "pre_addToGroup", array("run" => true, "uid" => $uid, "gid" => $gid));
			$group->addUser($user);
			OC_Hook::emit("OC_User", "post_addToGroup", array("uid" => $uid, "gid" => $gid));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief Removes a user from a group
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public static function removeFromGroup($uid, $gid) {
		$group = self::getManager()->get($gid);
		$user = self::$userManager->get($uid);
		if ($group and $user) {
			OC_Hook::emit("OC_Group", "pre_removeFromGroup", array("run" => true, "uid" => $uid, "gid" => $gid));
			$group->removeUser($user);
			OC_Hook::emit("OC_User", "post_removeFromGroup", array("uid" => $uid, "gid" => $gid));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public static function getUserGroups($uid) {
		$user = self::$userManager->get($uid);
		if ($user) {
			$groups = self::getManager()->getUserGroups($user);
			$groupIds = array();
			foreach ($groups as $group) {
				$groupIds[] = $group->getGID();
			}
			return $groupIds;
		} else {
			return array();
		}
	}

	/**
	 * @brief get a list of all groups
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array with group names
	 *
	 * Returns a list with all groups
	 */
	public static function getGroups($search = '', $limit = null, $offset = null) {
		$groups = self::getManager()->search($search, $limit, $offset);
		$groupIds = array();
		foreach ($groups as $group) {
			$groupIds[] = $group->getGID();
		}
		return $groupIds;
	}

	/**
	 * check if a group exists
	 *
	 * @param string $gid
	 * @return bool
	 */
	public static function groupExists($gid) {
		return self::getManager()->groupExists($gid);
	}

	/**
	 * @brief get a list of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with user ids
	 */
	public static function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$group = self::getManager()->get($gid);
		if ($group) {
			$users = $group->searchUsers($search, $limit, $offset);
			$userIds = array();
			foreach ($users as $user) {
				$userIds[] = $user->getUID();
			}
			return $userIds;
		} else {
			return array();
		}
	}

	/**
	 * @brief get a list of all users in several groups
	 * @param string[] $gids
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with user ids
	 */
	public static function usersInGroups($gids, $search = '', $limit = -1, $offset = 0) {
		$users = array();
		foreach ($gids as $gid) {
			// TODO Need to apply limits to groups as total
			$users = array_merge(array_diff(self::usersInGroup($gid, $search, $limit, $offset), $users), $users);
		}
		return $users;
	}

	/**
	 * @brief get a list of all display names in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with display names (value) and user ids(key)
	 */
	public static function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		return self::getManager()->displayNamesInGroup($gid, $search, $limit, $offset);
	}

	/**
	 * @brief get a list of all display names in several groups
	 * @param array $gids
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with display names (Key) user ids (value)
	 */
	public static function displayNamesInGroups($gids, $search = '', $limit = -1, $offset = 0) {
		$displayNames = array();
		foreach ($gids as $gid) {
			// TODO Need to apply limits to groups as total
			$diff = array_diff(
				self::displayNamesInGroup($gid, $search, $limit, $offset),
				$displayNames
			);
			if ($diff) {
				$displayNames = array_merge($diff, $displayNames);
			}
		}
		return $displayNames;
	}
}
