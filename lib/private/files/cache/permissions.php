<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Permissions {
	/**
	 * @var string $storageId
	 */
	private $storageId;

	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	protected $storage;

	/**
	 * @param \OC\Files\Storage\Storage|string $storage
	 */
	public function __construct($storage) {
		if ($storage instanceof \OC\Files\Storage\Storage) {
			$this->storageId = $storage->getId();
			$this->storage = $storage;
		} else {
			$this->storageId = $storage;
			$mountManager = \OC\Files\Filesystem::getMountManager();
			$mount = $mountManager->findByStorageId($this->storageId);
			$firstMountPoint = reset($mount);
			if ($firstMountPoint instanceof \OC\Files\Storage\Storage) {
				$storage = $firstMountPoint->getStorage();
				$this->storage = $storage;
			}
		}

	}

	/**
	 * get the permissions for a single file
	 *
	 * @param int $fileId
	 * @param string $user
	 * @return int (-1 if file no permissions set)
	 */
	public function get($fileId, $user) {
		$sql = 'SELECT `permissions` FROM `*PREFIX*permissions` WHERE `user` = ? AND `fileid` = ?';
		$result = \OC_DB::executeAudited($sql, array($user, $fileId));
		if ($row = $result->fetchRow()) {
			return $this->updatePermissions($row['permissions']);
		} else {
			return -1;
		}
	}

	/**
	 * set the permissions of a file
	 *
	 * @param int $fileId
	 * @param string $user
	 * @param int $permissions
	 */
	public function set($fileId, $user, $permissions) {
		if (self::get($fileId, $user) !== -1) {
			$sql = 'UPDATE `*PREFIX*permissions` SET `permissions` = ? WHERE `user` = ? AND `fileid` = ?';
		} else {
			$sql = 'INSERT INTO `*PREFIX*permissions`(`permissions`, `user`, `fileid`) VALUES(?, ?,? )';
		}
		\OC_DB::executeAudited($sql, array($permissions, $user, $fileId));
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
		$params = $fileIds;
		$params[] = $user;
		$inPart = implode(', ', array_fill(0, count($fileIds), '?'));

		$sql = 'SELECT `fileid`, `permissions` FROM `*PREFIX*permissions`'
			. ' WHERE `fileid` IN (' . $inPart . ') AND `user` = ?';
		$result = \OC_DB::executeAudited($sql, $params);
		$filePermissions = array();
		while ($row = $result->fetchRow()) {
			$filePermissions[$row['fileid']] = $this->updatePermissions($row['permissions']);
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
		$sql = 'SELECT `*PREFIX*permissions`.`fileid`, `permissions`
			FROM `*PREFIX*permissions`
			INNER JOIN `*PREFIX*filecache` ON `*PREFIX*permissions`.`fileid` = `*PREFIX*filecache`.`fileid`
			WHERE `*PREFIX*filecache`.`parent` = ? AND `*PREFIX*permissions`.`user` = ?';

		$result = \OC_DB::executeAudited($sql, array($parentId, $user));
		$filePermissions = array();
		while ($row = $result->fetchRow()) {
			$filePermissions[$row['fileid']] = $this->updatePermissions($row['permissions']);
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
		if (is_null($user)) {
			\OC_DB::executeAudited('DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ?', array($fileId));
		} else {
			$sql = 'DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ? AND `user` = ?';
			\OC_DB::executeAudited($sql, array($fileId, $user));
		}
	}

	public function removeMultiple($fileIds, $user) {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ? AND `user` = ?');
		foreach ($fileIds as $fileId) {
			\OC_DB::executeAudited($query, array($fileId, $user));
		}
	}

	/**
	 * get the list of users which have permissions stored for a file
	 *
	 * @param int $fileId
	 */
	public function getUsers($fileId) {
		$sql = 'SELECT `user` FROM `*PREFIX*permissions` WHERE `fileid` = ?';
		$result = \OC_DB::executeAudited($sql, array($fileId));
		$users = array();
		while ($row = $result->fetchRow()) {
			$users[] = $row['user'];
		}
		return $users;
	}

	/**
	 * check if admin removed the share permission for the user and update the permissions
	 *
	 * @param int $permissions
	 * @return int
	 */
	protected function updatePermissions($permissions) {
		if (\OCP\Util::isSharingDisabledForUser()) {
			$permissions &= ~\OCP\PERMISSION_SHARE;
		}
		return $permissions;
	}
}
