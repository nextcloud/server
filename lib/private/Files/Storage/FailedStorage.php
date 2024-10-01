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

	public function getId(): string {
		// we can't return anything sane here
		return 'failedstorage';
	}

	public function mkdir($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function rmdir($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function opendir($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function is_dir($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function is_file($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function stat($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filetype($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filesize($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isCreatable($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isReadable($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isUpdatable($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isDeletable($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isSharable($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getPermissions($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_exists($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filemtime($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_get_contents($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_put_contents($path, $data): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function unlink($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function rename($source, $target): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function copy($source, $target): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function fopen($path, $mode): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getMimeType($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function hash($type, $path, $raw = false): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function free_space($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function touch($path, $mtime = null): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getLocalFile($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function hasUpdated($path, $time): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getETag($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getDirectDownload($path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function verifyPath($path, $fileName): void {
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath, $preserveMtime = false): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function acquireLock($path, $type, ILockingProvider $provider): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function releaseLock($path, $type, ILockingProvider $provider): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function changeLock($path, $type, ILockingProvider $provider): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getAvailability(): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function setAvailability($isAvailable): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getCache($path = '', $storage = null): FailedCache {
		return new FailedCache();
	}
}
