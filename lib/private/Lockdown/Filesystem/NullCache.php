<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Lockdown\Filesystem;

use OC\Files\Cache\CacheEntry;
use OC\Files\Search\SearchComparison;
use OC\ForbiddenException;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;

class NullCache implements ICache {
	#[\Override]
	public function getNumericStorageId(): int {
		return -1;
	}

	#[\Override]
	public function get($file): false|ICacheEntry {
		if ($file !== '') {
			return false;
		}

		return 	new CacheEntry([
			'fileid' => -1,
			'parent' => -1,
			'name' => '',
			'path' => '',
			'size' => '0',
			'mtime' => time(),
			'storage_mtime' => time(),
			'etag' => '',
			'mimetype' => FileInfo::MIMETYPE_FOLDER,
			'mimepart' => 'httpd',
			'permissions' => Constants::PERMISSION_READ
		]);
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
	public function put($file, array $data): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function insert($file, array $data): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function update($id, array $data): never {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function getId($file) {
		return -1;
	}

	#[\Override]
	public function getParentId($file) {
		return -1;
	}

	#[\Override]
	public function inCache($file) {
		return $file === '';
	}

	#[\Override]
	public function remove($file) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function move($source, $target) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	#[\Override]
	public function getStatus($file) {
		return ICache::COMPLETE;
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

	#[\Override]
	public function getIncomplete() {
		return [];
	}

	#[\Override]
	public function getPathById($id) {
		return '';
	}

	#[\Override]
	public function normalize($path) {
		return $path;
	}

	#[\Override]
	public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
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
