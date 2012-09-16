<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class Scanner {
	const SCAN_RECURSIVE = true;
	const SCAN_SHALLOW = false;

	/**
	 * get all the metadata of a file or folder
	 * *
	 * @param \OC\Files\File $file
	 * @return array with metadata of the file
	 */
	public static function getData(\OC\Files\File $file) {
		$data = array();
		$storage = $file->getStorage();
		$path = $file->getInternalPath();
		if (!$storage->isReadable($path)) return null; //cant read, nothing we can do
		clearstatcache();
		$data['mimetype'] = $storage->getMimeType($path);
		$data['mtime'] = $storage->filemtime($path);
		if ($data['mimetype'] == 'httpd/unix-directory') {
			$data['size'] = -1; //unknown
			$data['permissions'] = $storage->getPermissions($path . '/');
		} else {
			$data['size'] = $storage->filesize($path);
			$data['permissions'] = $storage->getPermissions($path);
		}
		return $data;
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param \OC\Files\File $file
	 * @return array with metadata of the scanned file
	 */
	public static function scanFile(\OC\Files\File $file) {
		$data = self::getData($file);
		Cache::put($file, $data);
		return $data;
	}

	/**
	 * scan all the files in a folder and store them in the cache
	 *
	 * @param \OC\Files\File $folder
	 * @param SCAN_RECURSIVE/SCAN_SHALLOW $recursive
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public static function scan(\OC\Files\File $folder, $recursive) {
		$size = 0;
		$storage = $folder->getStorage();
		$path = $folder->getInternalPath();
		if ($dh = $storage->opendir($path)) {
			while ($file = readdir($dh)) {
				if ($file !== '.' and $file !== '..') {
					$child = new \OC\Files\File($storage, $path . '/' . $file);
					$data = self::scanFile($child);
					if ($recursive === self::SCAN_RECURSIVE and $data['mimetype'] === 'httpd/unix-directory') {
						$data['size'] = self::scan($child, self::SCAN_RECURSIVE);
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
