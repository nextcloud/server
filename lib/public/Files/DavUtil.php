<?php
/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Klaas Freitag <freitag@owncloud.com>
 * @author Markus Goetz <markus@woboq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
