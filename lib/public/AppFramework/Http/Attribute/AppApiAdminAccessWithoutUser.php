<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for (sub)administrator controller methods that allow access for ExApps when the User is not set.
 *
 * @since 30.0.0
 */
#[Attribute]
class AppApiAdminAccessWithoutUser {
}
