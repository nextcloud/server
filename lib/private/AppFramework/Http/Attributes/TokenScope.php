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
 * limited to scopes. Methods without it refuse those app passwords.
 *
 * A method's attribute replaces its class's, it does not add to it.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class TokenScope {
	/**
	 * @param string|null $scope the scope the app password needs, null for any app password with scopes
	 */
	public function __construct(
		public readonly ?string $scope = null,
	) {
	}
}
