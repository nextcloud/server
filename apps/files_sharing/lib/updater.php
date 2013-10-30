<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2013 Michael Gapczynski mtgap@owncloud.com
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

class Shared_Updater {

	/**
	* Correct the parent folders' ETags for all users shared the file at $target
	*
	* @param string $target
	*/
	static public function correctFolders($target) {
		$uid = \OCP\User::getUser();
		$uidOwner = \OC\Files\Filesystem::getOwner($target);
		$info = \OC\Files\Filesystem::getFileInfo($target);
		$checkedUser = array($uidOwner);
		// Correct Shared folders of other users shared with
		$users = \OCP\Share::getUsersItemShared('file', $info['fileid'], $uidOwner, true);
		if (!empty($users)) {
			while (!empty($users)) {
				$reshareUsers = array();
				foreach ($users as $user) {
					if ( !in_array($user, $checkedUser) ) {
						$etag = \OC\Files\Filesystem::getETag('');
						\OCP\Config::setUserValue($user, 'files_sharing', 'etag', $etag);
						// Look for reshares
						$reshareUsers = array_merge($reshareUsers, \OCP\Share::getUsersItemShared('file', $info['fileid'], $user, true));
						$checkedUser[] = $user;
					}
				}
				$users = $reshareUsers;
			}
		}
	}

	/**
	 * @brief remove all shares for a given file if the file was deleted
	 *
	 * @param string $path
	 */
	private static function removeShare($path) {
		$fileInfo = \OC\Files\Filesystem::getFileInfo($path);
		$fileSource = $fileInfo['fileid'];

		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `file_source`=?');
		try	{
			\OC_DB::executeAudited($query, array($fileSource));
		} catch (\Exception $e) {
			\OCP\Util::writeLog('files_sharing', "can't remove share: " . $e->getMessage(), \OCP\Util::WARN);
		}
	}

	/**
	 * @param array $params
	 */
	static public function writeHook($params) {
		self::correctFolders($params['path']);
	}

	/**
	 * @param array $params
	 */
	static public function renameHook($params) {
		self::correctFolders($params['newpath']);
		self::correctFolders(pathinfo($params['oldpath'], PATHINFO_DIRNAME));
	}

	/**
	 * @param array $params
	 */
	static public function deleteHook($params) {
		self::correctFolders($params['path']);
		self::removeShare($params['path']);
	}


	/**
	 * @param array $params
	 */
	static public function shareHook($params) {
		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
			$uidOwner = \OCP\User::getUser();
			$users = \OCP\Share::getUsersItemShared($params['itemType'], $params['fileSource'], $uidOwner, true);
			if (!empty($users)) {
				while (!empty($users)) {
					$reshareUsers = array();
					foreach ($users as $user) {
						if ($user !== $uidOwner) {
							$etag = \OC\Files\Filesystem::getETag('');
							\OCP\Config::setUserValue($user, 'files_sharing', 'etag', $etag);
							// Look for reshares
							$reshareUsers = array_merge($reshareUsers, \OCP\Share::getUsersItemShared('file', $params['fileSource'], $user, true));
						}
					}
					$users = $reshareUsers;
				}
			}
		}
	}

}
