<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheDependencies;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;
use OCP\Server;

class CacheWrapper extends Cache {
	public function __construct(
		protected ?ICache $cache,
		?CacheDependencies $dependencies = null,
	) {
		if (!$dependencies && $this->cache instanceof Cache) {
			$this->mimetypeLoader = $this->cache->mimetypeLoader;
			$this->connection = $this->cache->connection;
			$this->querySearchHelper = $this->cache->querySearchHelper;
		} else {
			if (!$dependencies) {
				$dependencies = Server::get(CacheDependencies::class);
			}
			$this->mimetypeLoader = $dependencies->getMimeTypeLoader();
			$this->connection = $dependencies->getConnection();
			$this->querySearchHelper = $dependencies->getQuerySearchHelper();
		}
	}

	public function getCache(): ICache {
		if (!$this->cache) {
			throw new \Exception('Source cache not initialized');
		}
		return $this->cache;
	}

	protected function hasEncryptionWrapper(): bool {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			return $cache->hasEncryptionWrapper();
		} else {
			return false;
		}
	}

	protected function shouldEncrypt(string $targetPath): bool {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			return $cache->shouldEncrypt($targetPath);
		} else {
			return false;
		}
	}

	/**
	 * Make it easy for wrappers to modify every returned cache entry
	 *
	 * @param ICacheEntry $entry
	 * @return ICacheEntry|false
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
		$result = $this->getCache()->get($file);
		if ($result instanceof ICacheEntry) {
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
		// can't do a simple $this->getCache()->.... call here since getFolderContentsById needs to be called on this
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
		$results = $this->getCache()->getFolderContentsById($fileId);
		return array_map([$this, 'formatCacheEntry'], $results);
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
		return $this->getCache()->insert($file, $data);
	}

	/**
	 * update the metadata in the cache
	 *
	 * @param int $id
	 * @param array $data
	 */
	public function update($id, array $data) {
		$this->getCache()->update($id, $data);
	}

	/**
	 * get the file id for a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getId($file) {
		return $this->getCache()->getId($file);
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file) {
		return $this->getCache()->getParentId($file);
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->getCache()->inCache($file);
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$this->getCache()->remove($file);
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->getCache()->move($source, $target);
	}

	protected function getMoveInfo($path) {
		/** @var Cache $cache */
		$cache = $this->getCache();
		return $cache->getMoveInfo($path);
	}

	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$this->getCache()->moveFromCache($sourceCache, $sourcePath, $targetPath);
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			$cache->clear();
		} else {
			$cache->remove('');
		}
	}

	/**
	 * @param string $file
	 *
	 * @return int Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		return $this->getCache()->getStatus($file);
	}

	public function searchQuery(ISearchQuery $query) {
		return current($this->querySearchHelper->searchInCaches($query, [$this]));
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param array|ICacheEntry|null $data (optional) meta data of the folder
	 */
	public function correctFolderSize(string $path, $data = null, bool $isBackgroundScan = false): void {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			$cache->correctFolderSize($path, $data, $isBackgroundScan);
		}
	}

	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array|null|ICacheEntry $entry (optional) meta data of the folder
	 * @return int|float
	 */
	public function calculateFolderSize($path, $entry = null) {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			return $cache->calculateFolderSize($path, $entry);
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
		/** @var Cache $cache */
		$cache = $this->getCache();
		return $cache->getAll();
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiple incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|false the path of the folder or false when no folder matched
	 */
	public function getIncomplete() {
		return $this->getCache()->getIncomplete();
	}

	/**
	 * get the path of a file on this storage by it's id
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getPathById($id) {
		return $this->getCache()->getPathById($id);
	}

	/**
	 * Returns the numeric storage id
	 *
	 * @return int
	 */
	public function getNumericStorageId() {
		return $this->getCache()->getNumericStorageId();
	}

	/**
	 * get the storage id of the storage for a file and the internal path of the file
	 * unlike getPathById this does not limit the search to files on this storage and
	 * instead does a global search in the cache table
	 *
	 * @param int $id
	 * @return array first element holding the storage id, second the path
	 */
	public static function getById($id) {
		return parent::getById($id);
	}

	public function getQueryFilterForStorage(): ISearchOperator {
		return $this->getCache()->getQueryFilterForStorage();
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		$rawEntry = $this->getCache()->getCacheEntryFromSearchResult($rawEntry);
		if ($rawEntry) {
			$entry = $this->formatCacheEntry(clone $rawEntry);
			return $entry ?: null;
		}

		return null;
	}
}
