<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Cache;

use OCP\EventDispatcher\Event;
use OCP\Files\Storage\IStorage;

/**
 * @since 22.0.0
 */
class AbstractCacheEvent extends Event implements ICacheEvent {
	protected $storage;
	protected $path;
	protected $fileId;
	protected $storageId;

	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @param int $fileId
	 * @since 22.0.0
	 */
	public function __construct(IStorage $storage, string $path, int $fileId, int $storageId) {
		$this->storage = $storage;
		$this->path = $path;
		$this->fileId = $fileId;
		$this->storageId = $storageId;
	}

	/**
	 * @return IStorage
	 * @since 22.0.0
	 */
	public function getStorage(): IStorage {
		return $this->storage;
	}

	/**
	 * @return string
	 * @since 22.0.0
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @param string $path
	 * @since 22.0.0
	 */
	public function setPath(string $path): void {
		$this->path = $path;
	}

	/**
	 * @return int
	 * @since 22.0.0
	 */
	public function getFileId(): int {
		return $this->fileId;
	}

	/**
	 * @return int
	 * @since 22.0.0
	 */
	public function getStorageId(): int {
		return $this->storageId;
	}
}
