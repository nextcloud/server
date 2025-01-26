<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OC\ForbiddenException;
use OCP\Files\NotFoundException;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;

class Scanner extends \OC\Files\Cache\Scanner {
	/** @var Storage */
	protected $storage;

	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		// Disable locking for federated shares
		parent::scan($path, $recursive, $reuse, false);
	}

	/**
	 * Scan a single file and store it in the cache.
	 * If an exception happened while accessing the external storage,
	 * the storage will be checked for availability and removed
	 * if it is not available any more.
	 *
	 * @param string $file file to scan
	 * @param int $reuseExisting
	 * @param int $parentId
	 * @param \OC\Files\Cache\CacheEntry|array|null|false $cacheData existing data in the cache for the file to be scanned
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @param array|null $data the metadata for the file, as returned by the storage
	 * @return array|null an array of metadata of the scanned file
	 */
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null) {
		try {
			return parent::scanFile($file, $reuseExisting, $parentId, $cacheData, $lock, $data);
		} catch (ForbiddenException $e) {
			$this->storage->checkStorageAvailability();
		} catch (NotFoundException $e) {
			// if the storage isn't found, the call to
			// checkStorageAvailable() will verify it and remove it
			// if appropriate
			$this->storage->checkStorageAvailability();
		} catch (StorageInvalidException $e) {
			$this->storage->checkStorageAvailability();
		} catch (StorageNotAvailableException $e) {
			$this->storage->checkStorageAvailability();
		}
	}
}
