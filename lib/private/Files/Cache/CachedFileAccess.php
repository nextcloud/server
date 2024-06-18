<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Cache;

use OC\FilesMetadata\FilesMetadataManager;
use OC\SystemConfig;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeLoader;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Provides cached access to file metadata.
 *
 * Note that this does not include any logic for invalidation and should only be
 * used in places where up to 5 minute outdated metadata is accepted
 */
class CachedFileAccess extends FileAccess {
	const TTL = 5 * 60;

	private ICache $cache;

	public function __construct(
		IDBConnection $connection,
		SystemConfig $systemConfig,
		LoggerInterface $logger,
		FilesMetadataManager $metadataManager,
		IMimeTypeLoader $mimeTypeLoader,
		ICacheFactory $cacheFactory,
	) {
		parent::__construct($connection, $systemConfig, $logger, $metadataManager, $mimeTypeLoader);
		$this->cache = $cacheFactory->createLocal('file_access::');
	}

	/**
	 * @param int[] $fileIds
	 * @return string
	 */
	private function getCacheKey(array $fileIds): string {
		return md5(implode(',', $fileIds));
	}

	/**
	 * @param int[] $fileIds
	 * @return null|ICacheEntry[]
	 */
	private function getCachedByFileIds(array $fileIds): ?array {
		$cached = $this->cache->get($this->getCacheKey($fileIds));
		if (is_array($cached)) {
			return array_map(function ($data) {
				return new CacheEntry($data);
			}, $cached);
		} else {
			return null;
		}
	}

	/**
	 * @param ICacheEntry[] $results
	 * @return void
	 */
	private function cacheEntries(array $results): void {
		$resultFileIds = array_map(function(ICacheEntry $result) {
			return $result->getId();
		}, $results);
		$value = array_map(function(ICacheEntry $entry) {
			return $entry->getData();
		}, $results);
		$this->cache->set($this->getCacheKey($resultFileIds), $value, self::TTL);
	}

	public function getByFileIdInStorage(int $fileId, int $storageId): ?CacheEntry {
		$items = array_values($this->getByFileIdsInStorage([$fileId], $storageId));
		return $items[0] ?? null;
	}

	public function getByFileId(int $fileId): ?CacheEntry {
		$items = array_values($this->getByFileIds([$fileId]));
		return $items[0] ?? null;
	}

	public function getByFileIds(array $fileIds): array {
		$cached = $this->getCachedByFileIds($fileIds);
		if ($cached) {
			return $cached;
		}
		$result = parent::getByFileIds($fileIds);
		$this->cacheEntries($result);
		return $result;
	}

	public function getByFileIdsInStorage(array $fileIds, int $storageId): array {
		$cached = $this->getCachedByFileIds($fileIds);
		if ($cached) {
			return $cached;
		}
		$result = parent::getByFileIdsInStorage($fileIds, $storageId);
		$this->cacheEntries($result);
		return $result;
	}


}
