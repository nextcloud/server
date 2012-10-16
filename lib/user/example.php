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
 * abstract reference class for user management
 * this class should only be used as a reference for method signatures and their descriptions
 */
abstract class OC_User_Example extends OC_User_Backend {
	/**
		* @brief Create a new user
		* @param $uid The username of the user to create
		* @param $password The password of the new user
		* @returns true/false
		*
		* Creates a new user. Basic checking of username is done in OC_User
		* itself, not in its subclasses.
		*/
	abstract public function createUser($uid, $password);

	/**
		* @brief Set password
		* @param $uid The username
		* @param $password The new password
		* @returns true/false
		*
		* Change the password of a user
		*/
	abstract public function setPassword($uid, $password);

	/**
		* @brief Check if the password is correct
		* @param $uid The username
		* @param $password The password
		* @returns string
		*
		* Check if the password is correct without logging in the user
		* returns the user id or false
		*/
	abstract public function checkPassword($uid, $password);

	/**
		* @brief get the user's home directory
		* @param $uid The username
		* @returns string
		*
		* get the user's home directory
		* returns the path or false
		*/
	abstract public function getHome($uid);
}
