<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute to mark controller methods as not requiring two-factor authentication.
 *
 * Use this wisely and only in 2FA auth apps, e.g. to allow setup during login.
 *
 * @since 33.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class NoTwoFactorRequired {
}
