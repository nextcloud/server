<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Authentication\TwoFactorAuth;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Marks the 2FA provider stateless. That means the state of 2FA activation
 * for user will be checked dynamically and not stored in the database.
 *
 * @since 33.0.0
 */
#[Implementable(since: '33.0.0')]
interface IStatelessProvider extends IProvider {
}
