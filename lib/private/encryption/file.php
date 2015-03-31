<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Encryption;

use OC\Encryption\Util;

class File implements \OCP\Encryption\IFile {

	/** @var Util */
	protected $util;

	public function __construct(Util $util) {
		$this->util = $util;
	}


	/**
	 * get list of users with access to the file
	 *
	 * @param $path to the file
	 * @return array
	 */
	public function getAccessList($path) {

		// Make sure that a share key is generated for the owner too
		list($owner, $ownerPath) = $this->util->getUidAndFilename($path);

		// always add owner to the list of users with access to the file
		$userIds = array($owner);

		if (!$this->util->isFile($ownerPath)) {
			return array('users' => $userIds, 'public' => false);
		}

		$ownerPath = substr($ownerPath, strlen('/files'));
		$ownerPath = $this->util->stripPartialFileExtension($ownerPath);

		// Find out who, if anyone, is sharing the file
		$result = \OCP\Share::getUsersSharingFile($ownerPath, $owner);
		$userIds = \array_merge($userIds, $result['users']);
		$public = $result['public'] || $result['remote'];

		// check if it is a group mount
		if (\OCP\App::isEnabled("files_external")) {
			$mounts = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mounts as $mount) {
				if ($mount['mountpoint'] == substr($ownerPath, 1, strlen($mount['mountpoint']))) {
					$mountedFor = $this->util->getUserWithAccessToMountPoint($mount['applicable']['users'], $mount['applicable']['groups']);
					$userIds = array_merge($userIds, $mountedFor);
				}
			}
		}

		// Remove duplicate UIDs
		$uniqueUserIds = array_unique($userIds);

		return array('users' => $uniqueUserIds, 'public' => $public);
	}

}