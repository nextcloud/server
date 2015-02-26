<?php
/**
 * @author Jörn Friedrich Dreyer
 * @copyright (c) 2014 Jörn Friedrich Dreyer <jfd@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;
use \OC\Files\Cache\Scanner;
use \OC\Files\Storage\Storage;

class NoopScanner extends Scanner {

	public function __construct(Storage $storage) {
		//we don't need the storage, so do nothing here
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @param int $reuseExisting
	 * @param bool $parentExistsInCache
	 * @return array with metadata of the scanned file
	 */
	public function scanFile($file, $reuseExisting = 0, $parentExistsInCache = false) {
		return array();
	}

	/**
	 * scan a folder and all it's children
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @return array with the meta data of the scanned file or folder
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1) {
		return array();
	}

	/**
	 * scan all the files and folders in a folder
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public function scanChildren($path, $recursive = Storage::SCAN_RECURSIVE, $reuse = -1) {
		$size = 0;
		return $size;
	}

	/**
	 * walk over any folders that are not fully scanned yet and scan them
	 */
	public function backgroundScan() {
		//noop
	}
}
