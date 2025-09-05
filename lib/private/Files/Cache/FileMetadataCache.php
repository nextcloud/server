<?php

declare(strict_types=1);

namespace OC\Files\Cache;

use OCP\Cache\CappedMemoryCache;
use OCP\Files\Cache\ICacheEntry;

/**
 * This class uses FileAccess to fetch data to populate ICacheEntry objects
 * and caches them in memory for subsequent access.
 */
class FileMetadataCache {

	private const CACHE_SIZE = 50000;

	private const MAX_QUERIED_ITEMS = 1000;

	private CappedMemoryCache $fileCache;

	public function __construct(
		private readonly FileAccess $fileAccess,
	) {
		$this->fileCache = new CappedMemoryCache(self::CACHE_SIZE);
	}

	/**
	 * Returns file metadata by retrieving it from an in-memory cache or the
	 * database.
	 *
	 * @param int[] $fileIds
	 * @return array<int, ICacheEntry|null>
	 * @see FileAccess::getByFileIds()
	 */
	public function getByFileIds(array $fileIds): array {
		// avoid thrashing the cache if we are asked to query too many ids
		$skipCaching = \count($fileIds) >= self::CACHE_SIZE;

		$cacheArray = $this->fileCache->getData();

		$result = [];
		$missingIds = [];
		foreach ($fileIds as $fileId) {
			$stringFileId = (string)$fileId;
			if (\array_key_exists($stringFileId, $cacheArray)) {
				$result[$fileId] = $this->fileCache->get($stringFileId);
			} else {
				$missingIds[] = $fileId;
			}
		}

		foreach (array_chunk($missingIds, self::MAX_QUERIED_ITEMS) as $chunk) {
			foreach ($this->fileAccess->getByFileIds($chunk) as $id => $fileMetadata) {
				$result[$id] = $fileMetadata;
				if (!$skipCaching || \count($this->fileCache->getData()) < self::CACHE_SIZE) {
					$this->fileCache->set((string)$id, $fileMetadata);
				}
			}
		}

		return $result;
	}
}
