<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 * 
 */

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
	 * @brief Logs the user out including all the session data
	 * Logout, destroys session
	 */
	public function logout();

}
