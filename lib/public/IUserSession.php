<?php

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Interface for managing and querying user session state.
 *
 * Provides methods for authenticating users, accessing the active user,
 * and handling login/logout functionality in a Nextcloud server session.
 * @since 6.0.0
 */
interface IUserSession {
	/**
	 * Attempts to authenticate the given user and start a session.
	 *
	 * @param string $uid The user's unique identifier (username).
	 * @param string $password The user's plain-text password.
	 * @return bool True on successful login, false otherwise.
	 * @since 6.0.0
	 */
	public function login($uid, $password);

	/**
	 * Logs out the current user and terminates their session.
	 *
	 * Clears authentication tokens and user-related session data.
	 *
	 * @return void
	 * @since 6.0.0
	 */
	public function logout();

	/**
	 * Sets the current active user for this session.
	 *
	 * Pass null to clear the active user and log out any existing session.
	 *
	 * @param \OCP\IUser|null $user The user to set as active, or null to unset.
	 * @since 8.0.0
	 */
	public function setUser($user);

	/**
	 * Temporarily sets the active user for this session without persisting it in the session storage.
	 *
	 * Useful for request-scoped user overrides that do not affect the actual session state.
	 *
	 * @param \OCP\IUser|null $user The user to set as active, or null to clear.
	 * @since 29.0.0
	 */
	public function setVolatileActiveUser(?IUser $user): void;

	/**
	 * Returns the currently authenticated user for this session, or null if the session has no active user.
	 *
	 * @return \OCP\IUser|null The active user, or null if the session is anonymous, expired, or in incognito mode.
	 * @since 8.0.0
	 */
	public function getUser();

	/**
	 * Checks whether a user is currently logged in for this session.
	 *
	 * @return bool True if a user is authenticated and enabled, false otherwise.
	 * @since 8.0.0
	 */
	public function isLoggedIn();

	/**
	 * Returns the user ID of the impersonator if another user is being impersonated.
	 *
	 * @return string|null The impersonating user's ID, or null if not impersonating.
	 * @since 18.0.0
	 */
	public function getImpersonatingUserID(): ?string;

	/**
	 * Sets or clears the impersonator's user ID in the current session.
	 *
	 * Note: This does not initiate impersonation, but only records the identity of the impersonator in the session.
	 *
	 * If $useCurrentUser is true (default), records the current user's ID as the impersonator.
	 * If false, removes any impersonator information from the session.
	 *
	 * @param bool $useCurrentUser Whether to assign the current user as the impersonator or to clear it.
	 * @since 18.0.0
	 */
	public function setImpersonatingUserID(bool $useCurrentUser = true): void;
}
