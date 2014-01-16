<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

/**
 * check the storage backends for updates and change the cache accordingly
 */
class Watcher {
	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	protected $storage;

	/**
	 * @var Cache $cache
	 */
	protected $cache;

	/**
	 * @var Scanner $scanner;
	 */
	protected $scanner;

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 */
	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->cache = $storage->getCache();
		$this->scanner = $storage->getScanner();
	}

	/**
	 * check $path for updates
	 *
	 * @param string $path
	 * @return boolean true if path was updated, false otherwise
	 */
	public function checkUpdate($path) {
		$cachedEntry = $this->cache->get($path);
		if ($this->storage->hasUpdated($path, $cachedEntry['storage_mtime'])) {
			if ($this->storage->is_dir($path)) {
				$this->scanner->scan($path, Scanner::SCAN_SHALLOW);
			} else {
				$this->scanner->scanFile($path);
			}
			if ($cachedEntry['mimetype'] === 'httpd/unix-directory') {
				$this->cleanFolder($path);
			}
			$this->cache->correctFolderSize($path);
			return true;
		}
		return false;
	}

	/**
	 * remove deleted files in $path from the cache
	 *
	 * @param string $path
	 */
	public function cleanFolder($path) {
		$cachedContent = $this->cache->getFolderContents($path);
		foreach ($cachedContent as $entry) {
			if (!$this->storage->file_exists($entry['path'])) {
				$this->cache->remove($entry['path']);
			}
		}
	}
}
