<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

use OC\Files\Cache\Scanner;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\FileInfo;

class ObjectStoreScanner extends Scanner {
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null) {
		return null;
	}

	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		return null;
	}

	protected function scanChildren(string $path, $recursive, int $reuse, int $folderId, bool $lock, int|float $oldSize, &$etagChanged = false) {
		return 0;
	}

	public function backgroundScan() {
		$lastPath = null;
		// find any path marked as unscanned and run the scanner until no more paths are unscanned (or we get stuck)
		// we sort by path DESC to ensure that contents of a folder are handled before the parent folder
		while (($path = $this->getIncomplete()) !== false && $path !== $lastPath) {
			$this->runBackgroundScanJob(function () use ($path) {
				$item = $this->cache->get($path);
				if ($item && $item->getMimeType() !== FileInfo::MIMETYPE_FOLDER) {
					$fh = $this->storage->fopen($path, 'r');
					if ($fh) {
						$stat = fstat($fh);
						if ($stat['size']) {
							$this->cache->update($item->getId(), ['size' => $stat['size']]);
						}
					}
				}
			}, $path);
			// FIXME: this won't proceed with the next item, needs revamping of getIncomplete()
			// to make this possible
			$lastPath = $path;
		}
	}

	/**
	 * Unlike the default Cache::getIncomplete this one sorts by path.
	 *
	 * This is needed since self::backgroundScan doesn't fix child entries when running on a parent folder.
	 * By sorting by path we ensure that we encounter the child entries first.
	 *
	 * @return false|string
	 * @throws \OCP\DB\Exception
	 */
	private function getIncomplete() {
		$query = $this->connection->getQueryBuilder();
		$query->select('path')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter($this->cache->getNumericStorageId(), IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->lt('size', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->orderBy('path', 'DESC')
			->setMaxResults(1);

		$result = $query->executeQuery();
		$path = $result->fetchOne();
		$result->closeCursor();

		if ($path === false) {
			return false;
		}

		// Make sure Oracle does not continue with null for empty strings
		return (string)$path;
	}
}
