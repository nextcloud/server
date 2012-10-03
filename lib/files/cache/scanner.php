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
	 * @var \OC\Files\Cache\Cache $cache
	 */
	private $cache;

	const SCAN_RECURSIVE = true;
	const SCAN_SHALLOW = false;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		$this->storage = $storage;
		$this->cache = new Cache($storage);
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
			$data['permissions'] = $this->storage->getPermissions($path . '/');
		} else {
			$data['size'] = $this->storage->filesize($path);
			$data['permissions'] = $this->storage->getPermissions($path);
		}
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @return array with metadata of the scanned file
	 */
	public function scanFile($file) {
		$data = $this->getData($file);
		if ($file !== '') {
			$parent = dirname($file);
			if ($parent === '.') {
				$parent = '';
			}
			if (!$this->cache->inCache($parent)) {
				$this->scanFile($parent);
			}
		}
		$this->cache->put($file, $data);
		return $data;
	}

	/**
	 * scan all the files in a folder and store them in the cache
	 *
	 * @param string $path
	 * @param SCAN_RECURSIVE/SCAN_SHALLOW $recursive
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public function scan($path, $recursive) {
		$size = 0;
		if ($dh = $this->storage->opendir($path)) {
			while ($file = readdir($dh)) {
				if ($file !== '.' and $file !== '..') {
					$child = $path . '/' . $file;
					$data = $this->scanFile($child);
					if ($recursive === self::SCAN_RECURSIVE and $data['mimetype'] === 'httpd/unix-directory') {
						$data['size'] = $this->scan($child, self::SCAN_RECURSIVE);
					}
					if ($data['size'] >= 0 and $size >= 0) {
						$size += $data['size'];
					}
				}
			}
		}
		return $size;
	}
}
