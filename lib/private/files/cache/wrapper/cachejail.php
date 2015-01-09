<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache\Wrapper;

/**
 * Jail to a subdirectory of the wrapped cache
 */
class CacheJail extends CacheWrapper {
	/**
	 * @var string
	 */
	protected $root;

	/**
	 * @param \OC\Files\Cache\Cache $cache
	 * @param string $root
	 */
	public function __construct($cache, $root) {
		parent::__construct($cache);
		$this->root = $root;
	}

	protected function getSourcePath($path) {
		if ($path === '') {
			return $this->root;
		} else {
			return $this->root . '/' . ltrim($path, '/');
		}
	}

	/**
	 * @param string $path
	 * @return null|string the jailed path or null if the path is outside the jail
	 */
	protected function getJailedPath($path) {
		$rootLength = strlen($this->root) + 1;
		if ($path === $this->root) {
			return '';
		} else if (substr($path, 0, $rootLength) === $this->root . '/') {
			return substr($path, $rootLength);
		} else {
			return null;
		}
	}

	/**
	 * @param array $entry
	 * @return array
	 */
	protected function formatCacheEntry($entry) {
		if (isset($entry['path'])) {
			$entry['path'] = $this->getJailedPath($entry['path']);
		}
		return $entry;
	}

	protected function filterCacheEntry($entry) {
		$rootLength = strlen($this->root) + 1;
		return ($entry['path'] === $this->root) or (substr($entry['path'], 0, $rootLength) === $this->root . '/');
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string /int $file
	 * @return array|false
	 */
	public function get($file) {
		if (is_string($file) or $file == '') {
			$file = $this->getSourcePath($file);
		}
		return parent::get($file);
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
		return $this->cache->put($this->getSourcePath($file), $data);
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {
		$this->cache->update($this->getSourcePath($id), $data);
	}

	/**
	 * get the file id for a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		return $this->cache->getId($this->getSourcePath($file));
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
			return $this->cache->getParentId($this->getSourcePath($file));
		}
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->cache->inCache($this->getSourcePath($file));
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$this->cache->remove($this->getSourcePath($file));
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->cache->move($this->getSourcePath($source), $this->getSourcePath($target));
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$this->cache->remove($this->root);
	}

	/**
	 * @param string $file
	 *
	 * @return int, Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		return $this->cache->getStatus($this->getSourcePath($file));
	}

	private function formatSearchResults($results) {
		$results = array_filter($results, array($this, 'filterCacheEntry'));
		$results = array_values($results);
		return array_map(array($this, 'formatCacheEntry'), $results);
	}

	/**
	 * search for files matching $pattern
	 *
	 * @param string $pattern
	 * @return array an array of file data
	 */
	public function search($pattern) {
		$results = $this->cache->search($pattern);
		return $this->formatSearchResults($results);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return array
	 */
	public function searchByMime($mimetype) {
		$results = $this->cache->searchByMime($mimetype);
		return $this->formatSearchResults($results);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return array
	 */
	public function searchByTag($tag, $userId) {
		$results = $this->cache->searchByTag($tag, $userId);
		return $this->formatSearchResults($results);
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param string|boolean $path
	 * @param array $data (optional) meta data of the folder
	 */
	public function correctFolderSize($path, $data = null) {
		$this->cache->correctFolderSize($this->getSourcePath($path), $data);
	}

	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array $entry (optional) meta data of the folder
	 * @return int
	 */
	public function calculateFolderSize($path, $entry = null) {
		return $this->cache->calculateFolderSize($this->getSourcePath($path), $entry);
	}

	/**
	 * get all file ids on the files on the storage
	 *
	 * @return int[]
	 */
	public function getAll() {
		// not supported
		return array();
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
		// not supported
		return false;
	}

	/**
	 * get the path of a file on this storage by it's id
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getPathById($id) {
		$path = $this->cache->getPathById($id);
		return $this->getJailedPath($path);
	}
}
