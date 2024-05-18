<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
