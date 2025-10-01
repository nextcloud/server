<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	 * @since 8.0.0
	 * @return void
	 */
	public function registerBackend(UserInterface $backend);

	/**
	 * Get the active backends
	 * @return UserInterface[]
	 * @since 8.0.0
	 */
	public function getBackends();

	/**
	 * remove a user backend
	 *
	 * @since 8.0.0
	 * @return void
	 */
	public function removeBackend(UserInterface $backend);

	/**
	 * remove all user backends
	 * @since 8.0.0
	 * @return void
	 */
	public function clearBackends();

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
	 * @deprecated 27.0.0, use searchDisplayName instead
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
	 * @since 30.0.0 $search parameter added
	 */
	public function getDisabledUsers(?int $limit = null, int $offset = 0, string $search = ''): array;

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
	 * Get how many users exists in total, whithin limit
	 *
	 * @param int $limit Limit the count to avoid resource waste. 0 to disable
	 * @param bool $onlyMappedUsers Count mapped users instead of all users for compatible backends
	 *
	 * @since 31.0.0
	 */
	public function countUsersTotal(int $limit = 0, bool $onlyMappedUsers = false): int|false;

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

	/**
	 * Gets the list of users sorted by lastLogin, from most recent to least recent
	 *
	 * @param int|null $limit how many records to fetch
	 * @param int $offset from which offset to fetch
	 * @param string $search search users based on search params
	 * @return list<string> list of user IDs
	 * @since 30.0.0
	 */
	public function getLastLoggedInUsers(?int $limit = null, int $offset = 0, string $search = ''): array;

	/**
	 * Gets the list of users.
	 * An iterator is returned allowing the caller to stop the iteration at any time.
	 * The offset argument allows the caller to continue the iteration at a specific offset.
	 *
	 * @param int $offset from which offset to fetch
	 * @param int|null $limit maximum number of records to fetch
	 * @return \Iterator<IUser> list of IUser object
	 * @since 32.0.0
	 */
	public function getSeenUsers(int $offset = 0, ?int $limit = null): \Iterator;
}
