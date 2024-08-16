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
 * Fallback implementation for moveFromCache
 */
trait MoveFromCacheTrait {
	/**
	 * store meta data for a file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 */
	abstract public function put($file, array $data);

	abstract public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int;

	/**
	 * Move a file or folder in the cache
	 *
	 * @param \OCP\Files\Cache\ICache $sourceCache
	 * @param string $sourcePath
	 * @param string $targetPath
	 */
	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$sourceEntry = $sourceCache->get($sourcePath);

		$this->copyFromCache($sourceCache, $sourceEntry, $targetPath);

		$sourceCache->remove($sourcePath);
	}
}
