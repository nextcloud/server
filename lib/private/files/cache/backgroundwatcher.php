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
	static $folderMimetype = null;

	static private function getFolderMimetype() {
		if (!is_null(self::$folderMimetype)) {
			return self::$folderMimetype;
		}
		$sql = 'SELECT `id` FROM `*PREFIX*mimetypes` WHERE `mimetype` = ?';
		$result = \OC_DB::executeAudited($sql, array('httpd/unix-directory'));
		$row = $result->fetchRow();
		return $row['id'];
	}

	static private function checkUpdate($id) {
		$cacheItem = Cache::getById($id);
		if (is_null($cacheItem)) {
			return;
		}
		list($storageId, $internalPath) = $cacheItem;
		$mounts = Filesystem::getMountByStorageId($storageId);

		if (count($mounts) === 0) {
			//if the storage we need isn't mounted on default, try to find a user that has access to the storage
			$permissionsCache = new Permissions($storageId);
			$users = $permissionsCache->getUsers($id);
			if (count($users) === 0) {
				return;
			}
			Filesystem::initMountPoints($users[0]);
			$mounts = Filesystem::getMountByStorageId($storageId);
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
	 * @param bool $folder
	 * @return int
	 */
	static private function getNextFileId($previous, $folder) {
		if ($folder) {
			$stmt = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `fileid` > ? AND `mimetype` = ? ORDER BY `fileid` ASC', 1);
		} else {
			$stmt = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `fileid` > ? AND `mimetype` != ? ORDER BY `fileid` ASC', 1);
		}
		$result = \OC_DB::executeAudited($stmt, array($previous,self::getFolderMimetype()));
		if ($row = $result->fetchRow()) {
			return $row['fileid'];
		} else {
			return 0;
		}
	}

	static public function checkNext() {
		// check both 1 file and 1 folder, this way new files are detected quicker because there are less folders than files usually
		$previousFile = \OC_Appconfig::getValue('files', 'backgroundwatcher_previous_file', 0);
		$previousFolder = \OC_Appconfig::getValue('files', 'backgroundwatcher_previous_folder', 0);
		$nextFile = self::getNextFileId($previousFile, false);
		$nextFolder = self::getNextFileId($previousFolder, true);
		\OC_Appconfig::setValue('files', 'backgroundwatcher_previous_file', $nextFile);
		\OC_Appconfig::setValue('files', 'backgroundwatcher_previous_folder', $nextFolder);
		if ($nextFile > 0) {
			self::checkUpdate($nextFile);
		}
		if ($nextFolder > 0) {
			self::checkUpdate($nextFolder);
		}
	}

	static public function checkAll() {
		$previous = 0;
		$next = 1;
		while ($next != 0) {
			$next = self::getNextFileId($previous, true);
			self::checkUpdate($next);
		}
		$previous = 0;
		$next = 1;
		while ($next != 0) {
			$next = self::getNextFileId($previous, false);
			self::checkUpdate($next);
		}
	}
}
