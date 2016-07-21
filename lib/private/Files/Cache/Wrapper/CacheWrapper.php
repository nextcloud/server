<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Daniel Jagszent <daniel@jagszent.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Cache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\ICache;

class CacheWrapper extends Cache {
	/**
	 * @var \OCP\Files\Cache\ICache
	 */
	protected $cache;

	/**
	 * @param \OCP\Files\Cache\ICache $cache
	 */
	public function __construct($cache) {
		$this->cache = $cache;
	}

	/**
	 * Make it easy for wrappers to modify every returned cache entry
	 *
	 * @param ICacheEntry $entry
	 * @return ICacheEntry
	 */
	protected function formatCacheEntry($entry) {
		return $entry;
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string|int $file
	 * @return ICacheEntry|false
	 */
	public function get($file) {
		$result = $this->cache->get($file);
		if ($result) {
			$result = $this->formatCacheEntry($result);
		}
		return $result;
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return ICacheEntry[]
	 */
	public function getFolderContents($folder) {
		// can't do a simple $this->cache->.... call here since getFolderContentsById needs to be called on this
		// and not the wrapped cache
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
		$results = $this->cache->getFolderContentsById($fileId);
		return array_map(array($this, 'formatCacheEntry'), $results);
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
		return $this->cache->insert($file, $data);
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {
		$this->cache->update($id, $data);
	}

	/**
	 * get the file id for a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		return $this->cache->getId($file);
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file) {
		return $this->cache->getParentId($file);
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->cache->inCache($file);
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$this->cache->remove($file);
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->cache->move($source, $target);
	}

	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$this->cache->moveFromCache($sourceCache, $sourcePath, $targetPath);
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$this->cache->clear();
	}

	/**
	 * @param string $file
	 *
	 * @return int Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		return $this->cache->getStatus($file);
	}

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern
	 * @return ICacheEntry[] an array of file data
	 */
	public function search($pattern) {
		$results = $this->cache->search($pattern);
		return array_map(array($this, 'formatCacheEntry'), $results);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return ICacheEntry[]
	 */
	public function searchByMime($mimetype) {
		$results = $this->cache->searchByMime($mimetype);
		return array_map(array($this, 'formatCacheEntry'), $results);
	}

	/**
	 * search for files by tag
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return ICacheEntry[] file data
	 */
	public function searchByTag($tag, $userId) {
		$results = $this->cache->searchByTag($tag, $userId);
		return array_map(array($this, 'formatCacheEntry'), $results);
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param string|boolean $path
	 * @param array $data (optional) meta data of the folder
	 */
	public function correctFolderSize($path, $data = null) {
		if ($this->cache instanceof Cache) {
			$this->cache->correctFolderSize($path, $data);
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
		if ($this->cache instanceof Cache) {
			return $this->cache->calculateFolderSize($path, $entry);
		} else {
			return 0;
		}
	}

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll() {
		return $this->cache->getAll();
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
		return $this->cache->getIncomplete();
	}

	/**
	 * get the path of a file on this storage by it's id
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getPathById($id) {
		return $this->cache->getPathById($id);
	}

	/**
	 * Returns the numeric storage id
	 *
	 * @return int
	 */
	public function getNumericStorageId() {
		return $this->cache->getNumericStorageId();
	}

	/**
	 * get the storage id of the storage for a file and the internal path of the file
	 * unlike getPathById this does not limit the search to files on this storage and
	 * instead does a global search in the cache table
	 *
	 * @param int $id
	 * @return array first element holding the storage id, second the path
	 */
	static public function getById($id) {
		return parent::getById($id);
	}
}
