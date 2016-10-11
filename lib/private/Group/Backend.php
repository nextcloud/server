<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OC\Group;

/**
 * Abstract base class for user management
 */
abstract class Backend implements \OCP\GroupInterface {
	/**
	 * error code for functions not provided by the group backend
	 */
	const NOT_IMPLEMENTED = -501;

	/**
	 * actions that user backends can define
	 */
	const CREATE_GROUP		= 0x00000001;
	const DELETE_GROUP		= 0x00000010;
	const ADD_TO_GROUP		= 0x00000100;
	const REMOVE_FROM_GOUP	= 0x00001000;
	//OBSOLETE const GET_DISPLAYNAME	= 0x00010000;
	const COUNT_USERS		= 0x00100000;

	protected $possibleActions = array(
		self::CREATE_GROUP => 'createGroup',
		self::DELETE_GROUP => 'deleteGroup',
		self::ADD_TO_GROUP => 'addToGroup',
		self::REMOVE_FROM_GOUP => 'removeFromGroup',
		self::COUNT_USERS => 'countUsersInGroup',
	);

	/**
	* Get all supported actions
	* @return int bitwise-or'ed actions
	*
	* Returns the supported actions as int to be
	* compared with \OC\Group\Backend::CREATE_GROUP etc.
	*/
	public function getSupportedActions() {
		$actions = 0;
		foreach($this->possibleActions AS $action => $methodName) {
			if(method_exists($this, $methodName)) {
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
	 * @return array an array of group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		return array();
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
		return array();
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
	 * @return array an array of user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		return array();
	}
}
