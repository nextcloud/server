<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;

/**
 * Interface for admins that have personal settings. These settings will be shown in the
 * security sections. Some information like the display name of the provider is read
 * from the provider directly.
 *
 * @since 15.0.0
 */
interface IProvidesPersonalSettings extends IProvider {
	/**
	 * @param IUser $user
	 *
	 * @return IPersonalProviderSettings
	 *
	 * @since 15.0.0
	 */
	public function getPersonalSettings(IUser $user): IPersonalProviderSettings;
}
