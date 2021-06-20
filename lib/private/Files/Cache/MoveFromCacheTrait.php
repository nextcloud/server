<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
