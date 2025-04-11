<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * User session
 * @since 6.0.0
 */
interface IUserSession {
	/**
	 * Do a user login
	 *
	 * @param string $uid the username
	 * @param string $password the password
	 * @return bool true if successful
	 * @since 6.0.0
	 */
	public function login($uid, $password);

	/**
	 * Logs the user out including all the session data
	 * Logout, destroys session
	 *
	 * @return void
	 * @since 6.0.0
	 */
	public function logout();

	/**
	 * set the currently active user
	 *
	 * @param \OCP\IUser|null $user
	 * @since 8.0.0
	 */
	public function setUser($user);

	/**
	 * Temporarily set the currently active user without persisting in the session
	 *
	 * @param IUser|null $user
	 * @since 29.0.0
	 */
	public function setVolatileActiveUser(?IUser $user): void;

	/**
	 * get the current active user
	 *
	 * @return \OCP\IUser|null Current user, otherwise null
	 * @since 8.0.0
	 */
	public function getUser();

	/**
	 * Checks whether the user is logged in
	 *
	 * @return bool if logged in
	 * @since 8.0.0
	 */
	public function isLoggedIn();

	/**
	 * get getImpersonatingUserID
	 *
	 * @return string|null
	 * @since 18.0.0
	 */
	public function getImpersonatingUserID(): ?string;

	/**
	 * set setImpersonatingUserID
	 *
	 * @since 18.0.0
	 */
	public function setImpersonatingUserID(bool $useCurrentUser = true): void;
}
