<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

use OC\ForbiddenException;
use OCP\Files\NotFoundException;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;

class Scanner extends \OC\Files\Cache\Scanner {
	/**
	 * @var \OCA\Files_Sharing\External\Storage
	 */
	protected $storage;

	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1) {
		$this->scanAll();
	}

	/**
	 * Scan a single file and store it in the cache.
	 * If an exception happened while accessing the external storage,
	 * the storage will be checked for availability and removed
	 * if it is not available any more.
	 *
	 * @param string $file file to scan
	 * @param int $reuseExisting
	 * @return array an array of metadata of the scanned file
	 */
	public function scanFile($file, $reuseExisting = 0) {
		try {
			return parent::scanFile($file, $reuseExisting);
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

	/**
	 * Checks the remote share for changes.
	 * If changes are available, scan them and update
	 * the cache.
	 */
	public function scanAll() {
		try {
			$data = $this->storage->getShareInfo();
		} catch (\Exception $e) {
			$this->storage->checkStorageAvailability();
			throw new \Exception(
				'Error while scanning remote share: "' .
				$this->storage->getRemote() . '" ' .
				$e->getMessage()
			);
		}
		if ($data['status'] === 'success') {
			$this->addResult($data['data'], '');
		} else {
			throw new \Exception(
				'Error while scanning remote share: "' .
				$this->storage->getRemote() . '"'
			);
		}
	}

	private function addResult($data, $path) {
		$id = $this->cache->put($path, $data);
		if (isset($data['children'])) {
			$children = array();
			foreach ($data['children'] as $child) {
				$children[$child['name']] = true;
				$this->addResult($child, ltrim($path . '/' . $child['name'], '/'));
			}

			$existingCache = $this->cache->getFolderContentsById($id);
			foreach ($existingCache as $existingChild) {
				// if an existing child is not in the new data, remove it
				if (!isset($children[$existingChild['name']])) {
					$this->cache->remove(ltrim($path . '/' . $existingChild['name'], '/'));
				}
			}
		}
	}
}
