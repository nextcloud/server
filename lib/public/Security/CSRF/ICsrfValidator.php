<?php

declare(strict_types=1);

namespace OCP\Security\CSRF;

use OCP\IRequest;

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @since 33.0.0
 */
interface ICsrfValidator {
	/**
	 * Check if a request uses a valid csrf token.
	 *
	 * @since 33.0.0
	 */
	public function validate(IRequest $request): bool;
}
