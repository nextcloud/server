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

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;

class Storage extends Wrapper {

	private $mountPoint;
	// remember already deleted files to avoid infinite loops if the trash bin
	// move files across storages
	private $deletedFiles = array();

	/**
	 * Disable trash logic
	 *
	 * @var bool
	 */
	private static $disableTrash = false;

	function __construct($parameters) {
		$this->mountPoint = $parameters['mountPoint'];
		parent::__construct($parameters);
	}

	/**
	 * @internal
	 */
	public static function preRenameHook($params) {
		// in cross-storage cases, a rename is a copy + unlink,
		// that last unlink must not go to trash
		self::$disableTrash = true;
	}

	/**
	 * @internal
	 */
	public static function postRenameHook($params) {
		self::$disableTrash = false;
	}

	/**
	 * Deletes the given file by moving it into the trashbin.
	 *
	 * @param string $path
	 */
	public function unlink($path) {
		if (self::$disableTrash) {
			return $this->storage->unlink($path);
		}
		$normalized = Filesystem::normalizePath($this->mountPoint . '/' . $path);
		$result = true;
		if (!isset($this->deletedFiles[$normalized])) {
			$view = Filesystem::getView();
			$this->deletedFiles[$normalized] = $normalized;
			if ($filesPath = $view->getRelativePath($normalized)) {
				$filesPath = trim($filesPath, '/');
				$result = \OCA\Files_Trashbin\Trashbin::move2trash($filesPath);
				// in cross-storage cases the file will be copied
				// but not deleted, so we delete it here
				if ($result) {
					$this->storage->unlink($path);
				}
			} else {
				$result = $this->storage->unlink($path);
			}
			unset($this->deletedFiles[$normalized]);
		} else if ($this->storage->file_exists($path)) {
			$result = $this->storage->unlink($path);
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
