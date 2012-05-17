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
 * dummy user backend, does not keep state, only for testing use
 */
class OC_User_Dummy extends OC_User_Backend {
	private $users=array();
	/**
		* @brief Create a new user
		* @param $uid The username of the user to create
		* @param $password The password of the new user
		* @returns true/false
		*
		* Creates a new user. Basic checking of username is done in OC_User
		* itself, not in its subclasses.
		*/
	public function createUser($uid, $password){
		if(isset($this->users[$uid])){
			return false;
		}else{
			$this->users[$uid]=$password;
			return true;
		}
	}

	/**
		* @brief delete a user
		* @param $uid The username of the user to delete
		* @returns true/false
		*
		* Deletes a user
		*/
	public function deleteUser( $uid ){
		if(isset($this->users[$uid])){
			unset($this->users[$uid]);
			return true;
		}else{
			return false;
		}
	}

	/**
		* @brief Set password
		* @param $uid The username
		* @param $password The new password
		* @returns true/false
		*
		* Change the password of a user
		*/
	public function setPassword($uid, $password){
		if(isset($this->users[$uid])){
			$this->users[$uid]=$password;
			return true;
		}else{
			return false;
		}
	}

	/**
		* @brief Check if the password is correct
		* @param $uid The username
		* @param $password The password
		* @returns string
		*
		* Check if the password is correct without logging in the user
		* returns the user id or false
		*/
	public function checkPassword($uid, $password){
		if(isset($this->users[$uid])){
			return ($this->users[$uid]==$password);
		}else{
			return false;
		}
	}

	/**
		* @brief Get a list of all users
		* @returns array with all uids
		*
		* Get a list of all users.
		*/
	public function getUsers(){
		return array_keys($this->users);
	}

	/**
		* @brief check if a user exists
		* @param string $uid the username
		* @return boolean
		*/
	public function userExists($uid){
		return isset($this->users[$uid]);
	}
}
