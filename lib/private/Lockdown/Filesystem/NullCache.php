<?php

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
	public function getNumericStorageId() {
		return -1;
	}

	public function get($file) {
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

	public function getFolderContents($folder) {
		return [];
	}

	public function getFolderContentsById($fileId) {
		return [];
	}

	public function put($file, array $data) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function insert($file, array $data) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function update($id, array $data) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getId($file) {
		return -1;
	}

	public function getParentId($file) {
		return -1;
	}

	public function inCache($file) {
		return $file === '';
	}

	public function remove($file) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function move($source, $target) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getStatus($file) {
		return ICache::COMPLETE;
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

	public function getIncomplete() {
		return [];
	}

	public function getPathById($id) {
		return '';
	}

	public function normalize($path) {
		return $path;
	}

	public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int {
		throw new ForbiddenException('This request is not allowed to access the filesystem');
	}

	public function getQueryFilterForStorage(): ISearchOperator {
		return new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'storage', -1);
	}

	public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		return null;
	}
}
