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
	 * @param array $parameters ['exception' => \Exception]
	 */
	public function __construct(array $parameters) {
		$this->e = $parameters['exception'];
		if (!$this->e) {
			throw new \InvalidArgumentException('Missing "exception" argument in FailedStorage constructor');
		}
	}

	public function getId(): string {
		// we can't return anything sane here
		return 'failedstorage';
	}

	public function mkdir(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function rmdir(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function opendir(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function is_dir(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function is_file(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function stat(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filetype(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filesize(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isCreatable(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isReadable(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isUpdatable(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isDeletable(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function isSharable(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getPermissions(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_exists(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function filemtime(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_get_contents(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function file_put_contents(string $path, mixed $data): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function unlink(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function rename(string $source, string $target): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function copy(string $source, string $target): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function fopen(string $path, string $mode): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getMimeType(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function hash(string $type, string $path, bool $raw = false): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function free_space(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function touch(string $path, ?int $mtime = null): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getLocalFile(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function hasUpdated(string $path, int $time): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getETag(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getDirectDownload(string $path): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function verifyPath(string $path, string $fileName): void {
	}

	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, bool $preserveMtime = false): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function acquireLock(string $path, int $type, ILockingProvider $provider): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function releaseLock(string $path, int $type, ILockingProvider $provider): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function changeLock(string $path, int $type, ILockingProvider $provider): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getAvailability(): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function setAvailability(bool $isAvailable): never {
		throw new StorageNotAvailableException($this->e->getMessage(), $this->e->getCode(), $this->e);
	}

	public function getCache(string $path = '', ?IStorage $storage = null): FailedCache {
		return new FailedCache();
	}
}
