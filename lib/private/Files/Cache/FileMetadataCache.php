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

	private CappedMemoryCache $fileCache;

	public function __construct(
		private readonly FileAccess $fileAccess,
	) {
		$this->fileCache = new CappedMemoryCache();
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
		$cacheArray = $this->fileCache->getData();
		// question: why doesn't CappedMemoryCache::hasKey use // array_key_exists?
		$arrayKeyExists = fn (int $id): bool => !array_key_exists(
			$id,
			$cacheArray
		);
		$missingIds = array_filter($fileIds, $arrayKeyExists(...));

		if (!empty($missingIds)) {
			$missingMetadata = $this->fileAccess->getByFileIds($missingIds);
			foreach ($missingMetadata as $id => $metadata) {
				$this->fileCache->set((string)$id, $metadata);
			}
		}

		return array_reduce(
			$fileIds,
			function (array $carry, int $id) {
				$carry[$id] = $this->fileCache->get((string)$id);
				return $carry;
			}, []);
	}
}
