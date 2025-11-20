<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Config;

use OC\User\Manager;
use OCP\Files\Config\IUserMountCache;

/**
 * Listen to hooks and update the mount cache as needed
 */
class UserMountCacheListener {
	public function __construct(
		private IUserMountCache $userMountCache,
	) {
	}

	public function listen(Manager $manager) {
		$manager->listen('\OC\User', 'postDelete', [$this->userMountCache, 'removeUserMounts']);
	}
}
