<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Http\Attributes;

use Attribute;

/**
 * Opens a controller method, or every method of a controller class, to app passwords
 * that hold the scope. Methods with neither this nor NoTokenScopeRequired refuse
 * app passwords limited to scopes.
 *
 * A method's attribute replaces its class's, it does not add to it.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class TokenScope {
	/**
	 * @param string $scope the scope the app password needs
	 */
	public function __construct(
		public readonly string $scope,
	) {
	}
}
