<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use OC\Files\Cache\FailedCache;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\Lock\ILockingProvider;

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
		if (!$this->e) {
			throw new \InvalidArgumentException('Missing "exception" argument in FailedStorage constructor');
		}
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

	public function filesize($path): false|int|float {
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

	public function rename($source, $target) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function copy($source, $target) {
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
		return true;
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime = false) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
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

	public function getAvailability() {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function setAvailability($isAvailable) {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getCache($path = '', $storage = null) {
		return new FailedCache();
	}
}
