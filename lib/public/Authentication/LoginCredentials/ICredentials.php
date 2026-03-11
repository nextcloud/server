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
	 * Get the user UID.
	 *
	 * @since 12.0.0
	 */
	public function getUID(): string;

	/**
	 * Get the login name the users used to log in.
	 *
	 * @since 12.0.0
	 */
	public function getLoginName(): string;

	/**
	 * Get the password.
	 *
	 * @since 12.0.0
	 *
	 * @throws PasswordUnavailableException
	 */
	public function getPassword(): ?string;
}
