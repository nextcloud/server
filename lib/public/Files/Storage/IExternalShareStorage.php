<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Storage;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Share\External\IExternalShare;

/**
 * Interface for a storage that is based on an external file share
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IExternalShareStorage extends IStorage {
	/**
	 * The associated external share
	 *
	 * @since 35.0.0
	 */
	public function getShare(): IExternalShare;
}
