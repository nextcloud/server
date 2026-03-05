<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;
use OCP\Settings\IDelegatedSettings;

/**
 * Attribute for controller methods that should be only accessible with
 * full admin or partial admin permissions.
 *
 * @since 27.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AuthorizedAdminSetting {
	/**
	 * @param class-string<IDelegatedSettings> $settings A settings section the user needs to be able to access
	 * @since 27.0.0
	 */
	public function __construct(
		protected string $settings,
	) {
	}

	/**
	 *
	 * @return class-string<IDelegatedSettings>
	 * @since 27.0.0
	 */
	public function getSettings(): string {
		return $this->settings;
	}
}
