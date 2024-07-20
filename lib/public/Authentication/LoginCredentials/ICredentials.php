<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\LoginCredentials;

use OCP\Authentication\Exceptions\PasswordUnavailableException;

/**
 * @since 12
 */
interface ICredentials {
	/**
	 * Get the user UID
	 *
	 * @since 12
	 *
	 * @return string
	 */
	public function getUID();

	/**
	 * Get the login name the users used to login
	 *
	 * @since 12
	 *
	 * @return string
	 */
	public function getLoginName();

	/**
	 * Get the password
	 *
	 * @since 12
	 *
	 * @return string|null
	 * @throws PasswordUnavailableException
	 */
	public function getPassword();
}
