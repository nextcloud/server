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
	 * @param \OC\Files\View $view
	 */
	public function __construct($view) {
		$this->view = $view;
		$this->propagator = new ChangePropagator($view);
	}

	public function disable() {
		$this->enabled = false;
	}

	public function enable() {
		$this->enabled = true;
	}

	public function propagate($path, $time = null) {
		if (Scanner::isPartialFile($path)) {
			return;
		}
		$this->propagator->addChange($path);
		$this->propagator->propagateChanges($time);
	}

	/**
	 * Update the cache for $path
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
			$data = $scanner->scan($internalPath, Scanner::SCAN_SHALLOW);
			$this->correctParentStorageMtime($storage, $internalPath);
			$cache->correctFolderSize($internalPath, $data);
			$this->propagator->propagateChanges($time);
		}
	}

	/**
	 * Remove $path from the cache
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
			if ($sourceStorage === $targetStorage) {
				$cache = $sourceStorage->getCache($sourceInternalPath);
				if ($cache->inCache($targetInternalPath)) {
					$cache->remove($targetInternalPath);
				}
				$cache->move($sourceInternalPath, $targetInternalPath);

				if (pathinfo($sourceInternalPath, PATHINFO_EXTENSION) !== pathinfo($targetInternalPath, PATHINFO_EXTENSION)) {
					// handle mime type change
					$mimeType = $sourceStorage->getMimeType($targetInternalPath);
					$fileId = $cache->getId($targetInternalPath);
					$cache->update($fileId, array('mimetype' => $mimeType));
				}

				$cache->correctFolderSize($sourceInternalPath);
				$cache->correctFolderSize($targetInternalPath);
				$this->correctParentStorageMtime($sourceStorage, $sourceInternalPath);
				$this->correctParentStorageMtime($targetStorage, $targetInternalPath);
				$this->propagator->addChange($source);
				$this->propagator->addChange($target);
			} else {
				$this->remove($source);
				$this->update($target);
			}
			$this->propagator->propagateChanges();
		}
	}

	/**
	 * update the storage_mtime of the parent
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
