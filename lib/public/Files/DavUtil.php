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
