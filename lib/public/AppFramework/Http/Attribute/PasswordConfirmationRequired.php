<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Attribute for controller methods that require password confirmation, if
 * supported by the active authentication backend.
 *
 * The exact enforcement behavior depends on the password confirmation
 * middleware.
 *
 * In non-strict mode, this normally relies on a recent prior confirmation,
 * currently defined by the middleware as within the last 30 minutes.
 *
 * In strict mode, confirmation is attempted as part of the current request.
 * Credentials must be provided via Basic HTTP authentication.
 *
 * @since 27.0.0
 */
#[Attribute]
class PasswordConfirmationRequired {
	/**
	 * @param bool $strict Whether password confirmation must happen as part of
	 *                     the current request instead of relying on a recent
	 *                     prior confirmation.
	 *
	 * @since 31.0.0
	 */
	public function __construct(
		protected bool $strict = false,
	) {
	}

	/**
	 * Returns whether password confirmation must happen during the current request.
	 *
	 * @since 31.0.0
	 */
	public function getStrict(): bool {
		return $this->strict;
	}
}
