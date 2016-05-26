<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Trashbin;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Files\View;
use OCP\IUserManager;

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

	/** @var  IUserManager */
	private $userManager;

	function __construct($parameters, IUserManager $userManager = null) {
		$this->mountPoint = $parameters['mountPoint'];
		$this->userManager = $userManager;
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
	 * Rename path1 to path2 by calling the wrapped storage.
	 *
	 * @param string $path1 first path
	 * @param string $path2 second path
	 */
	public function rename($path1, $path2) {
		$result = $this->storage->rename($path1, $path2);
		if ($result === false) {
			// when rename failed, the post_rename hook isn't triggered,
			// but we still want to reenable the trash logic
			self::$disableTrash = false;
		}
		return $result;
	}

	/**
	 * Deletes the given file by moving it into the trashbin.
	 *
	 * @param string $path path of file or folder to delete
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function unlink($path) {
		return $this->doDelete($path, 'unlink');
	}

	/**
	 * Deletes the given folder by moving it into the trashbin.
	 *
	 * @param string $path path of folder to delete
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function rmdir($path) {
		return $this->doDelete($path, 'rmdir');
	}

	/**
	 * check if it is a file located in data/user/files only files in the
	 * 'files' directory should be moved to the trash
	 *
	 * @param $path
	 * @return bool
	 */
	protected function shouldMoveToTrash($path){
		$normalized = Filesystem::normalizePath($this->mountPoint . '/' . $path);
		$parts = explode('/', $normalized);
		if (count($parts) < 4) {
			return false;
		}

		if ($this->userManager->userExists($parts[1]) && $parts[2] == 'files') {
			return true;
		}

		return false;
	}

	/**
	 * Run the delete operation with the given method
	 *
	 * @param string $path path of file or folder to delete
	 * @param string $method either "unlink" or "rmdir"
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	private function doDelete($path, $method) {
		if (self::$disableTrash
			|| !\OC_App::isEnabled('files_trashbin')
			|| (pathinfo($path, PATHINFO_EXTENSION) === 'part')
			|| $this->shouldMoveToTrash($path) === false
		) {
			return call_user_func_array([$this->storage, $method], [$path]);
		}

		// check permissions before we continue, this is especially important for
		// shared files
		if (!$this->isDeletable($path)) {
			return false;
		}

		$normalized = Filesystem::normalizePath($this->mountPoint . '/' . $path, true, false, true);
		$result = true;
		$view = Filesystem::getView();
		if (!isset($this->deletedFiles[$normalized]) && $view instanceof View) {
			$this->deletedFiles[$normalized] = $normalized;
			if ($filesPath = $view->getRelativePath($normalized)) {
				$filesPath = trim($filesPath, '/');
				$result = \OCA\Files_Trashbin\Trashbin::move2trash($filesPath);
				// in cross-storage cases the file will be copied
				// but not deleted, so we delete it here
				if ($result) {
					call_user_func_array([$this->storage, $method], [$path]);
				}
			} else {
				$result = call_user_func_array([$this->storage, $method], [$path]);
			}
			unset($this->deletedFiles[$normalized]);
		} else if ($this->storage->file_exists($path)) {
			$result = call_user_func_array([$this->storage, $method], [$path]);
		}

		return $result;
	}

	/**
	 * Setup the storate wrapper callback
	 */
	public static function setupStorage() {
		\OC\Files\Filesystem::addStorageWrapper('oc_trashbin', function ($mountPoint, $storage) {
			return new \OCA\Files_Trashbin\Storage(
				array('storage' => $storage, 'mountPoint' => $mountPoint),
				\OC::$server->getUserManager()
			);
		}, 1);
	}

}
