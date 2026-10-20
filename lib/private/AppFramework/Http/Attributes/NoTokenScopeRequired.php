<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Http\Attributes;

use Attribute;

/**
 * Opens a controller method, or every method of a controller class, to every app password,
 * whatever its scopes. For what a token does to itself, such as revoking it.
 *
 * A method's attribute replaces its class's, like with TokenScope.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class NoTokenScopeRequired {
}
