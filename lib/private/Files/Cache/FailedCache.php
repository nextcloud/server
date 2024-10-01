<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OC\Files\Search\SearchComparison;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;

/**
 * Storage placeholder to represent a missing precondition, storage unavailable
 */
class FailedCache implements ICache {
	/** @var bool whether to show the failed storage in the ui */
	private $visible;

	/**
	 * FailedCache constructor.
	 *
	 * @param bool $visible
	 */
	public function __construct($visible = true) {
		$this->visible = $visible;
	}


	public function getNumericStorageId() {
		return -1;
	}

	public function get($file) {
		if ($file === '') {
			return new CacheEntry([
				'fileid' => -1,
				'size' => 0,
				'mimetype' => 'httpd/unix-directory',
				'mimepart' => 'httpd',
				'permissions' => $this->visible ? Constants::PERMISSION_READ : 0,
				'mtime' => time()
			]);
		} else {
			return false;
		}
	}

	public function getFolderContents($folder) {
		return [];
	}

	public function getFolderContentsById($fileId) {
		return [];
	}

	public function put($file, array $data) {
	}

	public function insert($file, array $data) {
	}

	public function update($id, array $data) {
	}

	public function getId($file) {
		return -1;
	}

	public function getParentId($file) {
		return -1;
	}

	public function inCache($file) {
		return false;
	}

	public function remove($file) {
	}

	public function move($source, $target) {
	}

	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
	}

	public function clear() {
	}

	public function getStatus($file) {
		return ICache::NOT_FOUND;
	}

	public function search($pattern) {
		return [];
	}

	public function searchByMime($mimetype) {
		return [];
	}

	public function searchQuery(ISearchQuery $query) {
		return [];
	}

	public function getAll() {
		return [];
	}

	public function getIncomplete() {
		return [];
	}

	public function getPathById($id) {
		return null;
	}

	public function normalize($path) {
		return $path;
	}

	public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int {
		throw new \Exception('Invalid cache');
	}

	public function getQueryFilterForStorage(): ISearchOperator {
		return new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', -1);
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		return null;
	}
}
