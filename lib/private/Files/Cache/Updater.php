<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use Doctrine\DBAL\Exception\DeadlockException;
use OC\Files\FileInfo;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\IUpdater;
use OCP\Files\Storage\IStorage;
use Psr\Log\LoggerInterface;

/**
 * Update the cache and propagate changes
 *
 */
class Updater implements IUpdater {
	/**
	 * @var bool
	 */
	protected $enabled = true;

	/**
	 * @var \OC\Files\Storage\Storage
	 */
	protected $storage;

	/**
	 * @var \OC\Files\Cache\Propagator
	 */
	protected $propagator;

	/**
	 * @var Scanner
	 */
	protected $scanner;

	/**
	 * @var Cache
	 */
	protected $cache;

	private LoggerInterface $logger;

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 */
	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->propagator = $storage->getPropagator();
		$this->scanner = $storage->getScanner();
		$this->cache = $storage->getCache();
		$this->logger = \OC::$server->get(LoggerInterface::class);
	}

	/**
	 * Disable updating the cache through this updater
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Re-enable the updating of the cache through this updater
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Get the propagator for etags and mtime for the view the updater works on
	 *
	 * @return Propagator
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
		$this->propagator->propagateChange($path, $time);
	}

	/**
	 * Update the cache for $path and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 * @param int $time
	 */
	public function update($path, $time = null, ?int $sizeDifference = null) {
		if (!$this->enabled or Scanner::isPartialFile($path)) {
			return;
		}
		if (is_null($time)) {
			$time = time();
		}

		$data = $this->scanner->scan($path, Scanner::SCAN_SHALLOW, -1, false);

		if (isset($data['oldSize']) && isset($data['size'])) {
			$sizeDifference = $data['size'] - $data['oldSize'];
		}

		// encryption is a pita and touches the cache itself
		if (isset($data['encrypted']) && (bool)$data['encrypted']) {
			$sizeDifference = null;
		}

		// scanner didn't provide size info, fallback to full size calculation
		if ($this->cache instanceof Cache && $sizeDifference === null) {
			$this->cache->correctFolderSize($path, $data);
		}
		$this->correctParentStorageMtime($path);
		$this->propagator->propagateChange($path, $time, $sizeDifference ?? 0);
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

		$parent = dirname($path);
		if ($parent === '.') {
			$parent = '';
		}

		$entry = $this->cache->get($path);

		$this->cache->remove($path);

		$this->correctParentStorageMtime($path);
		if ($entry instanceof ICacheEntry) {
			$this->propagator->propagateChange($path, time(), -$entry->getSize());
		} else {
			$this->propagator->propagateChange($path, time());
			if ($this->cache instanceof Cache) {
				$this->cache->correctFolderSize($parent);
			}
		}
	}

	/**
	 * Rename a file or folder in the cache.
	 *
	 * @param IStorage $sourceStorage
	 * @param string $source
	 * @param string $target
	 */
	public function renameFromStorage(IStorage $sourceStorage, $source, $target) {
		$this->copyOrRenameFromStorage($sourceStorage, $source, $target, function (ICache $sourceCache) use ($sourceStorage, $source, $target) {
			// Remove existing cache entry to no reuse the fileId.
			if ($this->cache->inCache($target)) {
				$this->cache->remove($target);
			}

			if ($sourceStorage === $this->storage) {
				$this->cache->move($source, $target);
			} else {
				$this->cache->moveFromCache($sourceCache, $source, $target);
			}
		});
	}

	/**
	 * Copy a file or folder in the cache.
	 */
	public function copyFromStorage(IStorage $sourceStorage, string $source, string $target): void {
		$this->copyOrRenameFromStorage($sourceStorage, $source, $target, function (ICache $sourceCache, ICacheEntry $sourceInfo) use ($target) {
			$parent = dirname($target);
			if ($parent === '.') {
				$parent = '';
			}
			$parentInCache = $this->cache->inCache($parent);
			if (!$parentInCache) {
				$parentData = $this->scanner->scan($parent, Scanner::SCAN_SHALLOW, -1, false);
				$parentInCache = $parentData !== null;
			}
			if ($parentInCache) {
				$this->cache->copyFromCache($sourceCache, $sourceInfo, $target);
			}
		});
	}

	/**
	 * Utility to copy or rename a file or folder in the cache and update the size, etag and mtime of the parent folders
	 */
	private function copyOrRenameFromStorage(IStorage $sourceStorage, string $source, string $target, callable $operation): void {
		if (!$this->enabled or Scanner::isPartialFile($source) or Scanner::isPartialFile($target)) {
			return;
		}

		$time = time();

		$sourceCache = $sourceStorage->getCache();
		$sourceUpdater = $sourceStorage->getUpdater();
		$sourcePropagator = $sourceStorage->getPropagator();

		$sourceInfo = $sourceCache->get($source);

		$sourceExtension = pathinfo($source, PATHINFO_EXTENSION);
		$targetExtension = pathinfo($target, PATHINFO_EXTENSION);
		$targetIsTrash = preg_match("/^d\d+$/", $targetExtension);

		if ($sourceInfo !== false) {
			if (!$this->storage->instanceOfStorage(ObjectStoreStorage::class)) {
				$operation($sourceCache, $sourceInfo);
			}

			$isDir = $sourceInfo->getMimeType() === FileInfo::MIMETYPE_FOLDER;
		} else {
			$isDir = $this->storage->is_dir($target);
		}

		if ($sourceExtension !== $targetExtension && !$isDir && !$targetIsTrash) {
			// handle mime type change
			$mimeType = $this->storage->getMimeType($target);
			$fileId = $this->cache->getId($target);
			$this->cache->update($fileId, ['mimetype' => $mimeType]);
		}

		if ($sourceCache instanceof Cache) {
			$sourceCache->correctFolderSize($source);
		}
		if ($this->cache instanceof Cache) {
			$this->cache->correctFolderSize($target);
		}
		if ($sourceUpdater instanceof Updater) {
			$sourceUpdater->correctParentStorageMtime($source);
		}
		$this->correctParentStorageMtime($target);
		$this->updateStorageMTimeOnly($target);
		$sourcePropagator->propagateChange($source, $time);
		$this->propagator->propagateChange($target, $time);
	}

	private function updateStorageMTimeOnly($internalPath) {
		$fileId = $this->cache->getId($internalPath);
		if ($fileId !== -1) {
			$mtime = $this->storage->filemtime($internalPath);
			if ($mtime !== false) {
				$this->cache->update(
					$fileId, [
						'mtime' => null, // this magic tells it to not overwrite mtime
						'storage_mtime' => $mtime
					]
				);
			}
		}
	}

	/**
	 * update the storage_mtime of the direct parent in the cache to the mtime from the storage
	 *
	 * @param string $internalPath
	 */
	private function correctParentStorageMtime($internalPath) {
		$parentId = $this->cache->getParentId($internalPath);
		$parent = dirname($internalPath);
		if ($parentId != -1) {
			$mtime = $this->storage->filemtime($parent);
			if ($mtime !== false) {
				try {
					$this->cache->update($parentId, ['storage_mtime' => $mtime]);
				} catch (DeadlockException $e) {
					// ignore the failure.
					// with failures concurrent updates, someone else would have already done it.
					// in the worst case the `storage_mtime` isn't updated, which should at most only trigger an extra rescan
					$this->logger->info('Error while updating parent storage_mtime, should be safe to ignore', ['exception' => $e]);
				}
			}
		}
	}
}
