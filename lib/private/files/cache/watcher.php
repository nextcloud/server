<?php
/**
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
	 * @return int either \OC\Files\Cache\Watcher::CHECK_NEVER, \OC\Files\Cache\Watcher::CHECK_ONCE, \OC\Files\Cache\Watcher::CHECK_ALWAYS
	 */
	public function getPolicy() {
		return $this->watchPolicy;
	}

	/**
	 * check $path for updates and update if needed
	 *
	 * @param string $path
	 * @param array $cachedEntry
	 * @return boolean true if path was updated
	 */
	public function checkUpdate($path, $cachedEntry = null) {
		if (is_null($cachedEntry)) {
			$cachedEntry = $this->cache->get($path);
		}
		if ($this->needsUpdate($path, $cachedEntry)) {
			$this->update($path, $cachedEntry);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update the cache for changes to $path
	 *
	 * @param string $path
	 * @param array $cachedData
	 */
	public function update($path, $cachedData) {
		if ($this->storage->is_dir($path)) {
			$this->scanner->scan($path, Scanner::SCAN_SHALLOW);
		} else {
			$this->scanner->scanFile($path);
		}
		if ($cachedData['mimetype'] === 'httpd/unix-directory') {
			$this->cleanFolder($path);
		}
		$this->cache->correctFolderSize($path);
	}

	/**
	 * Check if the cache for $path needs to be updated
	 *
	 * @param string $path
	 * @param array $cachedData
	 * @return bool
	 */
	public function needsUpdate($path, $cachedData) {
		if ($this->watchPolicy === self::CHECK_ALWAYS or ($this->watchPolicy === self::CHECK_ONCE and array_search($path, $this->checkedPaths) === false)) {
			$this->checkedPaths[] = $path;
			return $this->storage->hasUpdated($path, $cachedData['storage_mtime']);
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
