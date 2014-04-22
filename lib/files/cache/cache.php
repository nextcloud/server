<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * Metadata cache for the filesystem
 *
 * don't use this class directly if you need to get metadata, use \OC\Files\Filesystem::getFileInfo instead
 */
class Cache {
	const NOT_FOUND = 0;
	const PARTIAL = 1; //only partial data available, file not cached in the database
	const SHALLOW = 2; //folder in cache, but not all child files are completely scanned
	const COMPLETE = 3;

	/**
	 * @var array partial data for the cache
	 */
	private $partial = array();

	/**
	 * @var string
	 */
	private $storageId;

	/**
	 * numeric storage id
	 *
	 * @var int $numericId
	 */
	private $numericId;

	private $mimetypeIds = array();
	private $mimetypes = array();

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

		$result = \OC_DB::executeAudited(
			'SELECT `numeric_id` FROM `*PREFIX*storages` WHERE `id` = ?',
			array($this->storageId)
		);
		if ($row = $result->fetchRow()) {
			$this->numericId = $row['numeric_id'];
		} else {
			\OC_DB::executeAudited(
				'INSERT INTO `*PREFIX*storages`(`id`) VALUES(?)',
				array($this->storageId)
			);
			$this->numericId = \OC_DB::insertid('*PREFIX*storages');
		}
	}

	public function getNumericStorageId() {
		return $this->numericId;
	}

	public static function storageExists($storageId) {
		if (strlen($storageId) > 64) {
			$storageId = md5($storageId);
		}
		$query = \OC_DB::prepare('SELECT `numeric_id` FROM `*PREFIX*storages` WHERE `id` = ?');
		$result = $query->execute(array($storageId));
		if ($row = $result->fetchRow()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * remove a storage from the cache
	 *
	 * @param string $storageId
	 */
	public static function removeStorage($storageId) {
		$storageCache = new Cache($storageId);

		\OC_DB::executeAudited('
			DELETE FROM `*PREFIX*filecache` WHERE `storage` = ?',
			array($storageCache->getNumericStorageId())
		);

		\OC_DB::executeAudited('
			DELETE FROM `*PREFIX*storages` WHERE `numeric_id` = ?',
			array($storageCache->getNumericStorageId())
		);
	}


	/**
	 * normalize mimetypes
	 *
	 * @param string $mime
	 * @return int
	 */
	public function getMimetypeId($mime) {
		if (empty($mime)) {
			// Can not insert empty string into Oracle NOT NULL column.
			$mime = 'application/octet-stream';
		}
		if (!isset($this->mimetypeIds[$mime])) {
			$result = \OC_DB::executeAudited(
				'SELECT `id` FROM `*PREFIX*mimetypes` WHERE `mimetype` = ?',
				array($mime)
			);
			if ($row = $result->fetchRow()) {
				$this->mimetypeIds[$mime] = $row['id'];
			} else {
				\OC_DB::executeAudited(
					'INSERT INTO `*PREFIX*mimetypes`(`mimetype`) VALUES(?)',
					array($mime)
				);
				$this->mimetypeIds[$mime] = \OC_DB::insertid('*PREFIX*mimetypes');
			}
			$this->mimetypes[$this->mimetypeIds[$mime]] = $mime;
		}
		return $this->mimetypeIds[$mime];
	}

	public function getMimetype($id) {
		if (!isset($this->mimetypes[$id])) {
			$result = \OC_DB::executeAudited(
				'SELECT `mimetype` FROM `*PREFIX*mimetypes` WHERE `id` = ?',
				array($id)
			);
			if ($row = $result->fetchRow()) {
				$this->mimetypes[$id] = $row['mimetype'];
			} else {
				return null;
			}
		}
		return $this->mimetypes[$id];
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string/int $file
	 * @return array | false
	 */
	public function get($file) {
		if (is_string($file) or $file == '') {
			// normalize file
			$file = $this->normalize($file);

			$where = 'WHERE `storage` = ? AND `path_hash` = ?';
			$params = array($this->numericId, md5($file));
		} else { //file id
			$where = 'WHERE `fileid` = ?';
			$params = array($file);
		}
		$result = \OC_DB::executeAudited(
			'SELECT `fileid`, `storage`, `path`, `parent`, `name`,
				`mimetype`, `mimepart`, `size`, `mtime`,
				`encrypted`, `unencrypted_size`, `etag`
			 FROM `*PREFIX*filecache` ' . $where,
			$params
		);
		$data = $result->fetchRow();

		//FIXME hide this HACK in the next database layer, or just use doctrine and get rid of MDB2 and PDO
		//PDO returns false, MDB2 returns null, oracle always uses MDB2, so convert null to false
		if ($data === null) {
			$data = false;
		}

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
			$data['encrypted'] = (bool)$data['encrypted'];
			$data['unencrypted_size'] = (int)$data['unencrypted_size'];
			$data['storage'] = $this->storageId;
			$data['mimetype'] = $this->getMimetype($data['mimetype']);
			$data['mimepart'] = $this->getMimetype($data['mimepart']);
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
			$result = \OC_DB::executeAudited('
				SELECT `fileid`, `storage`, `path`, `parent`,
					`name`, `mimetype`, `mimepart`, `size`,
					`mtime`, `encrypted`,
					`unencrypted_size`, `etag`
				FROM `*PREFIX*filecache`
				WHERE `parent` = ?
				ORDER BY `name` ASC',
				array($fileId)
			);
			$files = $result->fetchAll();
			foreach ($files as &$file) {
				$file['mimetype'] = $this->getMimetype($file['mimetype']);
				$file['mimepart'] = $this->getMimetype($file['mimepart']);
			}
			return $files;
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
			// normalize file
			$file = $this->normalize($file);

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
			$params[] = $this->numericId;
			$valuesPlaceholder = array_fill(0, count($queryParts), '?');

			\OC_DB::executeAudited('
				INSERT INTO `*PREFIX*filecache`(' . implode(', ', $queryParts) . ')
				VALUES(' . implode(', ', $valuesPlaceholder) . ')',
				$params
			);

			return (int)\OC_DB::insertid('*PREFIX*filecache');
		}
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {
		if(isset($data['path'])) {
			// normalize path
			$data['path'] = $this->normalize($data['path']);
		}

		if(isset($data['name'])) {
			// normalize path
			$data['name'] = $this->normalize($data['name']);
		}

		list($queryParts, $params) = $this->buildParts($data);
		$params[] = $id;

		\OC_DB::executeAudited('
			UPDATE `*PREFIX*filecache`
			SET ' . implode(' = ?, ', $queryParts) . '=?
			WHERE `fileid` = ?',
			$params
		);
	}

	/**
	 * extract query parts and params array from data array
	 *
	 * @param array $data
	 * @return array
	 */
	function buildParts(array $data) {
		$fields = array('path', 'parent', 'name', 'mimetype', 'size', 'mtime', 'encrypted', 'unencrypted_size', 'etag');
		$params = array();
		$queryParts = array();
		foreach ($data as $name => $value) {
			if (array_search($name, $fields) !== false) {
				if ($name === 'path') {
					$params[] = md5($value);
					$queryParts[] = '`path_hash`';
				} elseif ($name === 'mimetype') {
					$params[] = $this->getMimetypeId(substr($value, 0, strpos($value, '/')));
					$queryParts[] = '`mimepart`';
					$value = $this->getMimetypeId($value);
				} elseif ($name === 'encrypted') {
					// Boolean to integer conversion
					$value = $value ? 1 : 0;
				}
				$params[] = $value;
				$queryParts[] = '`' . $name . '`';
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
		// normalize path
		$file = $this->normalize($file);

		$pathHash = md5($file);

		$result = \OC_DB::executeAudited('
			SELECT `fileid` FROM `*PREFIX*filecache`
			WHERE `storage` = ? AND `path_hash` = ?',
			array($this->numericId, $pathHash)
		);

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
		if ($file === '') {
			return -1;
		} else {
			$parent = dirname($file);
			if ($parent === '.') {
				$parent = '';
			}
			return $this->getId($parent);
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
		$entry = $this->get($file);
		if ($entry['mimetype'] === 'httpd/unix-directory') {
			$children = $this->getFolderContents($file);
			foreach ($children as $child) {
				$this->remove($child['path']);
			}
		}
		\OC_DB::executeAudited('
			DELETE FROM `*PREFIX*filecache` WHERE `fileid` = ?',
			array($entry['fileid'])
		);

		$permissionsCache = new Permissions($this->storageId);
		$permissionsCache->remove($entry['fileid']);
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		// normalize source and target
		$source = $this->normalize($source);
		$target = $this->normalize($target);

		$sourceData = $this->get($source);
		$sourceId = $sourceData['fileid'];
		$newParentId = $this->getParentId($target);

		if ($sourceData['mimetype'] === 'httpd/unix-directory') {
			//find all child entries
			$result = \OC_DB::executeAudited('
				SELECT `path`, `fileid` FROM `*PREFIX*filecache`
				WHERE `storage` = ? AND `path` LIKE ?',
				array($this->getNumericStorageId(), $source . '/%')
			);
			$childEntries = $result->fetchAll();
			$sourceLength = strlen($source);
			$query = \OC_DB::prepare('UPDATE `*PREFIX*filecache` SET `path` = ?, `path_hash` = ? WHERE `fileid` = ?');

			foreach ($childEntries as $child) {
				$targetPath = $target . substr($child['path'], $sourceLength);
				\OC_DB::executeAudited($query,
					array(
						$targetPath,
						md5($targetPath),
						$child['fileid']
					)
				);
			}
		}

		\OC_DB::executeAudited('
			UPDATE `*PREFIX*filecache`
			SET `path` = ?, `path_hash` = ?, `name` = ?, `parent` =?
			WHERE `fileid` = ?',
			array($target, md5($target), basename($target), $newParentId, $sourceId)
		);
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		\OC_DB::executeAudited('
			DELETE FROM `*PREFIX*filecache` WHERE `storage` = ?',
			array($this->numericId)
		);

		\OC_DB::executeAudited('
			DELETE FROM `*PREFIX*storages` WHERE `id` = ?',
			array($this->storageId)
		);
	}

	/**
	 * @param string $file
	 *
	 * @return int, Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		// normalize file
		$file = $this->normalize($file);

		$pathHash = md5($file);
		$result = \OC_DB::executeAudited('
			SELECT `size`
			FROM `*PREFIX*filecache`
			WHERE `storage` = ? AND `path_hash` = ?',
			array($this->numericId, $pathHash)
		);
		if ($row = $result->fetchRow()) {
			if ((int)$row['size'] === -1) {
				return self::SHALLOW;
			} else {
				return self::COMPLETE;
			}
		} else {
			if (isset($this->partial[$file])) {
				return self::PARTIAL;
			} else {
				return self::NOT_FOUND;
			}
		}
	}

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern
	 * @return array of file data
	 */
	public function search($pattern) {
		// normalize pattern
		$pattern = $this->normalize($pattern);
		
		$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`,
					`mimetype`, `mimepart`, `size`, `mtime`,
					`encrypted`, `unencrypted_size`, `etag`
				FROM `*PREFIX*filecache`
				WHERE `storage` = ? AND ';
		if(\OC_Config::getValue( 'dbtype', 'sqlite' ) === 'oci') {
			//remove starting and ending % from the pattern
			$pattern = trim($pattern, '%');
			$sql .= 'REGEXP_LIKE(`name`, ?, \'i\')';
		} else {
			$sql .= '`name` LIKE ?';
		}

		$result = \OC_DB::executeAudited(
			$sql,
			array($this->numericId, $pattern)
		);
		$files = array();
		while ($row = $result->fetchRow()) {
			$row['mimetype'] = $this->getMimetype($row['mimetype']);
			$row['mimepart'] = $this->getMimetype($row['mimepart']);
			$files[] = $row;
		}
		return $files;
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return array
	 */
	public function searchByMime($mimetype) {
		if (strpos($mimetype, '/')) {
			$where = '`mimetype` = ?';
		} else {
			$where = '`mimepart` = ?';
		}
		$mimetype = $this->getMimetypeId($mimetype);
		
		$result = \OC_DB::executeAudited('
			SELECT `fileid`, `storage`, `path`, `parent`, `name`,
				`mimetype`, `mimepart`, `size`, `mtime`,
				`encrypted`, `unencrypted_size`, `etag`
			FROM `*PREFIX*filecache`
			WHERE ' . $where . ' AND `storage` = ?',
			array($mimetype, $this->numericId)
		);
		
		$files = array();
		while ($row = $result->fetchRow()) {
			$row['mimetype'] = $this->getMimetype($row['mimetype']);
			$row['mimepart'] = $this->getMimetype($row['mimepart']);
			$files[] = $row;
		}
		return $files;
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param $path
	 */
	public function correctFolderSize($path) {
		$this->calculateFolderSize($path);
		if ($path !== '') {
			$parent = dirname($path);
			if ($parent === '.' or $parent === '/') {
				$parent = '';
			}
			$this->correctFolderSize($parent);
		}
	}

	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @return int
	 */
	public function calculateFolderSize($path) {
		$totalSize = 0;
		$entry = $this->get($path);
		if ($entry && $entry['mimetype'] === 'httpd/unix-directory') {
			$id = $entry['fileid'];
			$result = \OC_DB::executeAudited('
				SELECT SUM(`size`), MIN(`size`)
				FROM `*PREFIX*filecache`
				WHERE `parent` = ? AND `storage` = ?',
				array($id, $this->getNumericStorageId())
			);
			if ($row = $result->fetchRow()) {
				list($sum, $min) = array_values($row);
				$sum = (int)$sum;
				$min = (int)$min;
				if ($min === -1) {
					$totalSize = $min;
				} else {
					$totalSize = $sum;
				}
				if ($entry['size'] !== $totalSize) {
					$this->update($id, array('size' => $totalSize));
				}

			}
		}
		return $totalSize;
	}

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll() {
		$result = \OC_DB::executeAudited('
			SELECT `fileid`
			FROM `*PREFIX*filecache`
			WHERE `storage` = ?',
			array($this->numericId)
		);
		$ids = array();
		while ($row = $result->fetchRow()) {
			$ids[] = $row['fileid'];
		}
		return $ids;
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiply incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|bool the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		$query = \OC_DB::prepare('
			SELECT `path`
			FROM `*PREFIX*filecache`
			WHERE `storage` = ? AND `size` = -1
			ORDER BY `fileid` DESC', 1);
		$result = \OC_DB::executeAudited(
			$query,
			array($this->numericId)
		);
		if ($row = $result->fetchRow()) {
			return $row['path'];
		} else {
			return false;
		}
	}

	/**
	 * path of a file on this storage by it's id
	 *
	 * @param int $id
	 * @return string | null
	 */
	public function getPathById($id) {
		$sql = 'SELECT `path` FROM `*PREFIX*filecache` WHERE `fileid` = ? AND `storage` = ?';
		$result = \OC_DB::executeAudited($sql, array($id, $this->getNumericStorageId()));
		if ($row = $result->fetchRow()) {
			return $row['path'];
		} else {
			return null;
		}
	}

	/**
	 * get the storage id of the storage for a file and the internal path of the file
	 * unlike getPathById this does not limit the search to files on this storage and
	 * instead does a global search in the cache table
	 *
	 * @param int $id
	 * @return array, first element holding the storage id, second the path
	 */
	static public function getById($id) {
		$result = \OC_DB::executeAudited('
			SELECT `storage`, `path`
			FROM `*PREFIX*filecache`
			WHERE `fileid` = ?',
			array($id)
		);
		if ($row = $result->fetchRow()) {
			$numericId = $row['storage'];
			$path = $row['path'];
		} else {
			return null;
		}

		$result = \OC_DB::executeAudited('
			SELECT `id`
			FROM `*PREFIX*storages`
			WHERE `numeric_id` = ?',
			array($numericId)
		);
		if ($row = $result->fetchRow()) {
			return array($row['id'], $path);
		} else {
			return null;
		}
	}

	/**
	 * normalize the given path
	 * @param $path
	 * @return string
	 */
	public function normalize($path) {
		return \OC_Util::normalizeUnicode($path);
	}

}
