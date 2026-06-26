<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;

/**
 * Marks a 2FA provider as activale by the administrator. This means that an
 * admin can activate this provider without user interaction. The provider,
 * therefore, must not require any user-provided configuration.
 *
 * @since 15.0.0
 */
interface IDeactivatableByAdmin extends IProvider {
	/**
	 * Disable this provider for the given user.
	 *
	 * @param IUser $user the user to deactivate this provider for
	 *
	 * @return void
	 *
	 * @since 15.0.0
	 */
	public function disableFor(IUser $user);
}
