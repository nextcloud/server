<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Config;

use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

/**
 * Provides
 *
 * @since 9.1.0
 */
interface IHomeMountProvider {
	/**
	 * Get all mountpoints applicable for the user
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 * @return \OCP\Files\Mount\IMountPoint|null
	 * @since 9.1.0
	 */
	public function getHomeMountForUser(IUser $user, IStorageFactory $loader);
}
