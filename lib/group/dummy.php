<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
	 * @param $gid The name of the group to create
	 * @returns true/false
	 *
	 * Trys to create a new group. If the group name already exists, false will
	 * be returned.
	 */
	public function createGroup($gid){
		if(!isset($this->groups[$gid])){
			$this->groups[$gid]=array();
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @brief delete a group
	 * @param $gid gid of the group to delete
	 * @returns true/false
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public function deleteGroup($gid){
		if(isset($this->groups[$gid])){
			unset($this->groups[$gid]);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @brief is user in group?
	 * @param $uid uid of the user
	 * @param $gid gid of the group
	 * @returns true/false
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid){
		if(isset($this->groups[$gid])){
			return (array_search($uid,$this->groups[$gid])!==false);
		}else{
			return false;
		}
	}

	/**
	 * @brief Add a user to a group
	 * @param $uid Name of the user to add to group
	 * @param $gid Name of the group in which add the user
	 * @returns true/false
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup($uid, $gid){
		if(isset($this->groups[$gid])){
			if(array_search($uid,$this->groups[$gid])===false){
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
	 * @param $uid NameUSER of the user to remove from group
	 * @param $gid Name of the group from which remove the user
	 * @returns true/false
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup($uid,$gid){
		if(isset($this->groups[$gid])){
			if(($index=array_search($uid,$this->groups[$gid]))!==false){
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
	 * @param $uid Name of the user
	 * @returns array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid){
		$groups=array();
		foreach($this->groups as $group=>$user){
			if($this->inGroup($uid,$group)){
				$groups[]=$group;
			}
		}
		return $groups;
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups(){
		return array_keys($this->groups);
	}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public function usersInGroup($gid){
		if(isset($this->groups[$gid])){
			return $this->groups[$gid];
		}else{
			return array();
		}
	}

}
