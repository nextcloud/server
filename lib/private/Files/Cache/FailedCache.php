<?php

declare(strict_types=1);

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
	public function __construct(
		private readonly bool $visible = true,
	) {
	}

	#[\Override]
	public function getNumericStorageId(): int {
		return -1;
	}

	#[\Override]
	public function get($file): false|ICacheEntry {
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

	#[\Override]
	public function getFolderContents(string $folder, ?string $mimeTypeFilter = null): array {
		return [];
	}

	#[\Override]
	public function getFolderContentsById(int $fileId, ?string $mimeTypeFilter = null): array {
		return [];
	}

	#[\Override]
	public function put($file, array $data) {
	}

	#[\Override]
	public function insert($file, array $data) {
	}

	#[\Override]
	public function update($id, array $data) {
	}

	#[\Override]
	public function getId($file): int {
		return -1;
	}

	#[\Override]
	public function getParentId($file): int {
		return -1;
	}

	#[\Override]
	public function inCache($file): bool {
		return false;
	}

	#[\Override]
	public function remove($file) {
	}

	#[\Override]
	public function move($source, $target) {
	}

	#[\Override]
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
	}

	public function clear() {
	}

	#[\Override]
	public function getStatus($file) {
		return ICache::NOT_FOUND;
	}

	#[\Override]
	public function search($pattern) {
		return [];
	}

	#[\Override]
	public function searchByMime($mimetype) {
		return [];
	}

	#[\Override]
	public function searchQuery(ISearchQuery $query) {
		return [];
	}

	public function getAll() {
		return [];
	}

	#[\Override]
	public function getIncomplete() {
		return [];
	}

	#[\Override]
	public function getPathById($id) {
		return null;
	}

	#[\Override]
	public function normalize($path) {
		return $path;
	}

	#[\Override]
	public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int {
		throw new \Exception('Invalid cache');
	}

	#[\Override]
	public function getQueryFilterForStorage(): ISearchOperator {
		return new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', -1);
	}

	#[\Override]
	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		return null;
	}
}
