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
	/**
	 * @var IUserMountCache
	 */
	private $userMountCache;

	/**
	 * UserMountCacheListener constructor.
	 *
	 * @param IUserMountCache $userMountCache
	 */
	public function __construct(IUserMountCache $userMountCache) {
		$this->userMountCache = $userMountCache;
	}

	public function listen(Manager $manager) {
		$manager->listen('\OC\User', 'postDelete', [$this->userMountCache, 'removeUserMounts']);
	}
}
