<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Scanner {
	/**
	 * @var \OC\Files\Storage\Storage $storage
	 */
	private $storage;

	/**
	 * @var string $storageId
	 */
	private $storageId;

	/**
	 * @var \OC\Files\Cache\Cache $cache
	 */
	private $cache;

	const SCAN_RECURSIVE = true;
	const SCAN_SHALLOW = false;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->storageId = $this->storage->getId();
		$this->cache = $storage->getCache();
	}

	/**
	 * get all the metadata of a file or folder
	 * *
	 *
	 * @param string $path
	 * @return array with metadata of the file
	 */
	public function getData($path) {
		$data = array();
		if (!$this->storage->isReadable($path)) return null; //cant read, nothing we can do
		$data['mimetype'] = $this->storage->getMimeType($path);
		$data['mtime'] = $this->storage->filemtime($path);
		if ($data['mimetype'] == 'httpd/unix-directory') {
			$data['size'] = -1; //unknown
		} else {
			$data['size'] = $this->storage->filesize($path);
		}
		$data['etag'] = $this->storage->getETag($path);
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @return array with metadata of the scanned file
	 */
	public function scanFile($file) {
		\OC_Hook::emit('\OC\Files\Cache\Scanner', 'scan_file', array('path' => $file, 'storage' => $this->storageId));
		$data = $this->getData($file);
		if ($data) {
			if ($file) {
				$parent = dirname($file);
				if ($parent === '.') {
					$parent = '';
				}
				if (!$this->cache->inCache($parent)) {
					$this->scanFile($parent);
				}
			}
			$id = $this->cache->put($file, $data);
		}
		return $data;
	}

	/**
	 * scan all the files in a folder and store them in the cache
	 *
	 * @param string $path
	 * @param SCAN_RECURSIVE/SCAN_SHALLOW $recursive
	 * @param bool $onlyChilds
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $onlyChilds = false) {
		\OC_Hook::emit('\OC\Files\Cache\Scanner', 'scan_folder', array('path' => $path, 'storage' => $this->storageId));
		$childQueue = array();
		if (!$onlyChilds) {
			$this->scanFile($path);
		}

		$size = 0;
		if ($dh = $this->storage->opendir($path)) {
			\OC_DB::beginTransaction();
			while ($file = readdir($dh)) {
				if ($file !== '.' and $file !== '..') {
					$child = ($path) ? $path . '/' . $file : $file;
					$data = $this->scanFile($child);
					if ($data) {
						if ($data['mimetype'] === 'httpd/unix-directory') {
							if ($recursive === self::SCAN_RECURSIVE) {
								$childQueue[] = $child;
								$data['size'] = 0;
							} else {
								$data['size'] = -1;
							}
						} else {
						}
						if ($data['size'] === -1) {
							$size = -1;
						} elseif ($size !== -1) {
							$size += $data['size'];
						}
					}
				}
			}
			\OC_DB::commit();
			foreach ($childQueue as $child) {
				$childSize = $this->scan($child, self::SCAN_RECURSIVE, true);
				if ($childSize === -1) {
					$size = -1;
				} else {
					$size += $childSize;
				}
			}
			if ($size !== -1) {
				$this->cache->put($path, array('size' => $size));
			}
		}
		return $size;
	}

	/**
	 * walk over any folders that are not fully scanned yet and scan them
	 */
	public function backgroundScan() {
		while ($path = $this->cache->getIncomplete()) {
			$this->scan($path);
			$this->cache->correctFolderSize($path);
		}
	}
}
