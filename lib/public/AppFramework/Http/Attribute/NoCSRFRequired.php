<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that are not CSRF protected
 *
 * @since 27.0.0
 */
#[Attribute]
class NoCSRFRequired {
}
