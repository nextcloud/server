<?php

/**
 * ownCloud - user interface
 *
 * @author Arthur Schiwon
 * @copyright 2012 Arthur Schiwon blizzz@owncloud.org
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

interface OC_User_Interface {

	/**
	* Check if backend implements actions
	* @param int $actions bitwise-or'ed actions
	* @return boolean
	*
	* Returns the supported actions as int to be
	* compared with \OC_User_Backend::CREATE_USER etc.
	*/
	public function implementsActions($actions);

	/**
	* delete a user
	* @param string $uid The username of the user to delete
	* @return bool
	*/
	public function deleteUser($uid);

	/**
	* Get a list of all users
	* @return array an array of all uids
	*
	* Get a list of all users.
	*/
	public function getUsers($search = '', $limit = null, $offset = null);

	/**
	* check if a user exists
	* @param string $uid the username
	* @return boolean
	*/
	public function userExists($uid);

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid);

	/**
	 * Get a list of all display names
	 * @return array an array of  all displayNames (value) and the corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null);

	/**
	 * Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 */
	public function hasUserListings();
}
