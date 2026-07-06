<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

/**
 * @since 14.0.0
 */
interface ICheckPasswordBackend {
	/**
	 * Check if the password is correct without logging in the user
	 * returns the user id or false.
	 *
	 * @param string $loginName The login name
	 * @param string $password The password
	 * @return string|false The uid on success false on failure
	 * @since 14.0.0
	 *
	 */
	public function checkPassword(string $loginName, string $password);
}
