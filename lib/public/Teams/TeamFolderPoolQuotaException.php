<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A proposed allocation does not fit in the financing pool.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
class TeamFolderPoolQuotaException extends \RuntimeException {
	/** @since 36.0.0 */
	public function __construct(public readonly TeamFolderStoragePool $pool) {
		parent::__construct('The team folder quota exceeds the storage pool limit', 409);
	}
}