<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
interface IUserFolder extends Folder {

	/**
	 * @param bool $useCache - Use the cached value if available instead of recalculate.
	 * @return array{used: int|float, free: int|float, total: int|float, quota: int|float}
	 * @since 33.0.0
	 */
	public function getUserQuota(bool $useCache = true): array;

}
