<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute for controller methods that want to disable the two factor
 * authentification requirements.
 *
 * A user can access the page before the two-factor challenge has been passed
 * (use this wisely and only in two-factor auth apps, e.g. to allow setup during
 * login).
 *
 * @since 34.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
#[Consumable(since: '34.0.0')]
class NoTwoFactorRequired {

}
