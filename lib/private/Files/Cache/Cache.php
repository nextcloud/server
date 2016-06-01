<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Florin Peter <github@florin-peter.de>
 * @author Jens-Christian Fischer <jens-christian.fischer@switch.ch>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author TheSFReader <TheSFReader@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use \OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;

/**
 * Metadata cache for a storage
 *
 * The cache stores the metadata for all files and folders in a storage and is kept up to date trough the following mechanisms:
 *
 * - Scanner: scans the storage and updates the cache where needed
 * - Watcher: checks for changes made to the filesystem outside of the ownCloud instance and rescans files and folder when a change is detected
 * - Updater: listens to changes made to the filesystem inside of the ownCloud instance and updates the cache where needed
 * - ChangePropagator: updates the mtime and etags of parent folders whenever a change to the cache is made to the cache by the updater
 */
class Cache implements ICache {
	use MoveFromCacheTrait {
		MoveFromCacheTrait::moveFromCache as moveFromCacheFallback;
	}

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

	/** @var IMimeTypeLoader */
	protected $mimetypeLoader;

	/**
	 * @var IDBConnection
	 */
	protected $connection;

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
		$this->mimetypeLoader = \OC::$server->getMimeTypeLoader();
		$this->connection = \OC::$server->getDatabaseConnection();
	}

	/**
	 * Get the numeric storage id for this cache's storage
	 *
	 * @return int
	 */
	public function getNumericStorageId() {
		return $this->storageCache->getNumericId();
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string | int $file either the path of a file or folder or the file id for a file or folder
	 * @return ICacheEntry|false the cache entry as array of false if the file is not found in the cache
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
					   `storage_mtime`, `encrypted`, `etag`, `permissions`, `checksum`
				FROM `*PREFIX*filecache` ' . $where;
		$result = $this->connection->executeQuery($sql, $params);
		$data = $result->fetch();

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
			return $data;
		} else {
			//fix types
			$data['fileid'] = (int)$data['fileid'];
			$data['parent'] = (int)$data['parent'];
			$data['size'] = 0 + $data['size'];
			$data['mtime'] = (int)$data['mtime'];
			$data['storage_mtime'] = (int)$data['storage_mtime'];
			$data['encryptedVersion'] = (int)$data['encrypted'];
			$data['encrypted'] = (bool)$data['encrypted'];
			$data['storage'] = $this->storageId;
			$data['mimetype'] = $this->mimetypeLoader->getMimetypeById($data['mimetype']);
			$data['mimepart'] = $this->mimetypeLoader->getMimetypeById($data['mimepart']);
			if ($data['storage_mtime'] == 0) {
				$data['storage_mtime'] = $data['mtime'];
			}
			$data['permissions'] = (int)$data['permissions'];
			return new CacheEntry($data);
		}
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return ICacheEntry[]
	 */
	public function getFolderContents($folder) {
		$fileId = $this->getId($folder);
		return $this->getFolderContentsById($fileId);
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param int $fileId the file id of the folder
	 * @return ICacheEntry[]
	 */
	public function getFolderContentsById($fileId) {
		if ($fileId > -1) {
			$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`,
						   `storage_mtime`, `encrypted`, `etag`, `permissions`, `checksum`
					FROM `*PREFIX*filecache` WHERE `parent` = ? ORDER BY `name` ASC';
			$result = $this->connection->executeQuery($sql, [$fileId]);
			$files = $result->fetchAll();
			foreach ($files as &$file) {
				$file['mimetype'] = $this->mimetypeLoader->getMimetypeById($file['mimetype']);
				$file['mimepart'] = $this->mimetypeLoader->getMimetypeById($file['mimepart']);
				if ($file['storage_mtime'] == 0) {
					$file['storage_mtime'] = $file['mtime'];
				}
				$file['permissions'] = (int)$file['permissions'];
				$file['mtime'] = (int)$file['mtime'];
				$file['storage_mtime'] = (int)$file['storage_mtime'];
				$file['size'] = 0 + $file['size'];
			}
			return array_map(function (array $data) {
				return new CacheEntry($data);
			}, $files);
		} else {
			return array();
		}
	}

	/**
	 * insert or update meta data for a file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 */
	public function put($file, array $data) {
		if (($id = $this->getId($file)) > -1) {
			$this->update($id, $data);
			return $id;
		} else {
			return $this->insert($file, $data);
		}
	}

	/**
	 * insert meta data for a new file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 */
	public function insert($file, array $data) {
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

		$queryParts = array_map(function ($item) {
			return trim($item, "`");
		}, $queryParts);
		$values = array_combine($queryParts, $params);
		if (\OC::$server->getDatabaseConnection()->insertIfNotExist('*PREFIX*filecache', $values, [
			'storage',
			'path_hash',
		])
		) {
			return (int)$this->connection->lastInsertId('*PREFIX*filecache');
		}

		// The file was created in the mean time
		if (($id = $this->getId($file)) > -1) {
			$this->update($id, $data);
			return $id;
		} else {
			throw new \RuntimeException('File entry could not be inserted with insertIfNotExist() but could also not be selected with getId() in order to perform an update. Please try again.');
		}
	}

	/**
	 * update the metadata of an existing file or folder in the cache
	 *
	 * @param int $id the fileid of the existing file or folder
	 * @param array $data [$key => $value] the metadata to update, only the fields provided in the array will be updated, non-provided values will remain unchanged
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
		// duplicate $params because we need the parts twice in the SQL statement
		// once for the SET part, once in the WHERE clause
		$params = array_merge($params, $params);
		$params[] = $id;

		// don't update if the data we try to set is the same as the one in the record
		// some databases (Postgres) don't like superfluous updates
		$sql = 'UPDATE `*PREFIX*filecache` SET ' . implode(' = ?, ', $queryParts) . '=? ' .
			'WHERE (' .
			implode(' <> ? OR ', $queryParts) . ' <> ? OR ' .
			implode(' IS NULL OR ', $queryParts) . ' IS NULL' .
			') AND `fileid` = ? ';
		$this->connection->executeQuery($sql, $params);

	}

	/**
	 * extract query parts and params array from data array
	 *
	 * @param array $data
	 * @return array [$queryParts, $params]
	 *        $queryParts: string[], the (escaped) column names to be set in the query
	 *        $params: mixed[], the new values for the columns, to be passed as params to the query
	 */
	protected function buildParts(array $data) {
		$fields = array(
			'path', 'parent', 'name', 'mimetype', 'size', 'mtime', 'storage_mtime', 'encrypted',
			'etag', 'permissions', 'checksum');

		$doNotCopyStorageMTime = false;
		if (array_key_exists('mtime', $data) && $data['mtime'] === null) {
			// this horrific magic tells it to not copy storage_mtime to mtime
			unset($data['mtime']);
			$doNotCopyStorageMTime = true;
		}

		$params = array();
		$queryParts = array();
		foreach ($data as $name => $value) {
			if (array_search($name, $fields) !== false) {
				if ($name === 'path') {
					$params[] = md5($value);
					$queryParts[] = '`path_hash`';
				} elseif ($name === 'mimetype') {
					$params[] = $this->mimetypeLoader->getId(substr($value, 0, strpos($value, '/')));
					$queryParts[] = '`mimepart`';
					$value = $this->mimetypeLoader->getId($value);
				} elseif ($name === 'storage_mtime') {
					if (!$doNotCopyStorageMTime && !isset($data['mtime'])) {
						$params[] = $value;
						$queryParts[] = '`mtime`';
					}
				} elseif ($name === 'encrypted') {
					if(isset($data['encryptedVersion'])) {
						$value = $data['encryptedVersion'];
					} else {
						// Boolean to integer conversion
						$value = $value ? 1 : 0;
					}
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
	 * A file id is a numeric id for a file or folder that's unique within an owncloud instance which stays the same for the lifetime of a file
	 *
	 * File ids are easiest way for apps to store references to a file since unlike paths they are not affected by renames or sharing
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		// normalize file
		$file = $this->normalize($file);

		$pathHash = md5($file);

		$sql = 'SELECT `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?';
		$result = $this->connection->executeQuery($sql, array($this->getNumericStorageId(), $pathHash));
		if ($row = $result->fetch()) {
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
			$parent = $this->getParentPath($file);
			return (int)$this->getId($parent);
		}
	}

	private function getParentPath($path) {
		$parent = dirname($path);
		if ($parent === '.') {
			$parent = '';
		}
		return $parent;
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
	 * when removing a folder from the cache all files and folders inside the folder will be removed as well
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$entry = $this->get($file);
		$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `fileid` = ?';
		$this->connection->executeQuery($sql, array($entry['fileid']));
		if ($entry['mimetype'] === 'httpd/unix-directory') {
			$this->removeChildren($entry);
		}
	}

	/**
	 * Get all sub folders of a folder
	 *
	 * @param array $entry the cache entry of the folder to get the subfolders for
	 * @return array[] the cache entries for the subfolders
	 */
	private function getSubFolders($entry) {
		$children = $this->getFolderContentsById($entry['fileid']);
		return array_filter($children, function ($child) {
			return $child['mimetype'] === 'httpd/unix-directory';
		});
	}

	/**
	 * Recursively remove all children of a folder
	 *
	 * @param array $entry the cache entry of the folder to remove the children of
	 * @throws \OC\DatabaseException
	 */
	private function removeChildren($entry) {
		$subFolders = $this->getSubFolders($entry);
		foreach ($subFolders as $folder) {
			$this->removeChildren($folder);
		}
		$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `parent` = ?';
		$this->connection->executeQuery($sql, array($entry['fileid']));
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->moveFromCache($this, $source, $target);
	}

	/**
	 * Get the storage id and path needed for a move
	 *
	 * @param string $path
	 * @return array [$storageId, $internalPath]
	 */
	protected function getMoveInfo($path) {
		return [$this->getNumericStorageId(), $path];
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param \OCP\Files\Cache\ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @throws \OC\DatabaseException
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		if ($sourceCache instanceof Cache) {
			// normalize source and target
			$sourcePath = $this->normalize($sourcePath);
			$targetPath = $this->normalize($targetPath);

			$sourceData = $sourceCache->get($sourcePath);
			$sourceId = $sourceData['fileid'];
			$newParentId = $this->getParentId($targetPath);

			list($sourceStorageId, $sourcePath) = $sourceCache->getMoveInfo($sourcePath);
			list($targetStorageId, $targetPath) = $this->getMoveInfo($targetPath);

			// sql for final update
			$moveSql = 'UPDATE `*PREFIX*filecache` SET `storage` =  ?, `path` = ?, `path_hash` = ?, `name` = ?, `parent` =? WHERE `fileid` = ?';

			if ($sourceData['mimetype'] === 'httpd/unix-directory') {
				//find all child entries
				$sql = 'SELECT `path`, `fileid` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path` LIKE ?';
				$result = $this->connection->executeQuery($sql, [$sourceStorageId, $this->connection->escapeLikeParameter($sourcePath) . '/%']);
				$childEntries = $result->fetchAll();
				$sourceLength = strlen($sourcePath);
				$this->connection->beginTransaction();
				$query = $this->connection->prepare('UPDATE `*PREFIX*filecache` SET `storage` = ?, `path` = ?, `path_hash` = ? WHERE `fileid` = ?');

				foreach ($childEntries as $child) {
					$newTargetPath = $targetPath . substr($child['path'], $sourceLength);
					$query->execute([$targetStorageId, $newTargetPath, md5($newTargetPath), $child['fileid']]);
				}
				$this->connection->executeQuery($moveSql, [$targetStorageId, $targetPath, md5($targetPath), basename($targetPath), $newParentId, $sourceId]);
				$this->connection->commit();
			} else {
				$this->connection->executeQuery($moveSql, [$targetStorageId, $targetPath, md5($targetPath), basename($targetPath), $newParentId, $sourceId]);
			}
		} else {
			$this->moveFromCacheFallback($sourceCache, $sourcePath, $targetPath);
		}
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$sql = 'DELETE FROM `*PREFIX*filecache` WHERE `storage` = ?';
		$this->connection->executeQuery($sql, array($this->getNumericStorageId()));

		$sql = 'DELETE FROM `*PREFIX*storages` WHERE `id` = ?';
		$this->connection->executeQuery($sql, array($this->storageId));
	}

	/**
	 * Get the scan status of a file
	 *
	 * - Cache::NOT_FOUND: File is not in the cache
	 * - Cache::PARTIAL: File is not stored in the cache but some incomplete data is known
	 * - Cache::SHALLOW: The folder and it's direct children are in the cache but not all sub folders are fully scanned
	 * - Cache::COMPLETE: The file or folder, with all it's children) are fully scanned
	 *
	 * @param string $file
	 *
	 * @return int Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		// normalize file
		$file = $this->normalize($file);

		$pathHash = md5($file);
		$sql = 'SELECT `size` FROM `*PREFIX*filecache` WHERE `storage` = ? AND `path_hash` = ?';
		$result = $this->connection->executeQuery($sql, array($this->getNumericStorageId(), $pathHash));
		if ($row = $result->fetch()) {
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
	 * @param string $pattern the search pattern using SQL search syntax (e.g. '%searchstring%')
	 * @return ICacheEntry[] an array of cache entries where the name matches the search pattern
	 */
	public function search($pattern) {
		// normalize pattern
		$pattern = $this->normalize($pattern);


		$sql = '
			SELECT `fileid`, `storage`, `path`, `parent`, `name`,
				`mimetype`, `mimepart`, `size`, `mtime`, `encrypted`,
				`etag`, `permissions`, `checksum`
			FROM `*PREFIX*filecache`
			WHERE `storage` = ? AND `name` ILIKE ?';
		$result = $this->connection->executeQuery($sql,
			[$this->getNumericStorageId(), $pattern]
		);

		$files = [];
		while ($row = $result->fetch()) {
			$row['mimetype'] = $this->mimetypeLoader->getMimetypeById($row['mimetype']);
			$row['mimepart'] = $this->mimetypeLoader->getMimetypeById($row['mimepart']);
			$files[] = $row;
		}
		return array_map(function(array $data) {
			return new CacheEntry($data);
		}, $files);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype either a full mimetype to search ('text/plain') or only the first part of a mimetype ('image')
	 *        where it will search for all mimetypes in the group ('image/*')
	 * @return ICacheEntry[] an array of cache entries where the mimetype matches the search
	 */
	public function searchByMime($mimetype) {
		if (strpos($mimetype, '/')) {
			$where = '`mimetype` = ?';
		} else {
			$where = '`mimepart` = ?';
		}
		$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, `mimetype`, `mimepart`, `size`, `mtime`, `encrypted`, `etag`, `permissions`, `checksum`
				FROM `*PREFIX*filecache` WHERE ' . $where . ' AND `storage` = ?';
		$mimetype = $this->mimetypeLoader->getId($mimetype);
		$result = $this->connection->executeQuery($sql, array($mimetype, $this->getNumericStorageId()));
		$files = array();
		while ($row = $result->fetch()) {
			$row['mimetype'] = $this->mimetypeLoader->getMimetypeById($row['mimetype']);
			$row['mimepart'] = $this->mimetypeLoader->getMimetypeById($row['mimepart']);
			$files[] = $row;
		}
		return array_map(function (array $data) {
			return new CacheEntry($data);
		}, $files);
	}

	/**
	 * Search for files by tag of a given users.
	 *
	 * Note that every user can tag files differently.
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return ICacheEntry[] file data
	 */
	public function searchByTag($tag, $userId) {
		$sql = 'SELECT `fileid`, `storage`, `path`, `parent`, `name`, ' .
			'`mimetype`, `mimepart`, `size`, `mtime`, ' .
			'`encrypted`, `etag`, `permissions`, `checksum` ' .
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
		$result = $this->connection->executeQuery(
			$sql,
			[
				$this->getNumericStorageId(),
				$userId,
				$tag
			]
		);
		$files = array();
		while ($row = $result->fetch()) {
			$files[] = $row;
		}
		return array_map(function (array $data) {
			return new CacheEntry($data);
		}, $files);
	}

	/**
	 * Re-calculate the folder size and the size of all parent folders
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
	 * calculate the size of a folder and set it in the cache
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
			$sql = 'SELECT SUM(`size`) AS f1, MIN(`size`) AS f2 ' .
				'FROM `*PREFIX*filecache` ' .
				'WHERE `parent` = ? AND `storage` = ?';
			$result = $this->connection->executeQuery($sql, array($id, $this->getNumericStorageId()));
			if ($row = $result->fetch()) {
				$result->closeCursor();
				list($sum, $min) = array_values($row);
				$sum = 0 + $sum;
				$min = 0 + $min;
				if ($min === -1) {
					$totalSize = $min;
				} else {
					$totalSize = $sum;
				}
				$update = array();
				if ($entry['size'] !== $totalSize) {
					$update['size'] = $totalSize;
				}
				if (count($update) > 0) {
					$this->update($id, $update);
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
		$result = $this->connection->executeQuery($sql, array($this->getNumericStorageId()));
		$ids = array();
		while ($row = $result->fetch()) {
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
		$query = $this->connection->prepare('SELECT `path` FROM `*PREFIX*filecache`'
			. ' WHERE `storage` = ? AND `size` = -1 ORDER BY `fileid` DESC', 1);
		$query->execute([$this->getNumericStorageId()]);
		if ($row = $query->fetch()) {
			return $row['path'];
		} else {
			return false;
		}
	}

	/**
	 * get the path of a file on this storage by it's file id
	 *
	 * @param int $id the file id of the file or folder to search
	 * @return string|null the path of the file (relative to the storage) or null if a file with the given id does not exists within this cache
	 */
	public function getPathById($id) {
		$sql = 'SELECT `path` FROM `*PREFIX*filecache` WHERE `fileid` = ? AND `storage` = ?';
		$result = $this->connection->executeQuery($sql, array($id, $this->getNumericStorageId()));
		if ($row = $result->fetch()) {
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
	 * @deprecated use getPathById() instead
	 * @return array first element holding the storage id, second the path
	 */
	static public function getById($id) {
		$connection = \OC::$server->getDatabaseConnection();
		$sql = 'SELECT `storage`, `path` FROM `*PREFIX*filecache` WHERE `fileid` = ?';
		$result = $connection->executeQuery($sql, array($id));
		if ($row = $result->fetch()) {
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
