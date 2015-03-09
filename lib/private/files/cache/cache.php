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
	protected $partial = array();

	/**
	 * @var string
	 */
	protected $storageId;

	/**
	 * @var Storage $storageCache
	 */
	protected $storageCache;

	protected static $mimetypeIds = array();
	protected static $mimetypes = array();

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

		$this->storageCache = new Storage($storage);
	}

	public function getNumericStorageId() {
		return $this->storageCache->getNumericId();
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
		if (empty(self::$mimetypeIds)) {
			$this->loadMimetypes();
		}

		if (!isset(self::$mimetypeIds[$mime])) {
			try {
				$connection = \OC_DB::getConnection();
				$connection->insertIfNotExist('*PREFIX*mimetypes', [
					'mimetype'	=> $mime,
				]);
				$this->loadMimetypes();
			} catch (\Doctrine\DBAL\DBALException $e) {
				\OC_Log::write('core', 'Exception during mimetype insertion: ' . $e->getmessage(), \OC_Log::DEBUG);
				return -1;
			}
		}

		return self::$mimetypeIds[$mime];
	}

	public function getMimetype($id) {
		if (empty(self::$mimetypes)) {
			$this->loadMimetypes();
		}

		return isset(self::$mimetypes[$id]) ? self::$mimetypes[$id] : null;
	}

	public function loadMimetypes() {
		self::$mimetypeIds = self::$mimetypes = array();

		$result = \OC_DB::executeAudited('SELECT `id`, `mimetype` FROM `*PREFIX*mimetypes`', array());
		if ($result) {
			while ($row = $result->fetchRow()) {
				self::$mimetypeIds[$row['mimetype']] = $row['id'];
				self::$mimetypes[$row['id']] = $row['mimetype'];
			}
		}
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string /int $file
	 * @return array|false
	 */
	public function get($file) {
		if (is_string($file) or $file == '') {
			// normalize file
			$file = $this->normalize($file);

			$where = 'WHERE `storage` = ? AND `path_hash` = ?';
			$params = array($this->getNumericStorageId(), md5($file));
		} else { //file id
			$where = 'WHERE `fileid` = ?';
			$params = array($file);
		}
		$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`,
					   `storage_mtime`, `encrypted`, `unencrypted_size`, `etag`, `permissions`
				FROM `*PREFIX*filecache` ' . $where;
		$result = \OC_DB::executeAudited($sql, $params);
		$data = $result->fetchRow();

		//FIXME hide this HACK in the next database layer, or just use doctrine and get rid of MDB2 and PDO
		//PDO returns false, MDB2 returns null, oracle always uses MDB2, so convert null to false
		if ($data === null) {
			$data = false;
		}

		//merge partial data
		if (!$data and is_string($file)) {
			if (isset($this->partial[$file])) {
				$data = $this->partial[$file];
			}
		} else {
			//fix types
			$data['fileid'] = (int)$data['fileid'];
			$data['size'] = 0 + $data['size'];
			$data['mtime'] = (int)$data['mtime'];
			$data['storage_mtime'] = (int)$data['storage_mtime'];
			$data['encrypted'] = (bool)$data['encrypted'];
			$data['unencrypted_size'] = 0 + $data['unencrypted_size'];
			$data['storage'] = $this->storageId;
			$data['mimetype'] = $this->getMimetype($data['mimetype']);
			$data['mimepart'] = $this->getMimetype($data['mimepart']);
			if ($data['storage_mtime'] == 0) {
				$data['storage_mtime'] = $data['mtime'];
			}
			$data['permissions'] = (int)$data['permissions'];
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
		return $this->getFolderContentsById($fileId);
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param int $fileId the file id of the folder
	 * @return array
	 */
	public function getFolderContentsById($fileId) {
		if ($fileId > -1) {
			$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`,
						   `storage_mtime`, `encrypted`, `unencrypted_size`, `etag`, `permissions`
					FROM `*PREFIX*filecache` WHERE `parent` = ? ORDER BY `name` ASC';
			$result = \OC_DB::executeAudited($sql, array($fileId));
			$files = $result->fetchAll();
			foreach ($files as &$file) {
				$file['mimetype'] = $this->getMimetype($file['mimetype']);
				$file['mimepart'] = $this->getMimetype($file['mimepart']);
				if ($file['storage_mtime'] == 0) {
					$file['storage_mtime'] = $file['mtime'];
				}
				if ($file['encrypted'] or ($file['unencrypted_size'] > 0 and $file['mimetype'] === 'httpd/unix-directory')) {
					$file['encrypted_size'] = $file['size'];
					$file['size'] = $file['unencrypted_size'];
				}
				$file['permissions'] = (int)$file['permissions'];
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
			$data['name'] = \OC_Util::basename($file);

			list($queryParts, $params) = $this->buildParts($data);
			$queryParts[] = '`storage`';
			$params[] = $this->getNumericStorageId();

			$params = array_map(function($item) {
				return trim($item, "`");
			}, $params);
			$queryParts = array_map(function($item) {
				return trim($item, "`");
			}, $queryParts);
			$values = array_combine($queryParts, $params);
			if (\OC::$server->getDatabaseConnection()->insertIfNotExist('*PREFIX*filecache', $values)) {
				return (int)\OC_DB::insertid('*PREFIX*filecache');
			}

			return $this->getId($file);
		}
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {

		if (isset($data['path'])) {
			// normalize path
			$data['path'] = $this->normalize($data['path']);
		}

		if (isset($data['name'])) {
			// normalize path
			$data['name'] = $this->normalize($data['name']);
		}

		list($queryParts, $params) = $this->buildParts($data);
		$params[] = $id;

		$sql = 'UPDATE `*PREFIX*filecache` SET ' . implode(' = ?, ', $queryParts) . '=? WHERE `fileid` = ?';
		\OC_DB::executeAudited($sql, $params);
	}

	/**
	 * extract query parts and params array from data array
	 *
	 * @param array $data
	 * @return array
	 */
	function buildParts(array $data) {
		$fields = array(
			'path', 'parent', 'name', 'mimetype', 'size', 'mtime', 'storage_mtime', 'encrypted', 'unencrypted_size',
			'etag', 'permissions');
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
				} elseif ($name === 'storage_mtime') {
					if (!isset($data['mtime'])) {
						$params[] = $value;
						$queryParts[] = '`mtime`';
					}
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
		// normalize file
		$file = $this->normalize($file);

		$pathHash = md5($file);

		$sql = 'SELECT `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?';
		$result = \OC_DB::executeAudited($sql, array($this->getNumericStorageId(), $pathHash));
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
		$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `fileid` = ?';
		\OC_DB::executeAudited($sql, array($entry['fileid']));
		if ($entry['mimetype'] === 'httpd/unix-directory') {
			$this->removeChildren($entry);
		}
	}

	private function getSubFolders($entry) {
		$children = $this->getFolderContentsById($entry['fileid']);
		return array_filter($children, function ($child) {
			return $child['mimetype'] === 'httpd/unix-directory';
		});
	}

	private function removeChildren($entry) {
		$subFolders = $this->getSubFolders($entry);
		foreach ($subFolders as $folder) {
			$this->removeChildren($folder);
		}
		$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `parent` = ?';
		\OC_DB::executeAudited($sql, array($entry['fileid']));
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
			$sql = 'SELECT `path`, `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path` LIKE ?';
			$result = \OC_DB::executeAudited($sql, array($this->getNumericStorageId(), $source . '/%'));
			$childEntries = $result->fetchAll();
			$sourceLength = strlen($source);
			$query = \OC_DB::prepare('UPDATE `*PREFIX*filecache` SET `path` = ?, `path_hash` = ? WHERE `fileid` = ?');

			foreach ($childEntries as $child) {
				$targetPath = $target . substr($child['path'], $sourceLength);
				\OC_DB::executeAudited($query, array($targetPath, md5($targetPath), $child['fileid']));
			}
		}

		$sql = 'UPDATE `*PREFIX*filecache` SET `path` = ?, `path_hash` = ?, `name` = ?, `parent` =? WHERE `fileid` = ?';
		\OC_DB::executeAudited($sql, array($target, md5($target), basename($target), $newParentId, $sourceId));
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `storage` = ?';
		\OC_DB::executeAudited($sql, array($this->getNumericStorageId()));

		$sql = 'DELETE FROM `*PREFIX*storages` WHERE `id` = ?';
		\OC_DB::executeAudited($sql, array($this->storageId));
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
		$sql = 'SELECT `size` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?';
		$result = \OC_DB::executeAudited($sql, array($this->getNumericStorageId(), $pathHash));
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
	 * @return array an array of file data
	 */
	public function search($pattern) {

		// normalize pattern
		$pattern = $this->normalize($pattern);


		$sql = '
			SELECT `fileid`, `storage`, `path`, `parent`, `name`,
				`mimetype`, `mimepart`, `size`, `mtime`, `encrypted`,
				`unencrypted_size`, `etag`, `permissions`
			FROM `*PREFIX*filecache`
			WHERE `storage` = ? AND `name` ILIKE ?';
		$result = \OC_DB::executeAudited($sql,
			array($this->getNumericStorageId(), $pattern)
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
		$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`, `encrypted`, `unencrypted_size`, `etag`, `permissions`
				FROM `*PREFIX*filecache` WHERE ' . $where . ' AND `storage` = ?';
		$mimetype = $this->getMimetypeId($mimetype);
		$result = \OC_DB::executeAudited($sql, array($mimetype, $this->getNumericStorageId()));
		$files = array();
		while ($row = $result->fetchRow()) {
			$row['mimetype'] = $this->getMimetype($row['mimetype']);
			$row['mimepart'] = $this->getMimetype($row['mimepart']);
			$files[] = $row;
		}
		return $files;
	}

	/**
	 * Search for files by tag of a given users.
	 *
	 * Note that every user can tag files differently.
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return array file data
	 */
	public function searchByTag($tag, $userId) {
		$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, ' .
			'`mimetype`, `mimepart`, `size`, `mtime`, ' .
			'`encrypted`, `unencrypted_size`, `etag`, `permissions` ' .
			'FROM `*PREFIX*filecache` `file`, ' .
			'`*PREFIX*vcategory_to_object` `tagmap`, ' .
			'`*PREFIX*vcategory` `tag` ' .
			// JOIN filecache to vcategory_to_object
			'WHERE `file`.`fileid` = `tagmap`.`objid` ' .
			// JOIN vcategory_to_object to vcategory
			'AND `tagmap`.`type` = `tag`.`type` ' .
			'AND `tagmap`.`categoryid` = `tag`.`id` ' .
			// conditions
			'AND `file`.`storage` = ? ' .
			'AND `tag`.`type` = \'files\' ' .
			'AND `tag`.`uid` = ? ';
		if (is_int($tag)) {
			$sql .= 'AND `tag`.`id` = ? ';
		} else {
			$sql .= 'AND `tag`.`category` = ? ';
		}
		$result = \OC_DB::executeAudited(
			$sql,
			array(
				$this->getNumericStorageId(),
				$userId,
				$tag
			)
		);
		$files = array();
		while ($row = $result->fetchRow()) {
			$files[] = $row;
		}
		return $files;
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param string|boolean $path
	 * @param array $data (optional) meta data of the folder
	 */
	public function correctFolderSize($path, $data = null) {
		$this->calculateFolderSize($path, $data);
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
	 * @param array $entry (optional) meta data of the folder
	 * @return int
	 */
	public function calculateFolderSize($path, $entry = null) {
		$totalSize = 0;
		if (is_null($entry) or !isset($entry['fileid'])) {
			$entry = $this->get($path);
		}
		if (isset($entry['mimetype']) && $entry['mimetype'] === 'httpd/unix-directory') {
			$id = $entry['fileid'];
			$sql = 'SELECT SUM(`size`) AS f1, MIN(`size`) AS f2, ' .
				'SUM(`unencrypted_size`) AS f3 ' .
				'FROM `*PREFIX*filecache` ' .
				'WHERE `parent` = ? AND `storage` = ?';
			$result = \OC_DB::executeAudited($sql, array($id, $this->getNumericStorageId()));
			if ($row = $result->fetchRow()) {
				$result->closeCursor();
				list($sum, $min, $unencryptedSum) = array_values($row);
				$sum = 0 + $sum;
				$min = 0 + $min;
				$unencryptedSum = 0 + $unencryptedSum;
				if ($min === -1) {
					$totalSize = $min;
				} else {
					$totalSize = $sum;
				}
				$update = array();
				if ($entry['size'] !== $totalSize) {
					$update['size'] = $totalSize;
				}
				if (!isset($entry['unencrypted_size']) or $entry['unencrypted_size'] !== $unencryptedSum) {
					$update['unencrypted_size'] = $unencryptedSum;
				}
				if (count($update) > 0) {
					$this->update($id, $update);
				}
				if ($totalSize !== -1 and $unencryptedSum > 0) {
					$totalSize = $unencryptedSum;
				}
			} else {
				$result->closeCursor();
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
		$sql = 'SELECT `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ?';
		$result = \OC_DB::executeAudited($sql, array($this->getNumericStorageId()));
		$ids = array();
		while ($row = $result->fetchRow()) {
			$ids[] = $row['fileid'];
		}
		return $ids;
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiple incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|bool the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		$query = \OC_DB::prepare('SELECT `path` FROM `*PREFIX*filecache`'
			. ' WHERE `storage` = ? AND `size` = -1 ORDER BY `fileid` DESC', 1);
		$result = \OC_DB::executeAudited($query, array($this->getNumericStorageId()));
		if ($row = $result->fetchRow()) {
			return $row['path'];
		} else {
			return false;
		}
	}

	/**
	 * get the path of a file on this storage by it's id
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getPathById($id) {
		$sql = 'SELECT `path` FROM `*PREFIX*filecache` WHERE `fileid` = ? AND `storage` = ?';
		$result = \OC_DB::executeAudited($sql, array($id, $this->getNumericStorageId()));
		if ($row = $result->fetchRow()) {
			// Oracle stores empty strings as null...
			if ($row['path'] === null) {
				return '';
			}
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
		$sql = 'SELECT `storage`, `path` FROM `*PREFIX*filecache` WHERE `fileid` = ?';
		$result = \OC_DB::executeAudited($sql, array($id));
		if ($row = $result->fetchRow()) {
			$numericId = $row['storage'];
			$path = $row['path'];
		} else {
			return null;
		}

		if ($id = Storage::getStorageId($numericId)) {
			return array($id, $path);
		} else {
			return null;
		}
	}

	/**
	 * normalize the given path
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalize($path) {

		return trim(\OC_Util::normalizeUnicode($path), '/');
	}
}
