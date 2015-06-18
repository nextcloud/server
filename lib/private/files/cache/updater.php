<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Cache;

/**
 * Update the cache and propagate changes
 *
 * Unlike most other classes an Updater is not related to a specific storage but handles updates for all storages in a users filesystem.
 * This is needed because the propagation of mtime and etags need to cross storage boundaries
 */
class Updater {
	/**
	 * @var bool
	 */
	protected $enabled = true;

	/**
	 * @var \OC\Files\View
	 */
	protected $view;

	/**
	 * @var \OC\Files\Cache\ChangePropagator
	 */
	protected $propagator;

	/**
	 * @param \OC\Files\View $view the view the updater works on, usually the view of the logged in user
	 */
	public function __construct($view) {
		$this->view = $view;
		$this->propagator = new ChangePropagator($view);
	}

	/**
	 * Disable updating the cache trough this updater
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Re-enable the updating of the cache trough this updater
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Get the propagator for etags and mtime for the view the updater works on
	 *
	 * @return ChangePropagator
	 */
	public function getPropagator() {
		return $this->propagator;
	}

	/**
	 * Propagate etag and mtime changes for the parent folders of $path up to the root of the filesystem
	 *
	 * @param string $path the path of the file to propagate the changes for
	 * @param int|null $time the timestamp to set as mtime for the parent folders, if left out the current time is used
	 */
	public function propagate($path, $time = null) {
		if (Scanner::isPartialFile($path)) {
			return;
		}
		$this->propagator->addChange($path);
		$this->propagator->propagateChanges($time);
	}

	/**
	 * Update the cache for $path and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 * @param int $time
	 */
	public function update($path, $time = null) {
		if (!$this->enabled or Scanner::isPartialFile($path)) {
			return;
		}
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = $this->view->resolvePath($path);
		if ($storage) {
			$this->propagator->addChange($path);
			$cache = $storage->getCache($internalPath);
			$scanner = $storage->getScanner($internalPath);
			$data = $scanner->scan($internalPath, Scanner::SCAN_SHALLOW, -1, false);
			$this->correctParentStorageMtime($storage, $internalPath);
			$cache->correctFolderSize($internalPath, $data);
			$this->propagator->propagateChanges($time);
		}
	}

	/**
	 * Remove $path from the cache and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 */
	public function remove($path) {
		if (!$this->enabled or Scanner::isPartialFile($path)) {
			return;
		}
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 * @var string $internalPath
		 */
		list($storage, $internalPath) = $this->view->resolvePath($path);
		if ($storage) {
			$parent = dirname($internalPath);
			if ($parent === '.') {
				$parent = '';
			}
			$this->propagator->addChange($path);
			$cache = $storage->getCache($internalPath);
			$cache->remove($internalPath);
			$cache->correctFolderSize($parent);
			$this->correctParentStorageMtime($storage, $internalPath);
			$this->propagator->propagateChanges();
		}
	}

	/**
	 * Rename a file or folder in the cache and update the size, etag and mtime of the parent folders
	 *
	 * @param string $source
	 * @param string $target
	 */
	public function rename($source, $target) {
		if (!$this->enabled or Scanner::isPartialFile($source) or Scanner::isPartialFile($target)) {
			return;
		}
		/**
		 * @var \OC\Files\Storage\Storage $sourceStorage
		 * @var \OC\Files\Storage\Storage $targetStorage
		 * @var string $sourceInternalPath
		 * @var string $targetInternalPath
		 */
		list($sourceStorage, $sourceInternalPath) = $this->view->resolvePath($source);
		// if it's a moved mountpoint we dont need to do anything
		if ($sourceInternalPath === '') {
			return;
		}
		list($targetStorage, $targetInternalPath) = $this->view->resolvePath($target);

		if ($sourceStorage && $targetStorage) {
			$targetCache = $targetStorage->getCache($sourceInternalPath);
			if ($targetCache->inCache($targetInternalPath)) {
				$targetCache->remove($targetInternalPath);
			}
			if ($sourceStorage === $targetStorage) {
				$targetCache->move($sourceInternalPath, $targetInternalPath);
			} else {
				$targetCache->moveFromCache($sourceStorage->getCache(), $sourceInternalPath, $targetInternalPath);
			}

			if (pathinfo($sourceInternalPath, PATHINFO_EXTENSION) !== pathinfo($targetInternalPath, PATHINFO_EXTENSION)) {
				// handle mime type change
				$mimeType = $targetStorage->getMimeType($targetInternalPath);
				$fileId = $targetCache->getId($targetInternalPath);
				$targetCache->update($fileId, array('mimetype' => $mimeType));
			}

			$targetCache->correctFolderSize($sourceInternalPath);
			$targetCache->correctFolderSize($targetInternalPath);
			$this->correctParentStorageMtime($sourceStorage, $sourceInternalPath);
			$this->correctParentStorageMtime($targetStorage, $targetInternalPath);
			$this->propagator->addChange($source);
			$this->propagator->addChange($target);
			$this->propagator->propagateChanges();
		}
	}

	/**
	 * update the storage_mtime of the direct parent in the cache to the mtime from the storage
	 *
	 * @param \OC\Files\Storage\Storage $storage
	 * @param string $internalPath
	 */
	private function correctParentStorageMtime($storage, $internalPath) {
		$cache = $storage->getCache();
		$parentId = $cache->getParentId($internalPath);
		$parent = dirname($internalPath);
		if ($parentId != -1) {
			$cache->update($parentId, array('storage_mtime' => $storage->filemtime($parent)));
		}
	}

	public function __destruct() {
		// propagate any leftover changes
		$this->propagator->propagateChanges();
	}
}
