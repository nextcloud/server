<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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
 */

namespace OCA\Files_Trashbin;

use OC\Files\Storage\Wrapper\Wrapper;

class Storage extends Wrapper {

	private $mountPoint;
	// remember already deleted files to avoid infinite loops if the trash bin
	// move files across storages
	private $deletedFiles = array();

	function __construct($parameters) {
		$this->mountPoint = $parameters['mountPoint'];
		parent::__construct($parameters);
	}

	public function unlink($path) {
		$normalized = \OC\Files\Filesystem::normalizePath($this->mountPoint . '/' . $path);
		$result = true;
		if (!isset($this->deletedFiles[$normalized])) {
			$this->deletedFiles[$normalized] = $normalized;
			$parts = explode('/', $normalized);
			if (count($parts) > 3 && $parts[2] === 'files') {
				$filesPath = implode('/', array_slice($parts, 3));
				$result = \OCA\Files_Trashbin\Trashbin::move2trash($filesPath);
			} else {
				$result = $this->storage->unlink($path);
			}
			unset($this->deletedFiles[$normalized]);
		}

		return $result;
	}

	/**
	 * Setup the storate wrapper callback
	 */
	public static function setupStorage() {
		\OC\Files\Filesystem::addStorageWrapper('oc_trashbin', function ($mountPoint, $storage) {
			return new \OCA\Files_Trashbin\Storage(array('storage' => $storage, 'mountPoint' => $mountPoint));
		});
	}

}
