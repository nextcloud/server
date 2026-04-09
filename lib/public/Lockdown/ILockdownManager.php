<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Lockdown;

use OC\Authentication\Token\IToken;

/**
 * @since 9.2
 */
interface ILockdownManager {
	/**
	 * Enable the lockdown restrictions
	 *
	 * @since 9.2
	 */
	public function enable();

	/**
	 * Set the active token to get the restrictions from and enable the lockdown
	 *
	 * @param IToken $token
	 * @since 9.2
	 */
	public function setToken(IToken $token);

	/**
	 * Check whether or not filesystem access is allowed
	 *
	 * @return bool
	 * @since 9.2
	 */
	public function canAccessFilesystem();
}
