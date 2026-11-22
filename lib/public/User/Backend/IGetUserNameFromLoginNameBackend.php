<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IGetUserNameFromLoginNameBackend {
	/**
	 * Returns the username for the given login name in the correct casing
	 *
	 * @since 36.0.0
	 */
	public function getUserNameFromLoginName(string $loginName): string|false;
}
