<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Storage;

use OCP\Share\IShare;

/**
 * Interface for a storage that is based on a file share
 *
 * @since 30.0.0
 */
interface ISharedStorage extends IStorage {
	/**
	 * The the associated share
	 *
	 * @since 30.0.0
	 */
	public function getShare(): IShare;
}
