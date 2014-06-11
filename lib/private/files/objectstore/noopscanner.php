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

class NoopScanner extends \OC\Files\Cache\Scanner {

	/**
	 * get all the metadata of a file or folder
	 * *
	 *
	 * @param string $path
	 * @return array with metadata of the file
	 */
	public function getData($path) {
		if (!$this->storage->isReadable($path)) {
			//cant read, nothing we can do
			\OCP\Util::writeLog('OC\Files\ObjectStore\NoopScanner', "!!! Path '$path' is not readable !!!", \OCP\Util::DEBUG);
			return null;
		}
		$data = array();
		$data['mimetype'] = $this->storage->getMimeType($path);
		$data['mtime'] = $this->storage->filemtime($path);
		if ($data['mimetype'] == 'httpd/unix-directory') {
			$data['size'] = -1; //unknown
		} else {
			$data['size'] = $this->storage->filesize($path);
		}
		$data['etag'] = $this->storage->getETag($path);
		$data['storage_mtime'] = $data['mtime'];
		$data['permissions'] = $this->storage->getPermissions($path);
		return $data;
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
	public function scanChildren($path, $recursive = \OC\Files\Storage\Storage::SCAN_RECURSIVE, $reuse = -1) {
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
