<?php

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later
declare(strict_types=1);

namespace OCP\User\Backend;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface IGetUserNameFromLoginNameBackend {
	/**
	 * Returns the username for the given login name in the correct casing
	 *
	 * @since 34.0.0
	 */
	public function getUserNameFromLoginName(string $loginName): string|false;
}
