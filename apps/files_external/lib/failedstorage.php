<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
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

namespace OCA\Files_External\Lib;

use \OCP\Lock\ILockingProvider;
use \OC\Files\Storage\Common;
use \OCP\Files\StorageNotAvailableException;

/**
 * Storage placeholder to represent a missing precondition, storage unavailable
 */
class FailedStorage extends Common {

	/** @var \Exception */
	protected $e;

	/**
	 * @param array $params ['exception' => \Exception]
	 */
	public function __construct($params) {
		$this->e = $params['exception'];
	}

	public function getId() {
		// we can't return anything sane here
		return 'failedstorage';
	}

	public function mkdir($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function rmdir($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function opendir($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function is_dir($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function is_file($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function stat($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filetype($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filesize($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isCreatable($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isReadable($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isUpdatable($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isDeletable($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isSharable($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getPermissions($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_exists($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filemtime($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_get_contents($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_put_contents($path, $data) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function unlink($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function rename($path1, $path2) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function copy($path1, $path2) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function fopen($path, $mode) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getMimeType($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function hash($type, $path, $raw = false) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function free_space($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function search($query) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function touch($path, $mtime = null) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getLocalFile($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getLocalFolder($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function hasUpdated($path, $time) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getETag($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getDirectDownload($path) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function verifyPath($path, $fileName) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function acquireLock($path, $type, ILockingProvider $provider) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function releaseLock($path, $type, ILockingProvider $provider) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function changeLock($path, $type, ILockingProvider $provider) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

}
