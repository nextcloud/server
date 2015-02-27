<?php
/**
 * ownCloud
 *
 * @author Bart Visscher
 * @author Bernhard Posselt
 * @copyright 2013 Bart Visscher bartv@thisnet.nl
 * @copyright 2014 Bernhard Posselt <dev@bernhard-posselt.com>
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
 * Public interface of ownCloud for apps to use.
 * User session interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * User session
 */
interface IUserSession {
	/**
	 * Do a user login
	 * @param string $user the username
	 * @param string $password the password
	 * @return bool true if successful
	 */
	public function login($user, $password);

	/**
	 * Logs the user out including all the session data
	 * Logout, destroys session
	 * @return void
	 */
	public function logout();

	/**
	 * set the currently active user
	 *
	 * @param \OCP\IUser|null $user
	 */
	public function setUser($user);

	/**
	 * get the current active user
	 *
	 * @return \OCP\IUser|null Current user, otherwise null
	 */
	public function getUser();

	/**
	 * Checks whether the user is logged in
	 *
	 * @return bool if logged in
	 */
	public function isLoggedIn();
}
