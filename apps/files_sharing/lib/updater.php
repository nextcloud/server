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

	// shares which can be removed from oc_share after the delete operation was successful
	static private $toRemove = array();

	/**
	 * walk up the users file tree and update the etags
	 * @param string $user
	 * @param string $path
	 */
	static private function correctUsersFolder($user, $path) {
		// $path points to the mount point which is a virtual folder, so we start with
		// the parent
		$path = '/files' . dirname($path);
		\OC\Files\Filesystem::initMountPoints($user);
		$view = new \OC\Files\View('/' . $user);
		if ($view->file_exists($path)) {
			while ($path !== dirname($path)) {
				$etag = $view->getETag($path);
				$view->putFileInfo($path, array('etag' => $etag));
				$path = dirname($path);
			}
		} else {
			\OCP\Util::writeLog('files_sharing', 'can not update etags on ' . $path . ' for user ' . $user . '. Path does not exists', \OCP\Util::DEBUG);
		}
	}

	/**
	* Correct the parent folders' ETags for all users shared the file at $target
	*
	* @param string $target
	*/
	static public function correctFolders($target) {

		// ignore part files
		if (pathinfo($target, PATHINFO_EXTENSION) === 'part') {
			return false;
		}

		// Correct Shared folders of other users shared with
		$shares = \OCA\Files_Sharing\Helper::getSharesFromItem($target);

		foreach ($shares as $share) {
			if ((int)$share['share_type'] === \OCP\Share::SHARE_TYPE_USER) {
				self::correctUsersFolder($share['share_with'], $share['file_target']);
			} elseif ((int)$share['share_type'] === \OCP\Share::SHARE_TYPE_GROUP) {
				$users = \OC_Group::usersInGroup($share['share_with']);
				foreach ($users as $user) {
					self::correctUsersFolder($user, $share['file_target']);
				}
			} else { //unique name for group share
				self::correctUsersFolder($share['share_with'], $share['file_target']);
			}
		}
	}

	/**
	 * remove all shares for a given file if the file was deleted
	 *
	 * @param string $path
	 */
	private static function removeShare($path) {
		$fileSource = self::$toRemove[$path];

		if (!\OC\Files\Filesystem::file_exists($path)) {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `file_source`=?');
			try	{
				\OC_DB::executeAudited($query, array($fileSource));
			} catch (\Exception $e) {
				\OCP\Util::writeLog('files_sharing', "can't remove share: " . $e->getMessage(), \OCP\Util::WARN);
			}
		}
		unset(self::$toRemove[$path]);
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
		self::renameChildren($params['oldpath'], $params['newpath']);
	}

	/**
	 * @param array $params
	 */
	static public function deleteHook($params) {
		$path = $params['path'];
		self::correctFolders($path);

		$fileInfo = \OC\Files\Filesystem::getFileInfo($path);

		// mark file as deleted so that we can clean up the share table if
		// the file was deleted successfully
		self::$toRemove[$path] =  $fileInfo['fileid'];
	}

	/**
	 * @param array $params
	 */
	static public function postDeleteHook($params) {
		self::removeShare($params['path']);
	}

	/**
	 * update etags if a file was shared
	 * @param array $params
	 */
	static public function postShareHook($params) {

		if ($params['itemType'] === 'folder' || $params['itemType'] === 'file') {

			$shareWith = $params['shareWith'];
			$shareType = $params['shareType'];

			if ($shareType === \OCP\Share::SHARE_TYPE_USER) {
				self::correctUsersFolder($shareWith, '/');
			} elseif ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
				foreach (\OC_Group::usersInGroup($shareWith) as $user) {
					self::correctUsersFolder($user, '/');
				}
			}
		}
	}

	/**
	 * update etags if a file was unshared
	 *
	 * @param array $params
	 */
	static public function postUnshareHook($params) {

		// only update etags for file/folders shared to local users/groups
		if (($params['itemType'] === 'file' || $params['itemType'] === 'folder') &&
				$params['shareType'] !== \OCP\Share::SHARE_TYPE_LINK &&
				$params['shareType'] !== \OCP\Share::SHARE_TYPE_REMOTE) {

			$deletedShares = isset($params['deletedShares']) ? $params['deletedShares'] : array();

			foreach ($deletedShares as $share) {
				if ($share['shareType'] === \OCP\Share::SHARE_TYPE_GROUP) {
					foreach (\OC_Group::usersInGroup($share['shareWith']) as $user) {
						self::correctUsersFolder($user, dirname($share['fileTarget']));
					}
				} else {
					self::correctUsersFolder($share['shareWith'], dirname($share['fileTarget']));
				}
			}
		}
	}

	/**
	 * update etags if file was unshared from self
	 * @param array $params
	 */
	static public function postUnshareFromSelfHook($params) {
		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {
			foreach ($params['unsharedItems'] as $item) {
				if ($item['shareType'] === \OCP\Share::SHARE_TYPE_GROUP) {
					foreach (\OC_Group::usersInGroup($item['shareWith']) as $user) {
						self::correctUsersFolder($user, dirname($item['fileTarget']));
					}
				} else {
					self::correctUsersFolder($item['shareWith'], dirname($item['fileTarget']));
				}
			}
		}
	}

	/**
	 * clean up oc_share table from files which are no longer exists
	 *
	 * This fixes issues from updates from files_sharing < 0.3.5.6 (ownCloud 4.5)
	 * It will just be called during the update of the app
	 */
	static public function fixBrokenSharesOnAppUpdate() {
		// delete all shares where the original file no longer exists
		$findAndRemoveShares = \OC_DB::prepare('DELETE FROM `*PREFIX*share` ' .
			'WHERE `item_type` IN (\'file\', \'folder\') ' .
			'AND `file_source` NOT IN (SELECT `fileid` FROM `*PREFIX*filecache`)'
		);
		$findAndRemoveShares->execute(array());
	}

	/**
	 * rename mount point from the children if the parent was renamed
	 *
	 * @param string $oldPath old path relative to data/user/files
	 * @param string $newPath new path relative to data/user/files
	 */
	static private function renameChildren($oldPath, $newPath) {

		$absNewPath =  \OC\Files\Filesystem::normalizePath('/' . \OCP\User::getUser() . '/files/' . $newPath);
		$absOldPath =  \OC\Files\Filesystem::normalizePath('/' . \OCP\User::getUser() . '/files/' . $oldPath);

		$mountManager = \OC\Files\Filesystem::getMountManager();
		$mountedShares = $mountManager->findIn('/' . \OCP\User::getUser() . '/files/' . $oldPath);
		foreach ($mountedShares as $mount) {
			if ($mount->getStorage()->instanceOfStorage('OCA\Files_Sharing\ISharedStorage')) {
				$mountPoint = $mount->getMountPoint();
				$target = str_replace($absOldPath, $absNewPath, $mountPoint);
				$mount->moveMount($target);
			}
		}
	}

}
