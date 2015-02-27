<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * Update the cache and propagate changes
 */
class Updater {
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

	public function propagate($path, $time = null) {
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
		if (Scanner::isPartialFile($path)) {
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
