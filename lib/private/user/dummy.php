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
 * dummy user backend, does not keep state, only for testing use
 */
class OC_User_Dummy extends OC_User_Backend implements \OCP\IUserBackend {
	private $users = array();
	private $displayNames = array();

	/**
	 * Create a new user
	 *
	 * @param string $uid The username of the user to create
	 * @param string $password The password of the new user
	 * @return bool
	 *
	 * Creates a new user. Basic checking of username is done in OC_User
	 * itself, not in its subclasses.
	 */
	public function createUser($uid, $password) {
		if (isset($this->users[$uid])) {
			return false;
		} else {
			$this->users[$uid] = $password;
			return true;
		}
	}

	/**
	 * delete a user
	 *
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public function deleteUser($uid) {
		if (isset($this->users[$uid])) {
			unset($this->users[$uid]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set password
	 *
	 * @param string $uid The username
	 * @param string $password The new password
	 * @return bool
	 *
	 * Change the password of a user
	 */
	public function setPassword($uid, $password) {
		if (isset($this->users[$uid])) {
			$this->users[$uid] = $password;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the password is correct
	 *
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		if (isset($this->users[$uid]) && $this->users[$uid] === $password) {
			return $uid;
		} else {
			return false;
		}
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return string[] with all uids
	 *
	 * Get a list of all users.
	 */
	public function getUsers($search = '', $limit = null, $offset = null) {
		if (empty($search)) {
			return array_keys($this->users);
		}
		$result = array();
		foreach (array_keys($this->users) as $user) {
			if (stripos($user, $search) !== false) {
				$result[] = $user;
			}
		}
		return $result;
	}

	/**
	 * check if a user exists
	 *
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		return isset($this->users[$uid]);
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return true;
	}

	/**
	 * counts the users in the database
	 *
	 * @return int|bool
	 */
	public function countUsers() {
		return 0;
	}

	public function setDisplayName($uid, $displayName) {
		$this->displayNames[$uid] = $displayName;
	}

	public function getDisplayName($uid) {
		return isset($this->displayNames[$uid])? $this->displayNames[$uid]: $uid;
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'Dummy';
	}
}
