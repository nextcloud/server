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
use Override;

class CacheWrapper extends Cache {

	public function __construct(
		protected ?ICache $cache,
		?CacheDependencies $dependencies = null,
	) {
		if (!$dependencies && $cache instanceof Cache) {
			$this->mimetypeLoader = $cache->mimetypeLoader;
			$this->connection = $cache->connection;
			$this->querySearchHelper = $cache->querySearchHelper;
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

	#[Override]
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

	#[Override]
	public function get($file) {
		$result = $this->getCache()->get($file);
		if ($result instanceof ICacheEntry) {
			$result = $this->formatCacheEntry($result);
		}
		return $result;
	}

	#[Override]
	public function getFolderContents($folder) {
		// can't do a simple $this->getCache()->.... call here since getFolderContentsById needs to be called on this
		// and not the wrapped cache
		$fileId = $this->getId($folder);
		return $this->getFolderContentsById($fileId);
	}

	#[Override]
	public function getFolderContentsById($fileId) {
		$results = $this->getCache()->getFolderContentsById($fileId);
		return array_filter(array_map(fn (ICacheEntry $entry): ICacheEntry|false => $this->formatCacheEntry($entry), $results));
	}

	#[Override]
	public function put($file, array $data) {
		if (($id = $this->getId($file)) > -1) {
			$this->update($id, $data);
			return $id;
		} else {
			return $this->insert($file, $data);
		}
	}

	#[Override]
	public function insert($file, array $data) {
		return $this->getCache()->insert($file, $data);
	}

	#[Override]
	public function update($id, array $data) {
		$this->getCache()->update($id, $data);
	}

	#[Override]
	public function getId($file) {
		return $this->getCache()->getId($file);
	}

	#[Override]
	public function getParentId($file) {
		return $this->getCache()->getParentId($file);
	}

	#[Override]
	public function inCache($file) {
		return $this->getCache()->inCache($file);
	}

	#[Override]
	public function remove($file) {
		$this->getCache()->remove($file);
	}

	#[Override]
	public function move($source, $target) {
		$this->getCache()->move($source, $target);
	}

	#[Override]
	protected function getMoveInfo($path) {
		/** @var Cache $cache */
		$cache = $this->getCache();
		return $cache->getMoveInfo($path);
	}

	#[Override]
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$this->getCache()->moveFromCache($sourceCache, $sourcePath, $targetPath);
	}

	#[Override]
	public function clear() {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			$cache->clear();
		} else {
			$cache->remove('');
		}
	}

	#[Override]
	public function getStatus($file) {
		return $this->getCache()->getStatus($file);
	}

	#[Override]
	public function searchQuery(ISearchQuery $query) {
		return current($this->querySearchHelper->searchInCaches($query, [$this]));
	}

	#[Override]
	public function correctFolderSize(string $path, $data = null, bool $isBackgroundScan = false): void {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			$cache->correctFolderSize($path, $data, $isBackgroundScan);
		}
	}

	#[Override]
	public function calculateFolderSize($path, $entry = null) {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			return $cache->calculateFolderSize($path, $entry);
		} else {
			return 0;
		}
	}

	#[Override]
	public function getAll() {
		/** @var Cache $cache */
		$cache = $this->getCache();
		return $cache->getAll();
	}

	#[Override]
	public function getIncomplete() {
		return $this->getCache()->getIncomplete();
	}

	#[Override]
	public function getPathById($id) {
		return $this->getCache()->getPathById($id);
	}

	#[Override]
	public function getNumericStorageId() {
		return $this->getCache()->getNumericStorageId();
	}

	#[Override]
	public static function getById($id) {
		return parent::getById($id);
	}

	#[Override]
	public function getQueryFilterForStorage(): ISearchOperator {
		return $this->getCache()->getQueryFilterForStorage();
	}

	#[Override]
	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		$rawEntry = $this->getCache()->getCacheEntryFromSearchResult($rawEntry);
		if ($rawEntry) {
			$entry = $this->formatCacheEntry(clone $rawEntry);
			return $entry ?: null;
		}

		return null;
	}
}
