<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lorenzo M. Catucci <lorenzo@sancho.ccd.uniroma2.it>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

/**
 * Public interface of ownCloud for apps to use.
 * User Class
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides access to the user management. You can get information
 * about the currently logged in user and the permissions for example
 * @since 5.0.0
 */
class User {
	/**
	 * Get the user id of the user currently logged in.
	 * @return string uid or false
	 * @deprecated 8.0.0 Use \OC::$server->getUserSession()->getUser()->getUID()
	 * @since 5.0.0
	 */
	public static function getUser() {
		return \OC_User::getUser();
	}

	/**
	 * Get a list of all users
	 * @param string $search search pattern
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array an array of all uids
	 * @deprecated 8.1.0 use method search() of \OCP\IUserManager - \OC::$server->getUserManager()
	 * @since 5.0.0
	 */
	public static function getUsers( $search = '', $limit = null, $offset = null ) {
		return \OC_User::getUsers( $search, $limit, $offset );
	}

	/**
	 * Get the user display name of the user currently logged in.
	 * @param string|null $user user id or null for current user
	 * @return string display name
	 * @deprecated 8.1.0 fetch \OCP\IUser (has getDisplayName()) by using method
	 *                   get() of \OCP\IUserManager - \OC::$server->getUserManager()
	 * @since 5.0.0
	 */
	public static function getDisplayName( $user = null ) {
		return \OC_User::getDisplayName( $user );
	}

	/**
	 * Get a list of all display names and user ids.
	 * @param string $search search pattern
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array an array of all display names (value) and the correspondig uids (key)
	 * @deprecated 8.1.0 use method searchDisplayName() of \OCP\IUserManager - \OC::$server->getUserManager()
	 * @since 5.0.0
	 */
	public static function getDisplayNames( $search = '', $limit = null, $offset = null ) {
		return \OC_User::getDisplayNames( $search, $limit, $offset );
	}

	/**
	 * Check if the user is logged in
	 * @return boolean
	 * @since 5.0.0
	 */
	public static function isLoggedIn() {
		return \OC_User::isLoggedIn();
	}

	/**
	 * Check if a user exists
	 * @param string $uid the username
	 * @param string $excludingBackend (default none)
	 * @return boolean
	 * @deprecated 8.1.0 use method userExists() of \OCP\IUserManager - \OC::$server->getUserManager()
	 * @since 5.0.0
	 */
	public static function userExists( $uid, $excludingBackend = null ) {
		return \OC_User::userExists( $uid, $excludingBackend );
	}
	/**
	 * Logs the user out including all the session data
	 * Logout, destroys session
	 * @deprecated 8.0.0 Use \OC::$server->getUserSession()->logout();
	 * @since 5.0.0
	 */
	public static function logout() {
		\OC::$server->getUserSession()->logout();
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string|false username on success, false otherwise
	 *
	 * Check if the password is correct without logging in the user
	 * @deprecated 8.0.0 Use \OC::$server->getUserManager()->checkPassword();
	 * @since 5.0.0
	 */
	public static function checkPassword( $uid, $password ) {
		return \OC_User::checkPassword( $uid, $password );
	}

	/**
	 * Check if the user is a admin, redirects to home if not
	 * @since 5.0.0
	 */
	public static function checkAdminUser() {
		\OC_Util::checkAdminUser();
	}

	/**
	 * Check if the user is logged in, redirects to home if not. With
	 * redirect URL parameter to the request URI.
	 * @since 5.0.0
	 */
	public static function checkLoggedIn() {
		\OC_Util::checkLoggedIn();
	}
}
