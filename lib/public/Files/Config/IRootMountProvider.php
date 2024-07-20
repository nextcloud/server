<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

use OCP\Files\Storage\IStorageFactory;

/**
 * @since 20.0.0
 */
interface IRootMountProvider {
	/**
	 * Get all root mountpoints of this provider
	 *
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 20.0.0
	 */
	public function getRootMounts(IStorageFactory $loader): array;
}
