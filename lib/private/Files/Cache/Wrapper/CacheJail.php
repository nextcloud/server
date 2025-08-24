<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheDependencies;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;

/**
 * Jail to a subdirectory of the wrapped cache
 */
class CacheJail extends CacheWrapper {

	protected string $unjailedRoot;

	public function __construct(
		?ICache $cache,
		protected string $root,
		?CacheDependencies $dependencies = null,
	) {
		parent::__construct($cache, $dependencies);

		$this->unjailedRoot = $root;
		$parent = $cache;
		while ($parent instanceof CacheWrapper) {
			if ($parent instanceof CacheJail) {
				$this->unjailedRoot = $parent->getSourcePath($this->unjailedRoot);
			}
			$parent = $parent->getCache();
		}
	}

	/**
	 * @return string
	 */
	protected function getRoot() {
		return $this->root;
	}

	/**
	 * Get the root path with any nested jails resolved
	 *
	 * @return string
	 */
	public function getGetUnjailedRoot() {
		return $this->unjailedRoot;
	}

	/**
	 * @return string
	 */
	protected function getSourcePath(string $path) {
		if ($path === '') {
			return $this->getRoot();
		} else {
			return $this->getRoot() . '/' . ltrim($path, '/');
		}
	}

	/**
	 * @param string $path
	 * @param null|string $root
	 * @return null|string the jailed path or null if the path is outside the jail
	 */
	protected function getJailedPath(string $path, ?string $root = null) {
		if ($root === null) {
			$root = $this->getRoot();
		}
		if ($root === '') {
			return $path;
		}
		$rootLength = strlen($root) + 1;
		if ($path === $root) {
			return '';
		} elseif (substr($path, 0, $rootLength) === $root . '/') {
			return substr($path, $rootLength);
		} else {
			return null;
		}
	}

	protected function formatCacheEntry($entry) {
		if (isset($entry['path'])) {
			$entry['path'] = $this->getJailedPath($entry['path']);
		}
		return $entry;
	}

	/**
	 * get the stored metadata of a file or folder
	 *
	 * @param string|int $file
	 * @return ICacheEntry|false
	 */
	public function get($file) {
		if (is_string($file) or $file == '') {
			$file = $this->getSourcePath($file);
		}
		return parent::get($file);
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
		return $this->getCache()->insert($this->getSourcePath($file), $data);
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
		return $this->getCache()->getId($this->getSourcePath($file));
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 */
	public function getParentId($file) {
		return $this->getCache()->getParentId($this->getSourcePath($file));
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 */
	public function inCache($file) {
		return $this->getCache()->inCache($this->getSourcePath($file));
	}

	/**
	 * remove a file or folder from the cache
	 *
	 * @param string $file
	 */
	public function remove($file) {
		$this->getCache()->remove($this->getSourcePath($file));
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function move($source, $target) {
		$this->getCache()->move($this->getSourcePath($source), $this->getSourcePath($target));
	}

	/**
	 * Get the storage id and path needed for a move
	 *
	 * @param string $path
	 * @return array [$storageId, $internalPath]
	 */
	protected function getMoveInfo($path) {
		return [$this->getNumericStorageId(), $this->getSourcePath($path)];
	}

	/**
	 * remove all entries for files that are stored on the storage from the cache
	 */
	public function clear() {
		$this->getCache()->remove($this->getRoot());
	}

	/**
	 * @param string $file
	 *
	 * @return int Cache::NOT_FOUND, Cache::PARTIAL, Cache::SHALLOW or Cache::COMPLETE
	 */
	public function getStatus($file) {
		return $this->getCache()->getStatus($this->getSourcePath($file));
	}

	/**
	 * update the folder size and the size of all parent folders
	 *
	 * @param array|ICacheEntry|null $data (optional) meta data of the folder
	 */
	public function correctFolderSize(string $path, $data = null, bool $isBackgroundScan = false): void {
		$cache = $this->getCache();
		if ($cache instanceof Cache) {
			$cache->correctFolderSize($this->getSourcePath($path), $data, $isBackgroundScan);
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
			return $cache->calculateFolderSize($this->getSourcePath($path), $entry);
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
		// not supported
		return [];
	}

	/**
	 * find a folder in the cache which has not been fully scanned
	 *
	 * If multiply incomplete folders are in the cache, the one with the highest id will be returned,
	 * use the one with the highest id gives the best result with the background scanner, since that is most
	 * likely the folder where we stopped scanning previously
	 *
	 * @return string|false the path of the folder or false when no folder matched
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
		$path = $this->getCache()->getPathById($id);
		if ($path === null) {
			return null;
		}

		return $this->getJailedPath($path);
	}

	/**
	 * Move a file or folder in the cache
	 *
	 * Note that this should make sure the entries are removed from the source cache
	 *
	 * @param \OCP\Files\Cache\ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 */
	public function moveFromCache(\OCP\Files\Cache\ICache $sourceCache, $sourcePath, $targetPath) {
		if ($sourceCache === $this) {
			return $this->move($sourcePath, $targetPath);
		}
		return $this->getCache()->moveFromCache($sourceCache, $sourcePath, $this->getSourcePath($targetPath));
	}

	public function getQueryFilterForStorage(): ISearchOperator {
		return $this->addJailFilterQuery($this->getCache()->getQueryFilterForStorage());
	}

	protected function addJailFilterQuery(ISearchOperator $filter): ISearchOperator {
		if ($this->getGetUnjailedRoot() !== '' && $this->getGetUnjailedRoot() !== '/') {
			return new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND,
				[
					$filter,
					new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR,
						[
							new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'path', $this->getGetUnjailedRoot()),
							new SearchComparison(ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE, 'path', SearchComparison::escapeLikeParameter($this->getGetUnjailedRoot()) . '/%'),
						],
					)
				]
			);
		} else {
			return $filter;
		}
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		if ($this->getGetUnjailedRoot() === '' || str_starts_with($rawEntry->getPath(), $this->getGetUnjailedRoot())) {
			$rawEntry = $this->getCache()->getCacheEntryFromSearchResult($rawEntry);
			if ($rawEntry) {
				$jailedPath = $this->getJailedPath($rawEntry->getPath());
				if ($jailedPath !== null) {
					return $this->formatCacheEntry(clone $rawEntry) ?: null;
				}
			}
		}

		return null;
	}
}
