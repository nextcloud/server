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
 * error code for functions not provided by the group backend
 */
define('OC_GROUP_BACKEND_NOT_IMPLEMENTED',   -501);

/**
 * actions that user backends can define
 */
define('OC_GROUP_BACKEND_CREATE_GROUP',      0x00000001);
define('OC_GROUP_BACKEND_DELETE_GROUP',      0x00000010);
define('OC_GROUP_BACKEND_IN_GROUP',          0x00000100);
define('OC_GROUP_BACKEND_ADD_TO_GROUP',      0x00001000);
define('OC_GROUP_BACKEND_REMOVE_FROM_GOUP',  0x00010000);
define('OC_GROUP_BACKEND_GET_USER_GROUPS',   0x00100000);
define('OC_GROUP_BACKEND_GET_USERS',         0x01000000);
define('OC_GROUP_BACKEND_GET_GROUPS',        0x10000000);

/**
 * Abstract base class for user management
 */
abstract class OC_Group_Backend {
	protected $possibleActions = array(
		OC_GROUP_BACKEND_CREATE_GROUP => 'createGroup',
		OC_GROUP_BACKEND_DELETE_GROUP => 'deleteGroup',
		OC_GROUP_BACKEND_IN_GROUP => 'inGroup',
		OC_GROUP_BACKEND_ADD_TO_GROUP => 'addToGroup',
		OC_GROUP_BACKEND_REMOVE_FROM_GOUP => 'removeFromGroup',
		OC_GROUP_BACKEND_GET_USER_GROUPS => 'getUserGroups',
		OC_GROUP_BACKEND_GET_USERS => 'usersInGroup',
		OC_GROUP_BACKEND_GET_GROUPS => 'getGroups'
	);
	
	/**
	* @brief Get all supported actions
	* @returns bitwise-or'ed actions
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function getSupportedActions(){
		$actions = 0;
		foreach($this->possibleActions AS $action => $methodName){
			if(method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}
	
	/**
	* @brief Check if backend implements actions
	* @param $actions bitwise-or'ed actions
	* @returns boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_GROUP_BACKEND_CREATE_GROUP etc.
	*/
	public function implementsActions($actions){
		return (bool)($this->getSupportedActions() & $actions);
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid){
		if(!$this->implementsActions(OC_GROUP_BACKEND_GET_GROUPS)){
			return false;
		}
		return in_array($gid, $this->getGroups());
	}
}
