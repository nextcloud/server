<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author goodkiller <markopraakli@gmail.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author macjohnny <estebanmarin@gmx.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Qingping Hou <dave2008713@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * This class provides all methods needed for managing groups.
 *
 * Note that &run is deprecated and won't work anymore.
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
	 * @return \OC\Group\Manager
	 * @deprecated Use \OC::$server->getGroupManager();
	 */
	public static function getManager() {
		return \OC::$server->getGroupManager();
	}

	/**
	 * @return \OC\User\Manager
	 * @deprecated Use \OC::$server->getUserManager()
	 */
	private static function getUserManager() {
		return \OC::$server->getUserManager();
	}

	/**
	 * set the group backend
	 * @param \OC\Group\Backend $backend  The backend to use for user management
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
	 * Try to create a new group
	 * @param string $gid The name of the group to create
	 * @return bool
	 *
	 * Tries to create a new group. If the group name already exists, false will
	 * be returned. Basic checking of Group name
	 * @deprecated Use \OC::$server->getGroupManager()->createGroup() instead
	 */
	public static function createGroup($gid) {
		if (self::getManager()->createGroup($gid)) {
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
	 * @deprecated Use \OC::$server->getGroupManager()->delete() instead
	 */
	public static function deleteGroup($gid) {
		$group = self::getManager()->get($gid);
		if ($group) {
			if ($group->delete()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 * @deprecated Use \OC::$server->getGroupManager->inGroup($user);
	 */
	public static function inGroup($uid, $gid) {
		$group = self::getManager()->get($gid);
		$user = self::getUserManager()->get($uid);
		if ($group and $user) {
			return $group->inGroup($user);
		}
		return false;
	}

	/**
	 * Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 * @deprecated Use \OC::$server->getGroupManager->addUser();
	 */
	public static function addToGroup($uid, $gid) {
		$group = self::getManager()->get($gid);
		$user = self::getUserManager()->get($uid);
		if ($group and $user) {
			$group->addUser($user);
			return true;
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
	public static function removeFromGroup($uid, $gid) {
		$group = self::getManager()->get($gid);
		$user = self::getUserManager()->get($uid);
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
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 * @deprecated Use \OC::$server->getGroupManager->getUserGroupIds($user)
	 */
	public static function getUserGroups($uid) {
		$user = self::getUserManager()->get($uid);
		if ($user) {
			return self::getManager()->getUserGroupIds($user);
		} else {
			return array();
		}
	}

	/**
	 * get a list of all groups
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array an array of group names
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
	 * @deprecated Use \OC::$server->getGroupManager->groupExists($gid)
	 */
	public static function groupExists($gid) {
		return self::getManager()->groupExists($gid);
	}

	/**
	 * get a list of all users in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of user ids
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
	 * get a list of all users in several groups
	 * @param string[] $gids
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of user ids
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
	 * get a list of all display names in a group
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of display names (value) and user ids(key)
	 * @deprecated Use \OC::$server->getGroupManager->displayNamesInGroup($gid, $search, $limit, $offset)
	 */
	public static function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		return self::getManager()->displayNamesInGroup($gid, $search, $limit, $offset);
	}

	/**
	 * get a list of all display names in several groups
	 * @param array $gids
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array an array of display names (Key) user ids (value)
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
				// A fix for LDAP users. array_merge loses keys...
				$displayNames = $diff + $displayNames;
			}
		}
		return $displayNames;
	}
}
