<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\AppFramework\Http\Attribute;

use Attribute;

/**
 * Opens a controller method, or every method of a controller class, to app passwords
 * that hold the scope. Methods without it refuse app passwords limited to scopes.
 *
 * A method's attribute replaces its class's, it does not add to it.
 *
 * Apps may only require scopes named after their own app id, `<appid>:<resource>:<action>`.
 * Other scopes are refused on apps that aren't shipped with Nextcloud.
 *
 * @experimental 36.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class TokenScope {
	/**
	 * @param string $scope the scope the app password needs
	 * @experimental 36.0.0
	 */
	public function __construct(
		public readonly string $scope,
	) {
	}
}
