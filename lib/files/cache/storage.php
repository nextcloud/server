<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * Class Storage
 *
 * cache storage specific data
 *
 * @package OC\Files\Cache
 */
class Storage {
	private $storageId;
	private $numericId;

	/**
	 * @param \OC\Files\Storage\Storage|string $storage
	 */
	public function __construct($storage) {
		if ($storage instanceof \OC\Files\Storage\Storage) {
			$this->storageId = $storage->getId();
		} else {
			$this->storageId = $storage;
		}
		if (strlen($this->storageId) > 64) {
			$this->storageId = md5($this->storageId);
		}

		$query = \OC_DB::prepare('SELECT `numeric_id` FROM `*PREFIX*storages` WHERE `id` = ?');
		$result = $query->execute(array($this->storageId));
		if ($row = $result->fetchRow()) {
			$this->numericId = $row['numeric_id'];
		} else {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*storages`(`id`) VALUES(?)');
			$query->execute(array($this->storageId));
			$this->numericId = \OC_DB::insertid('*PREFIX*storages');
		}
	}

	public function getNumericId() {
		return $this->numericId;
	}

	public static function getStorageId($numericId) {
		$query = \OC_DB::prepare('SELECT `id` FROM `*PREFIX*storages` WHERE `numeric_id` = ?');
		$result = $query->execute(array($numericId));
		if ($row = $result->fetchRow()) {
			return $row['id'];
		} else {
			return null;
		}
	}
}
