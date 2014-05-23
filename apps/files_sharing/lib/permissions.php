<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
namespace OC\Files\Cache;

class Shared_Permissions extends Permissions {

	/**
	 * get the permissions for a single file
	 *
	 * @param int $fileId
	 * @param string $user
	 * @return int permissions
	 */
	public function get($fileId, $user) {

		$permissions = $this->storage->getPermissions();

		return $this->updatePermissions($permissions);
	}

	/**
	 * @param integer $fileId
	 * @param string $user
	 */
	private function getFile($fileId, $user) {
		return $this->get($fileId, $user);
	}

	/**
	 * set the permissions of a file
	 *
	 * @param int $fileId
	 * @param string $user
	 * @param int $permissions
	 */
	public function set($fileId, $user, $permissions) {
		// Not a valid action for Shared Permissions
	}

	/**
	 * get the permissions of multiply files
	 *
	 * @param int[] $fileIds
	 * @param string $user
	 * @return int[]
	 */
	public function getMultiple($fileIds, $user) {
		if (count($fileIds) === 0) {
			return array();
		}
		foreach ($fileIds as $fileId) {
			$filePermissions[$fileId] = $this->get($fileId, $user);
		}
		return $filePermissions;
	}

	/**
	 * get the permissions for all files in a folder
	 *
	 * @param int $parentId
	 * @param string $user
	 * @return int[]
	 */
	public function getDirectoryPermissions($parentId, $user) {

		$fileCacheId = ($parentId === -1) ? $this->storage->getSourceId() : $parentId;

		$query = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `parent` = ?');
		$result = $query->execute(array($fileCacheId));
		$permissions = $this->get($parentId, $user);
		$filePermissions = array();
		while ($row = $result->fetchRow()) {
			$filePermissions[$row['fileid']] = $permissions;
		}
		return $filePermissions;
	}

	/**
	 * remove the permissions for a file
	 *
	 * @param int $fileId
	 * @param string $user
	 */
	public function remove($fileId, $user = null) {
		// Not a valid action for Shared Permissions
	}

	public function removeMultiple($fileIds, $user) {
		// Not a valid action for Shared Permissions
	}

}
