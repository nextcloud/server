<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Encryption;

use OC\Cache\CappedMemoryCache;

class File implements \OCP\Encryption\IFile {

	/** @var Util */
	protected $util;

	/**
	 * cache results of already checked folders
	 *
	 * @var array
	 */
	protected $cache;

	public function __construct(Util $util) {
		$this->util = $util;
		$this->cache = new CappedMemoryCache();
	}


	/**
	 * get list of users with access to the file
	 *
	 * @param string $path to the file
	 * @return array  ['users' => $uniqueUserIds, 'public' => $public]
	 */
	public function getAccessList($path) {

		// Make sure that a share key is generated for the owner too
		list($owner, $ownerPath) = $this->util->getUidAndFilename($path);

		// always add owner to the list of users with access to the file
		$userIds = array($owner);

		if (!$this->util->isFile($owner . '/' . $ownerPath)) {
			return array('users' => $userIds, 'public' => false);
		}

		$ownerPath = substr($ownerPath, strlen('/files'));
		$ownerPath = $this->util->stripPartialFileExtension($ownerPath);


		// first get the shares for the parent and cache the result so that we don't
		// need to check all parents for every file
		$parent = dirname($ownerPath);
		if (isset($this->cache[$parent])) {
			$resultForParents = $this->cache[$parent];
		} else {
			$resultForParents = \OCP\Share::getUsersSharingFile($parent, $owner);
			$this->cache[$parent] = $resultForParents;
		}
		$userIds = \array_merge($userIds, $resultForParents['users']);
		$public = $resultForParents['public'] || $resultForParents['remote'];


		// Find out who, if anyone, is sharing the file
		$resultForFile = \OCP\Share::getUsersSharingFile($ownerPath, $owner, false, false, false);
		$userIds = \array_merge($userIds, $resultForFile['users']);
		$public = $resultForFile['public'] || $resultForFile['remote'] || $public;

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
