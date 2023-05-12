<?php
/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Files;

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
		$p = '';
		if ($info->isShared()) {
			$p .= 'S';
		}
		if ($info->isShareable()) {
			$p .= 'R';
		}
		if ($info->isMounted()) {
			$p .= 'M';
		}
		if ($info->isReadable()) {
			$p .= 'G';
		}
		if ($info->isDeletable()) {
			$p .= 'D';
		}
		if ($info->isUpdateable()) {
			$p .= 'NV'; // Renameable, Moveable
		}
		if ($info->getType() === FileInfo::TYPE_FILE) {
			if ($info->isUpdateable()) {
				$p .= 'W';
			}
		} else {
			if ($info->isCreatable()) {
				$p .= 'CK';
			}
		}
		return $p;
	}
}
