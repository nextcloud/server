<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Http\Attributes;

use Attribute;

/**
 * Opens a controller method to every app password, whatever its scopes, over a TokenScope
 * on its class. For what a token does to itself, such as revoking it.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class NoTokenScopeRequired {
}
