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
	const CHECK_NEVER = 0; // never check the underlying filesystem for updates
	const CHECK_ONCE = 1; // check the underlying filesystem for updates once every request for each file
	const CHECK_ALWAYS = 2; // always check the underlying filesystem for updates

	protected $watchPolicy = self::CHECK_ONCE;

	protected $checkedPaths = array();

	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	protected $storage;

	/**
	 * @var Cache $cache
	 */
	protected $cache;

	/**
	 * @var Scanner $scanner ;
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
	 * @param int $policy either \OC\Files\Cache\Watcher::CHECK_NEVER, \OC\Files\Cache\Watcher::CHECK_ONCE, \OC\Files\Cache\Watcher::CHECK_ALWAYS
	 */
	public function setPolicy($policy) {
		$this->watchPolicy = $policy;
	}

	/**
	 * check $path for updates
	 *
	 * @param string $path
	 * @param array $cachedEntry
	 * @return boolean true if path was updated
	 */
	public function checkUpdate($path, $cachedEntry = null) {
		if ($this->watchPolicy === self::CHECK_ALWAYS or ($this->watchPolicy === self::CHECK_ONCE and array_search($path, $this->checkedPaths) === false)) {
			if (is_null($cachedEntry)) {
				$cachedEntry = $this->cache->get($path);
			}
			$this->checkedPaths[] = $path;
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
		} else {
			return false;
		}
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
