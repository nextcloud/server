<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that require the password to be confirmed with in the last 30 minutes
 *
 * @since 27.0.0
 */
#[Attribute]
class PasswordConfirmationRequired {
}
