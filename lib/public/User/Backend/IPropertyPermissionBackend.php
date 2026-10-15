<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
#[Consumable(since: '34.0.0')]
interface IPropertyPermissionBackend {
	/**
	 * @since 34.0.0
	 *
	 * @param IAccountManager::PROPERTY_*|IAccountManager::COLLECTION_* $property
	 * @return bool Whether the user is allowed to edit its own property
	 */
	public function canEditProperty(string $uid, string $property): bool;
}
