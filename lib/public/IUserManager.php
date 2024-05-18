<?php
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
 * @since 8.0.0
 */
interface IUserManager {
	/**
	 * @since 26.0.0
	 */
	public const MAX_PASSWORD_LENGTH = 469;

	/**
	 * register a user backend
	 *
	 * @param \OCP\UserInterface $backend
	 * @since 8.0.0
	 */
	public function registerBackend($backend);

	/**
	 * Get the active backends
	 * @return \OCP\UserInterface[]
	 * @since 8.0.0
	 */
	public function getBackends();

	/**
	 * remove a user backend
	 *
	 * @param \OCP\UserInterface $backend
	 * @since 8.0.0
	 */
	public function removeBackend($backend);

	/**
	 * remove all user backends
	 * @since 8.0.0
	 */
	public function clearBackends() ;

	/**
	 * get a user by user id
	 *
	 * @param string $uid
	 * @return \OCP\IUser|null Either the user or null if the specified user does not exist
	 * @since 8.0.0
	 */
	public function get($uid);

	/**
	 * Get the display name of a user
	 *
	 * @param string $uid
	 * @return string|null
	 * @since 25.0.0
	 */
	public function getDisplayName(string $uid): ?string;

	/**
	 * check if a user exists
	 *
	 * @param string $uid
	 * @return bool
	 * @since 8.0.0
	 */
	public function userExists($uid);

	/**
	 * Check if the password is valid for the user
	 *
	 * @param string $loginName
	 * @param string $password
	 * @return IUser|false the User object on success, false otherwise
	 * @since 8.0.0
	 */
	public function checkPassword($loginName, $password);

	/**
	 * search by user id
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\IUser[]
	 * @since 8.0.0
	 */
	public function search($pattern, $limit = null, $offset = null);

	/**
	 * search by displayName
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\IUser[]
	 * @since 8.0.0
	 */
	public function searchDisplayName($pattern, $limit = null, $offset = null);

	/**
	 * @return IUser[]
	 * @since 28.0.0
	 */
	public function getDisabledUsers(?int $limit = null, int $offset = 0): array;

	/**
	 * Search known users (from phonebook sync) by displayName
	 *
	 * @param string $searcher
	 * @param string $pattern
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return IUser[]
	 * @since 21.0.1
	 */
	public function searchKnownUsersByDisplayName(string $searcher, string $pattern, ?int $limit = null, ?int $offset = null): array;

	/**
	 * @param string $uid
	 * @param string $password
	 * @throws \InvalidArgumentException
	 * @return false|\OCP\IUser the created user or false
	 * @since 8.0.0
	 */
	public function createUser($uid, $password);

	/**
	 * @param string $uid
	 * @param string $password
	 * @param UserInterface $backend
	 * @return IUser|null
	 * @throws \InvalidArgumentException
	 * @since 12.0.0
	 */
	public function createUserFromBackend($uid, $password, UserInterface $backend);

	/**
	 * Get how many users per backend exist (if supported by backend)
	 *
	 * @return array<string, int> an array of backend class name as key and count number as value
	 * @since 8.0.0
	 */
	public function countUsers();

	/**
	 * @param \Closure $callback
	 * @psalm-param \Closure(\OCP\IUser):void $callback
	 * @param string $search
	 * @since 9.0.0
	 */
	public function callForAllUsers(\Closure $callback, $search = '');

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
	 * @param \Closure $callback
	 * @psalm-param \Closure(\OCP\IUser):?bool $callback
	 * @since 11.0.0
	 */
	public function callForSeenUsers(\Closure $callback);

	/**
	 * returns all users having the provided email set as system email address
	 *
	 * @param string $email
	 * @return IUser[]
	 * @since 9.1.0
	 */
	public function getByEmail($email);

	/**
	 * @param string $uid The user ID to validate
	 * @param bool $checkDataDirectory Whether it should be checked if files for the ID exist inside the data directory
	 * @throws \InvalidArgumentException Message is an already translated string with a reason why the ID is not valid
	 * @since 26.0.0
	 */
	public function validateUserId(string $uid, bool $checkDataDirectory = false): void;
}
