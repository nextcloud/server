<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files;

use OCP\Constants;
use OCP\Files\Mount\IMovableMount;

/**
 * This class provides different helper functions related to WebDAV protocol
 *
 * @since 25.0.0
 */
class DavUtil {
	/**
	 * Compute the fileId to use for dav responses
	 *
	 * @param int $id Id of the file returned by FileInfo::getId
	 * @since 25.0.0
	 */
	public static function getDavFileId(int $id): string {
		$instanceId = \OC_Util::getInstanceId();
		$id = sprintf('%08d', $id);
		return $id . $instanceId;
	}

	/**
	 * Compute the format needed for returning permissions for dav
	 *
	 * @since 25.0.0
	 */
	public static function getDavPermissions(FileInfo $info): string {
		$permissions = $info->getPermissions();
		$p = '';
		if ($info->isShared()) {
			$p .= 'S';
		}
		if ($permissions & Constants::PERMISSION_SHARE) {
			$p .= 'R';
		}
		if ($info->isMounted()) {
			$p .= 'M';
		}
		if ($permissions & Constants::PERMISSION_READ) {
			$p .= 'G';
		}
		if ($permissions & Constants::PERMISSION_DELETE) {
			$p .= 'D';
		}
		if ($permissions & Constants::PERMISSION_UPDATE) {
			$p .= 'NV'; // Renameable, Movable
		}

		// since we always add update permissions for the root of movable mounts
		// we need to check the shared cache item directly to determine if it's writable
		$storage = $info->getStorage();
		if ($info->getInternalPath() === '' && $info->getMountPoint() instanceof IMovableMount) {
			$rootEntry = $storage->getCache()->get('');
			$isWritable = $rootEntry->getPermissions() & Constants::PERMISSION_UPDATE;
		} else {
			$isWritable = $permissions & Constants::PERMISSION_UPDATE;
		}

		if ($info->getType() === FileInfo::TYPE_FILE) {
			if ($isWritable) {
				$p .= 'W';
			}
		} else {
			if ($permissions & Constants::PERMISSION_CREATE) {
				$p .= 'CK';
			}
		}
		return $p;
	}
}
