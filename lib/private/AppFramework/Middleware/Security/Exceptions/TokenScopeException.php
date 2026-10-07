<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware\Security\Exceptions;

use OCP\AppFramework\Http;

/**
 * Thrown when a token limited to scopes requests a route those scopes don't open
 */
class TokenScopeException extends SecurityException {
	public function __construct() {
		parent::__construct('This app password cannot access this resource', Http::STATUS_FORBIDDEN);
	}
}
