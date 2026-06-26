<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;

/**
 * @since 17.0.0
 */
interface IActivatableAtLogin extends IProvider {
	/**
	 * @param IUser $user
	 *
	 * @return ILoginSetupProvider
	 *
	 * @since 17.0.0
	 */
	public function getLoginSetup(IUser $user): ILoginSetupProvider;
}
