<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;

/**
 * Marks the 2FA provider stateless. That means the state of 2FA activation
 * for user will be checked dynamically and not stored in the database.
 */
interface IStatelessProvider extends IProvider {

	public function isTwoFactorAuthEnabledForUser(IUser $user): bool;
}
