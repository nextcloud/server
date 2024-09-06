<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OCP\Files\Config\IHomeMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

/**
 * Mount provider for regular posix home folders
 */
class LocalHomeMountProvider implements IHomeMountProvider {
	public function getHomeMountForUser(IUser $user, IStorageFactory $loader) {
		$arguments = ['user' => $user];
		return new HomeMountPoint($user, '\OC\Files\Storage\Home', '/' . $user->getUID(), $arguments, $loader, null, null, self::class);
	}
}
