<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface IUser {
	/**
	 * get the user id
	 *
	 * @return string
	 */
	public function getUID();

	/**
	 * get the display name for the user, if no specific display name is set it will fallback to the user id
	 *
	 * @return string
	 */
	public function getDisplayName();

	/**
	 * set the display name for the user
	 *
	 * @param string $displayName
	 * @return bool
	 */
	public function setDisplayName($displayName);

	/**
	 * returns the timestamp of the user's last login or 0 if the user did never
	 * login
	 *
	 * @return int
	 */
	public function getLastLogin();

	/**
	 * updates the timestamp of the most recent login of this user
	 */
	public function updateLastLoginTimestamp();

	/**
	 * Delete the user
	 *
	 * @return bool
	 */
	public function delete();

	/**
	 * Set the password of the user
	 *
	 * @param string $password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 */
	public function setPassword($password, $recoveryPassword = null);

	/**
	 * get the users home folder to mount
	 *
	 * @return string
	 */
	public function getHome();

	/**
	 * Get the name of the backend class the user is connected with
	 *
	 * @return string
	 */
	public function getBackendClassName();

	/**
	 * check if the backend allows the user to change his avatar on Personal page
	 *
	 * @return bool
	 */
	public function canChangeAvatar();

	/**
	 * check if the backend supports changing passwords
	 *
	 * @return bool
	 */
	public function canChangePassword();

	/**
	 * check if the backend supports changing display names
	 *
	 * @return bool
	 */
	public function canChangeDisplayName();

	/**
	 * check if the user is enabled
	 *
	 * @return bool
	 */
	public function isEnabled();

	/**
	 * set the enabled status for the user
	 *
	 * @param bool $enabled
	 */
	public function setEnabled($enabled);
}
