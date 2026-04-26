<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;

/**
 * Generic fallback implementation for moving cache entries.
 *
 * This fallback copies the source entry to the target path and then removes
 * it from the source cache.
 *
 * It is intended for cache implementations that do not provide a specialized
 * in-place move operation.
 */
trait MoveFromCacheTrait {

	abstract public function put($file, array $data);

	abstract public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int;

	/**
	 * Move a file or folder in the cache.
	 *
	 * This fallback performs the move as a copy-then-delete, so it does not
	 * preserve the original cache entry identity and may result in a new file id
	 * at the destination.
	 *
	 * @param ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @throws \RuntimeException if the source entry cannot be copied
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$sourceEntry = $sourceCache->get($sourcePath);

		if (!$sourceEntry) {
			throw new \RuntimeException('Source path not found in cache: ' . $sourcePath);
		}

		$this->copyFromCache($sourceCache, $sourceEntry, $targetPath);
		// non-atomic; failed removals can leave duplicates
		$sourceCache->remove($sourcePath);
	}
}
