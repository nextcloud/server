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
	 * @param \OC\Files\Storage\Storage|string $storage
	 */
	public function __construct($storage) {
		if ($storage instanceof \OC\Files\Storage\Storage) {
			$this->storageId = $storage->getId();
		} else {
			$this->storageId = $storage;
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
		$query = \OC_DB::prepare('SELECT `permissions` FROM `*PREFIX*permissions` WHERE `user` = ? AND `fileid` = ?');
		$result = $query->execute(array($user, $fileId));
		if ($row = $result->fetchRow()) {
			return $row['permissions'];
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
			$query = \OC_DB::prepare('UPDATE `*PREFIX*permissions` SET `permissions` = ?'
				. ' WHERE `user` = ? AND `fileid` = ?');
		} else {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*permissions`(`permissions`, `user`, `fileid`)'
				. ' VALUES(?, ?,? )');
		}
		$query->execute(array($permissions, $user, $fileId));
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

		$query = \OC_DB::prepare('SELECT `fileid`, `permissions` FROM `*PREFIX*permissions`'
			. ' WHERE `fileid` IN (' . $inPart . ') AND `user` = ?');
		$result = $query->execute($params);
		$filePermissions = array();
		while ($row = $result->fetchRow()) {
			$filePermissions[$row['fileid']] = $row['permissions'];
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
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ?');
			$query->execute(array($fileId));
		} else {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ? AND `user` = ?');
			$query->execute(array($fileId, $user));
		}
	}

	public function removeMultiple($fileIds, $user) {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*permissions` WHERE `fileid` = ? AND `user` = ?');
		foreach ($fileIds as $fileId) {
			$query->execute(array($fileId, $user));
		}
	}
}
