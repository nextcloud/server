<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files\Config;

use OCP\IUser;

/**
 * Manages the different mount providers
 */
interface IMountProviderCollection {
	/**
	 * Get all configured mount points for the user
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user);

	/**
	 * Add a provider for mount points
	 *
	 * @param \OCP\Files\Config\IMountProvider $provider
	 */
	public function registerProvider(IMountProvider $provider);
}
