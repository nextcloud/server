<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\LoginCredentials;

use OCP\Authentication\Exceptions\CredentialsUnavailableException;

/**
 * @since 12
 */
interface IStore {
	/**
	 * Get login credentials of the currently logged in user
	 *
	 * @since 12
	 *
	 * @throws CredentialsUnavailableException
	 * @return ICredentials the login credentials of the current user
	 */
	public function getLoginCredentials(): ICredentials;
}
