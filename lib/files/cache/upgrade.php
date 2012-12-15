<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Upgrade {
	static $permissionsCaches = array();

	static function upgrade() {
		$insertQuery = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache`( `fileid`, `storage`, `path`, `path_hash`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`, `encrypted` )
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

		try {
			$oldEntriesQuery = \OC_DB::prepare('SELECT * FROM `*PREFIX*fscache` ORDER BY `id` ASC'); //sort ascending to ensure the parent gets inserted before a child
		} catch (\Exception $e) {
			return;
		}
		try {
			$oldEntriesResult = $oldEntriesQuery->execute();
		} catch (\Exception $e) {
			return;
		}
		if (!$oldEntriesResult) {
			return;
		}

		$checkExistingQuery = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `fileid` = ?');

		while ($row = $oldEntriesResult->fetchRow()) {
			if($checkExistingQuery->execute(array($row['id']))->fetchRow()){
				continue;
			}

			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($row['path']);
			/**
			 * @var \OC\Files\Storage\Storage $storage
			 * @var string $internalPath;
			 */
			$pathHash = md5($internalPath);
			$storageId = $storage->getId();
			$parentId = ($internalPath === '') ? -1 : $row['parent'];

			$insertQuery->execute(array($row['id'], $storageId, $internalPath, $pathHash, $parentId, $row['name'], $row['mimetype'], $row['mimepart'], $row['size'], $row['mtime'], $row['encrypted']));

			$permissions = ($row['writable']) ? \OCP\PERMISSION_ALL : \OCP\PERMISSION_READ;
			$permissionsCache = self::getPermissionsCache($storage);
			$permissionsCache->set($row['id'], $row['user'], $permissions);
		}
	}

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 * @return Permissions
	 */
	static function getPermissionsCache($storage) {
		$storageId = $storage->getId();
		if (!isset(self::$permissionsCaches[$storageId])) {
			self::$permissionsCaches[$storageId] = $storage->getPermissionsCache();
		}
		return self::$permissionsCaches[$storageId];
	}
}
