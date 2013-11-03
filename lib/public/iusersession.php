<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
	 */
	public function logout();

}
