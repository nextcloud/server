<?php
/**
 * @author Dominik Schmidt <dev@dominik-schmidt.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
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
abstract class OC_User_Example extends OC_User_Backend {
	/**
		* Create a new user
		* @param string $uid The username of the user to create
		* @param string $password The password of the new user
		* @return bool
		*
		* Creates a new user. Basic checking of username is done in OC_User
		* itself, not in its subclasses.
		*/
	abstract public function createUser($uid, $password);

	/**
		* Set password
		* @param string $uid The username
		* @param string $password The new password
		* @return bool
		*
		* Change the password of a user
		*/
	abstract public function setPassword($uid, $password);

	/**
		* Check if the password is correct
		* @param string $uid The username
		* @param string $password The password
		* @return string
		*
		* Check if the password is correct without logging in the user
		* returns the user id or false
		*/
	abstract public function checkPassword($uid, $password);

	/**
		* get the user's home directory
		* @param string $uid The username
		* @return string
		*
		* get the user's home directory
		* returns the path or false
		*/
	abstract public function getHome($uid);
}
