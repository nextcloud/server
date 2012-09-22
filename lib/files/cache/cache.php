<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Cache {
	/**
	 * @var array partial data for the cache
	 */
	private static $partial = array();

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param \OC\Files\File or int $file
	 * @return array
	 */
	static public function get($file) {
		if ($file instanceof \OC\Files\File) {
			$where = 'WHERE `storage` = ? AND `path_hash` = ?';
			$params = array($file->getStorageId(), md5($file->getInternalPath()));
		} else { //file id
			$where = 'WHERE `fileid` = ?';
			$params = array($file);
		}
		$query = \OC_DB::prepare(
			'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`
			 FROM `*PREFIX*filecache` ' . $where);
		$result = $query->execute($params);
		$data = $result->fetchRow();

		//merge partial data
		if (!$data and  $file instanceof \OC\Files\File) {
			$key = $file->getStorageId() . '::' . $file->getInternalPath();
			if (isset(self::$partial[$key])) {
				$data = self::$partial[$key];
			}
		}
		return $data;
	}

	/**
	 * store meta data for a file or folder
	 *
	 * @param \OC\Files\File $file
	 * @param array $data
	 *
	 * @return int file id
	 */
	static public function put(\OC\Files\File $file, array $data) {
		if (($id = self::getId($file)) > -1) {
			self::update($id, $data);
			return $id;
		} else {
			$key = $file->getStorageId() . '::' . $file->getInternalPath();
			if (isset(self::$partial[$key])) { //add any saved partial data
				$data = array_merge(self::$partial[$key], $data);
				unset(self::$partial[$key]);
			}

			$requiredFields = array('size', 'mtime', 'mimetype');
			foreach ($requiredFields as $field) {
				if (!isset($data[$field])) { //data not complete save as partial and return
					self::$partial[$key] = $data;
					return -1;
				}
			}

			$data['path'] = $file->getInternalPath();
			$data['parent'] = self::getParentId($file);
			$data['name'] = basename($file->getInternalPath());

			list($queryParts, $params) = self::buildParts($data);
			$queryParts[] = '`storage`';
			$params[] = $file->getStorageId();
			$valuesPlaceholder = array_fill(0, count($queryParts), '?');

			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache`(' . implode(', ', $queryParts) . ') VALUES(' . implode(', ', $valuesPlaceholder) . ')');
			$query->execute($params);

			return \OC_DB::insertid('*PREFIX*filecache');
		}
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	static public function update($id, array $data) {
		list($queryParts, $params) = self::buildParts($data);
		$params[] = $id;

		$query = \OC_DB::prepare('UPDATE `*PREFIX*filecache` SET ' . implode(' = ?, ', $queryParts) . '=? WHERE fileid = ?');
		$query->execute($params);
	}

	/**
	 * extract query parts and params array from data array
	 *
	 * @param array $data
	 * @return array
	 */
	private static function buildParts(array $data) {
		$fields = array('path', 'parent', 'name', 'mimetype', 'size', 'mtime');

		$params = array();
		$queryParts = array();
		foreach ($data as $name => $value) {
			if (array_search($name, $fields) !== false) {
				$params[] = $value;
				$queryParts[] = '`' . $name . '`';
				if ($name === 'path') {
					$params[] = md5($value);
					$queryParts[] = '`path_hash`';
				} elseif ($name === 'mimetype') {
					$params[] = substr($value, 0, strpos($value, '/'));
					$queryParts[] = '`mimepart`';
				}
			}
		}
		return array($queryParts, $params);
	}

	/**
	 * get the file id for a file
	 *
	 * @param \OC\Files\File $file
	 * @return int
	 */
	static public function getId(\OC\Files\File $file) {
		$storageId = $file->getStorageId();
		$pathHash = md5($file->getInternalPath());

		$query = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?');
		$result = $query->execute(array($storageId, $pathHash));

		if ($row = $result->fetchRow()) {
			return $row['fileid'];
		} else {
			return -1;
		}
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param \OC\Files\File $file
	 * @return int
	 */
	static public function getParentId(\OC\Files\File $file) {
		$path = $file->getInternalPath();
		if ($path === '/' or $path === '') {
			return -1;
		} else {
			return self::getId(new \OC\Files\File($file->getStorage(), dirname($path)));
		}
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param \OC\Files\File $file
	 * @return bool
	 */
	static public function inCache(\OC\Files\File $file) {
		return self::getId($file) != -1;
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param \OC\Files\File $file
	 */
	static public function remove(\OC\Files\File $file) {
		$storageId = $file->getStorageId();
		$pathHash = md5($file->getInternalPath());
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?');
		$query->execute(array($storageId, $pathHash));
	}

	/**
	 * remove all entries for files that are stored on $storage form the cache
	 *
	 * @param \OC\Files\Storage\Storage $storage
	 */
	static public function removeStorage(\OC\Files\Storage\Storage $storage) {
		$storageId = $storage->getId();
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*filecache` WHERE storage=?');
		$query->execute(array($storageId));
	}
}
