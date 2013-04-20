<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

use \OC\Files\Mount;
use \OC\Files\Filesystem;

class BackgroundWatcher {
	static private function checkUpdate($id) {
		$cacheItem = Cache::getById($id);
		if (is_null($cacheItem)) {
			return;
		}
		list($storageId, $internalPath) = $cacheItem;
		$mounts = Mount::findByStorageId($storageId);

		if (count($mounts) === 0) {
			//if the storage we need isn't mounted on default, try to find a user that has access to the storage
			$permissionsCache = new Permissions($storageId);
			$users = $permissionsCache->getUsers($id);
			if (count($users) === 0) {
				return;
			}
			Filesystem::initMountPoints($users[0]);
			$mounts = Mount::findByStorageId($storageId);
			if (count($mounts) === 0) {
				return;
			}
		}
		$storage = $mounts[0]->getStorage();
		$watcher = new Watcher($storage);
		$watcher->checkUpdate($internalPath);
	}

	/**
	 * get the next fileid in the cache
	 *
	 * @param int $previous
	 * @return int
	 */
	static private function getNextFileId($previous) {
		$query = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `fileid` > ? ORDER BY `fileid` ASC', 1);
		$result = $query->execute(array($previous));
		if ($row = $result->fetchRow()) {
			return $row['fileid'];
		} else {
			return 0;
		}
	}

	static public function checkNext() {
		$previous = \OC_Appconfig::getValue('files', 'backgroundwatcher_previous', 0);
		$next = self::getNextFileId($previous);
		error_log($next);
		\OC_Appconfig::setValue('files', 'backgroundwatcher_previous', $next);
		self::checkUpdate($next);
	}

	static public function checkAll() {
		$previous = 0;
		$next = 1;
		while ($next != 0) {
			$next = self::getNextFileId($previous);
			self::checkUpdate($next);
		}
	}
}
