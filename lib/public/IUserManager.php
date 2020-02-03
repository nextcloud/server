<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP;


use Closure;
use InvalidArgumentException;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\User:
 * - preSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - postSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - preDelete(\OC\User\User $user)
 * - postDelete(\OC\User\User $user)
 * - preCreateUser(string $uid, string $password)
 * - postCreateUser(\OC\User\User $user, string $password)
 * - assignedUserId(string $uid)
 * - preUnassignedUserId(string $uid)
 * - postUnassignedUserId(string $uid)
 *
 * @package OC\User
 * @since 8.0.0
 */
interface IUserManager {
		/**
	 * register a user backend
	 *
	 * @param UserInterface $backend
	 * @since 8.0.0
	 */
	public function registerBackend(UserInterface $backend);

	/**
	 * Get the active backends
	 *
	 * @return UserInterface[]
	 * @since 8.0.0
	 */
	public function getBackends();

	/**
	 * remove a user backend
	 *
	 * @param UserInterface $backend
	 * @since 8.0.0
	 */
	public function removeBackend(UserInterface $backend);

	/**
	 * remove all user backends
	 * @since 8.0.0
	 */
	public function clearBackends() ;

	/**
	 * get a user by user id
	 *
	 * @param string $uid
	 * @return IUser|null Either the user or null if the specified user does not exist
	 * @since 8.0.0
	 */
	public function get(string $uid);

	/**
	 * check if a user exists
	 *
	 * @param string $uid
	 * @return bool
	 * @since 8.0.0
	 */
	public function userExists(string $uid);

	/**
	 * Check if the password is valid for the user
	 *
	 * @param string $loginName
	 * @param string $password
	 * @return mixed the User object on success, false otherwise
	 * @since 8.0.0
	 */
	public function checkPassword(string $loginName, string $password);

	/**
	 * search by user id
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function search(string $pattern, int $limit = null, int $offset = null);

	/**
	 * search by displayName
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 * @since 8.0.0
	 */
	public function searchDisplayName(string $pattern, int $limit = null, int $offset = null);

	/**
	 * @param string $uid
	 * @param string $password
	 * @return bool|IUser the created user or false
	 * @throws InvalidArgumentException
	 * @since 8.0.0
	 */
	public function createUser(string $uid, string $password);

	/**
	 * @param string $uid
	 * @param string $password
	 * @param UserInterface $backend
	 * @return IUser|null
	 * @throws InvalidArgumentException
	 * @since 12.0.0
	 */
	public function createUserFromBackend(string $uid, string $password, UserInterface $backend);

	/**
	 * returns how many users per backend exist (if supported by backend)
	 *
	 * @return array an array of backend class as key and count number as value
	 * @since 8.0.0
	 */
	public function countUsers();

	/**
	 * @param Closure $callback
	 * @param string $search
	 * @since 9.0.0
	 */
	public function callForAllUsers(Closure $callback, $search = '');

	/**
	 * returns how many users have logged in once
	 *
	 * @return int
	 * @since 11.0.0
	 */
	public function countDisabledUsers();

	/**
	 * returns how many users have logged in once
	 *
	 * @return int
	 * @since 11.0.0
	 */
	public function countSeenUsers();

	/**
	 * @param Closure $callback
	 * @since 11.0.0
	 */
	public function callForSeenUsers(Closure $callback);

	/**
	 * @param string $email
	 * @return IUser[]
	 * @since 9.1.0
	 */
	public function getByEmail(string $email);
}
