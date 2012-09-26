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
	 * @var \OC\Files\Storage\Storage
	 */
	private $storage;

	/**
	 * @var array partial data for the cache
	 */
	private $partial = array();

	private $storageId;

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 */
	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->storageId = $storage->getId();
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string/int $file
	 * @return array
	 */
	public function get($file) {
		if (is_string($file)) {
			$where = 'WHERE `storage` = ? AND `path_hash` = ?';
			$params = array($this->storageId, md5($file));
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
		if (!$data and  is_string($file)) {
			if (isset($this->partial[$file])) {
				$data = $this->partial[$file];
			}
		} else {
			//fix types
			$data['fileid'] = (int)$data['fileid'];
			$data['size'] = (int)$data['size'];
			$data['mtime'] = (int)$data['mtime'];
		}

		return $data;
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return array
	 */
	public function getFolderContents($folder) {
		$fileId = $this->getId($folder);
		if ($fileId > -1) {
			$query = \OC_DB::prepare(
				'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`
			 	 FROM `*PREFIX*filecache` WHERE parent = ?');
			$result = $query->execute(array($fileId));
			return $result->fetchAll();
		} else {
			return array();
		}
	}

	/**
	 * store meta data for a file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 */
	public function put($file, array $data) {
		if (($id = $this->getId($file)) > -1) {
			$this->update($id, $data);
			return $id;
		} else {
			if (isset($this->partial[$file])) { //add any saved partial data
				$data = array_merge($this->partial[$file], $data);
				unset($this->partial[$file]);
			}

			$requiredFields = array('size', 'mtime', 'mimetype');
			foreach ($requiredFields as $field) {
				if (!isset($data[$field])) { //data not complete save as partial and return
					$this->partial[$file] = $data;
					return -1;
				}
			}

			$data['path'] = $file;
			$data['parent'] = $this->getParentId($file);
			$data['name'] = basename($file);

			list($queryParts, $params) = $this->buildParts($data);
			$queryParts[] = '`storage`';
			$params[] = $this->storageId;
			$valuesPlaceholder = array_fill(0, count($queryParts), '?');

			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache`(' . implode(', ', $queryParts) . ') VALUES(' . implode(', ', $valuesPlaceholder) . ')');
			$query->execute($params);

			return (int) \OC_DB::insertid('*PREFIX*filecache');
		}
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {
		list($queryParts, $params) = $this->buildParts($data);
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
	static function buildParts(array $data) {
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
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		$pathHash = md5($file);

		$query = \OC_DB::prepare('SELECT `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?');
		$result = $query->execute(array($this->storageId, $pathHash));

		if ($row = $result->fetchRow()) {
			return $row['fileid'];
		} else {
			return -1;
		}
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file) {
		if ($file === '/' or $file === '') {
			return -1;
		} else {
			return $this->getId(dirname($file));
		}
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->getId($file) != -1;
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$pathHash = md5($file);
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?');
		$query->execute(array($this->storageId, $pathHash));
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*filecache` WHERE storage=?');
		$query->execute(array($this->storageId));
	}
}
