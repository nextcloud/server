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
	public static function getDavPermissions(FileInfo $info, FileInfo $parent): string {
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
		if (self::canRename($info, $parent)) {
			$p .= 'N'; // Renamable
		}
		if ($permissions & Constants::PERMISSION_UPDATE) {
			$p .= 'V'; // Movable
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

	public static function canRename(FileInfo $info, FileInfo $parent): bool {
		// the root of a movable mountpoint can be renamed regardless of the file permissions
		if ($info->getMountPoint() instanceof IMovableMount && $info->getInternalPath() === '') {
			return true;
		}

		// we allow renaming the file if either the file has update permissions
		if ($info->isUpdateable()) {
			return true;
		}

		// or the file can be deleted and the parent has create permissions
		if ($info->getStorage() instanceof IHomeStorage && $info->getInternalPath() === 'files') {
			// can't rename the users home
			return false;
		}

		return $info->isDeletable() && $parent->isCreatable();
	}
}
