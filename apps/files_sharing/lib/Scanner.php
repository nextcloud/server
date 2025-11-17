<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing;

use OC\Files\ObjectStore\ObjectStoreScanner;
use OC\Files\Storage\Storage;
use OCP\Files\Cache\IScanner;

/**
 * Scanner for SharedStorage
 * @property SharedStorage $storage
 */
class Scanner extends \OC\Files\Cache\Scanner {
	private ?IScanner $sourceScanner = null;

	/**
	 * Returns metadata from the shared storage, but
	 * with permissions from the source storage.
	 *
	 * @param string $path path of the file for which to retrieve metadata
	 *
	 * @return array|null an array of metadata of the file
	 */
	public function getData($path) {
		$data = parent::getData($path);
		if ($data === null) {
			return null;
		}
		$internalPath = $this->storage->getUnjailedPath($path);
		$data['permissions'] = $this->storage->getSourceStorage()->getPermissions($internalPath);
		return $data;
	}

	private function getSourceScanner(): ?IScanner {
		if ($this->sourceScanner) {
			return $this->sourceScanner;
		}
		if ($this->storage->instanceOfStorage('\OCA\Files_Sharing\SharedStorage')) {
			/** @var Storage $storage */
			[$storage] = $this->storage->resolvePath('');
			$this->sourceScanner = $storage->getScanner();
			return $this->sourceScanner;
		} else {
			return null;
		}
	}

	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null) {
		$sourceScanner = $this->getSourceScanner();
		if ($sourceScanner instanceof ObjectStoreScanner) {
			// ObjectStoreScanner doesn't scan
			return null;
		} else {
			return parent::scanFile($file, $reuseExisting, $parentId, $cacheData, $lock);
		}
	}
}
