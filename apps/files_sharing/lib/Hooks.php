<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\View;

class Hooks {
	public static function deleteUser($params) {
		$manager = \OC::$server->get(External\Manager::class);

		$manager->removeUserShares($params['uid']);
	}

	public static function unshareChildren($params) {
		$path = Filesystem::getView()->getAbsolutePath($params['path']);
		$view = new View('/');

		// find share mount points within $path and unmount them
		$mountManager = Filesystem::getMountManager();
		$mountedShares = $mountManager->findIn($path);
		foreach ($mountedShares as $mount) {
			if ($mount->getStorage()->instanceOfStorage(ISharedStorage::class)) {
				$mountPoint = $mount->getMountPoint();
				$view->unlink($mountPoint);
			}
		}
	}
}
