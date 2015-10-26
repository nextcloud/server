<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * Hooks provided:
 *   post_createSubAdmin($gid)
 *   post_deleteSubAdmin($gid)
 */
class OC_SubAdmin{

	/**
	 * add a SubAdmin
	 * @param string $uid uid of the SubAdmin
	 * @param string $gid gid of the group
	 * @return boolean
	 */
	public static function createSubAdmin($uid, $gid) {
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();
		$subAdmin = $groupManager->getSubAdmin();

		return $subAdmin->createSubAdmin($userManager->get($uid), $groupManager->get($gid));
	}

	/**
	 * delete a SubAdmin
	 * @param string $uid uid of the SubAdmin
	 * @param string $gid gid of the group
	 * @return boolean
	 */
	public static function deleteSubAdmin($uid, $gid) {
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();
		$subAdmin = $groupManager->getSubAdmin();

		return $subAdmin->deleteSubAdmin($userManager->get($uid), $groupManager->get($gid));
	}

	/**
	 * get groups of a SubAdmin
	 * @param string $uid uid of the SubAdmin
	 * @return array
	 */
	public static function getSubAdminsGroups($uid) {
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();
		$subAdmin = $groupManager->getSubAdmin();

		$groups = $subAdmin->getSubAdminsGroups($userManager->get($uid));

		// New class returns IGroup[] so convert back
		$gids = [];
		foreach ($groups as $group) {
			$gids[] = $group->getGID();
		}
		return $gids;
	}

	/**
	 * get SubAdmins of a group
	 * @param string $gid gid of the group
	 * @return array
	 */
	public static function getGroupsSubAdmins($gid) {
		$groupManager = \OC::$server->getGroupManager();
		$subAdmin = $groupManager->getSubAdmin();

		$users = $subAdmin->getGroupsSubAdmins($groupManager->get($gid));

		// New class returns IUser[] so convert back
		$uids = [];
		foreach ($users as $user) {
			$uids[] = $user->getUID();
		}
		return $uids;
	}

	/**
	 * get all SubAdmins
	 * @return array
	 */
	public static function getAllSubAdmins() {
		$groupManager = \OC::$server->getGroupManager();
		$subAdmin = $groupManager->getSubAdmin();

		$subAdmins = $subAdmin->getAllSubAdmins();

		// New class returns IUser[] so convert back
		$result = [];
		foreach ($subAdmins as $subAdmin) {
			$result[] = [
				'gid' => $subAdmin['group']->getGID(),
				'uid' => $subAdmin['user']->getUID(),
			];
		}
		return $result;
	}

	/**
	 * checks if a user is a SubAdmin of a group
	 * @param string $uid uid of the subadmin
	 * @param string $gid gid of the group
	 * @return bool
	 */
	public static function isSubAdminofGroup($uid, $gid) {
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();
		$subAdmin = $groupManager->getSubAdmin();

		return $subAdmin->isSubAdminOfGroup($userManager->get($uid), $groupManager->get($gid));
	}

	/**
	 * checks if a user is a SubAdmin
	 * @param string $uid uid of the subadmin
	 * @return bool
	 */
	public static function isSubAdmin($uid) {
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();
		$subAdmin = $groupManager->getSubAdmin();

		return $subAdmin->isSubAdmin($userManager->get($uid));
	}

	/**
	 * checks if a user is a accessible by a subadmin
	 * @param string $subadmin uid of the subadmin
	 * @param string $user uid of the user
	 * @return bool
	 */
	public static function isUserAccessible($subadmin, $user) {
		$groupManager = \OC::$server->getGroupManager();
		$userManager = \OC::$server->getUserManager();
		$subAdmin = $groupManager->getSubAdmin();

		return $subAdmin->isUserAccessible($userManager->get($subadmin), $userManager->get($user));
	}

	/*
	 * alias for self::isSubAdminofGroup()
	 */
	public static function isGroupAccessible($subadmin, $group) {
		return self::isSubAdminofGroup($subadmin, $group);
	}
}
