<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
	 * @throws \RuntimeException
	 */
	public function __construct($storage) {
		if ($storage instanceof \OC\Files\Storage\Storage) {
			$this->storageId = $storage->getId();
		} else {
			$this->storageId = $storage;
		}
		$this->storageId = self::adjustStorageId($this->storageId);

		$sql = 'SELECT `numeric_id` FROM `*PREFIX*storages` WHERE `id` = ?';
		$result = \OC_DB::executeAudited($sql, array($this->storageId));
		if ($row = $result->fetchRow()) {
			$this->numericId = $row['numeric_id'];
		} else {
			$connection = \OC_DB::getConnection();
			if ($connection->insertIfNotExist('*PREFIX*storages', ['id' => $this->storageId])) {
				$this->numericId = \OC_DB::insertid('*PREFIX*storages');
			} else {
				$result = \OC_DB::executeAudited($sql, array($this->storageId));
				if ($row = $result->fetchRow()) {
					$this->numericId = $row['numeric_id'];
				} else {
					throw new \RuntimeException('Storage could neither be inserted nor be selected from the database');
				}
			}
		}
	}

	/**
	 * Adjusts the storage id to use md5 if too long
	 * @param string $storageId storage id
	 * @return unchanged $storageId if its length is less than 64 characters,
	 * else returns the md5 of $storageId
	 */
	public static function adjustStorageId($storageId) {
		if (strlen($storageId) > 64) {
			return md5($storageId);
		}
		return $storageId;
	}

	/**
	 * @return string
	 */
	public function getNumericId() {
		return $this->numericId;
	}

	/**
	 * @return string|null
	 */
	public static function getStorageId($numericId) {

		$sql = 'SELECT `id` FROM `*PREFIX*storages` WHERE `numeric_id` = ?';
		$result = \OC_DB::executeAudited($sql, array($numericId));
		if ($row = $result->fetchRow()) {
			return $row['id'];
		} else {
			return null;
		}
	}

	/**
	 * @return string|null
	 */
	public static function getNumericStorageId($storageId) {
		$storageId = self::adjustStorageId($storageId);

		$sql = 'SELECT `numeric_id` FROM `*PREFIX*storages` WHERE `id` = ?';
		$result = \OC_DB::executeAudited($sql, array($storageId));
		if ($row = $result->fetchRow()) {
			return $row['numeric_id'];
		} else {
			return null;
		}
	}

	/**
	 * @param string $storageId
	 * @return bool
	 */
	public static function exists($storageId) {
		return !is_null(self::getNumericStorageId($storageId));
	}

	/**
	 * remove the entry for the storage
	 *
	 * @param string $storageId
	 */
	public static function remove($storageId) {
		$storageId = self::adjustStorageId($storageId);
		$numericId = self::getNumericStorageId($storageId);
		$sql = 'DELETE FROM `*PREFIX*storages` WHERE `id` = ?';
		\OC_DB::executeAudited($sql, array($storageId));

		if (!is_null($numericId)) {
			$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `storage` = ?';
			\OC_DB::executeAudited($sql, array($numericId));
		}
	}
}
