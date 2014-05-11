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
 * dummy group backend, does not keep state, only for testing use
 */
class OC_Group_Dummy extends OC_Group_Backend {
	private $groups=array();
	/**
	 * @brief Try to create a new group
	 * @param string $gid The name of the group to create
	 * @return bool
	 *
	 * Trys to create a new group. If the group name already exists, false will
	 * be returned.
	 */
	public function createGroup($gid) {
		if(!isset($this->groups[$gid])) {
			$this->groups[$gid]=array();
			return true;
		}else{
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
	public function deleteGroup($gid) {
		if(isset($this->groups[$gid])) {
			unset($this->groups[$gid]);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @brief is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		if(isset($this->groups[$gid])) {
			return (array_search($uid, $this->groups[$gid])!==false);
		}else{
			return false;
		}
	}

	/**
	 * @brief Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup($uid, $gid) {
		if(isset($this->groups[$gid])) {
			if(array_search($uid, $this->groups[$gid])===false) {
				$this->groups[$gid][]=$uid;
				return true;
			}else{
				return false;
			}
		}else{
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
	public function removeFromGroup($uid, $gid) {
		if(isset($this->groups[$gid])) {
			if(($index=array_search($uid, $this->groups[$gid]))!==false) {
				unset($this->groups[$gid][$index]);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * @brief Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		$groups=array();
		$allGroups=array_keys($this->groups);
		foreach($allGroups as $group) {
			if($this->inGroup($uid, $group)) {
				$groups[]=$group;
			}
		}
		return $groups;
	}

	/**
	 * @brief get a list of all groups
	 * @return array an array of group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return array_keys($this->groups);
	}

	/**
	 * @brief get a list of all users in a group
	 * @return array an array of user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if(isset($this->groups[$gid])) {
			return $this->groups[$gid];
		}else{
			return array();
		}
	}

	/**
	 * @brief get the number of all users in a group
	 * @return int|bool
	 */
	public function countUsersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if(isset($this->groups[$gid])) {
			return count($this->groups[$gid]);
		}
	}

}
