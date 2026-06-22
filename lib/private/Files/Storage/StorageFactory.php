<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage;

use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IConstructableStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

class StorageFactory implements IStorageFactory {
	/**
	 * @var array[] [$name=>['priority'=>$priority, 'wrapper'=>$callable] $storageWrappers
	 */
	private array $storageWrappers = [];

	private bool $wrappersSorted = false;

	/**
	 * @var IStorage[]
	 */
	private array $wrappersList = [];

	#[\Override]
	public function addStorageWrapper(string $wrapperName, callable $callback, int $priority = 50, array $existingMounts = []): bool {
		if (isset($this->storageWrappers[$wrapperName])) {
			return false;
		}

		// apply to existing mounts before registering it to prevent applying it double in MountPoint::createStorage
		foreach ($existingMounts as $mount) {
			$mount->wrapStorage($callback);
		}

		$this->storageWrappers[$wrapperName] = ['wrapper' => $callback, 'priority' => $priority];
		$this->wrappersSorted = false;
		return true;
	}

	/**
	 * Remove a storage wrapper by name.
	 * Note: internal method only to be used for cleanup
	 *
	 * @internal
	 */
	public function removeStorageWrapper(string $wrapperName): void {
		unset($this->storageWrappers[$wrapperName]);
		$this->wrappersSorted = false;
	}

	/**
	 * Create an instance of a storage and apply the registered storage wrappers
	 */
	#[\Override]
	public function getInstance(IMountPoint $mountPoint, string $class, array $arguments): IStorage {
		if (!is_a($class, IConstructableStorage::class, true)) {
			Server::get(LoggerInterface::class)->warning('Building a storage not implementing IConstructableStorage is deprecated since 31.0.0', ['class' => $class]);
		}
		return $this->wrap($mountPoint, new $class($arguments));
	}

	public function wrap(IMountPoint $mountPoint, IStorage $storage): IStorage {
		if (!$this->wrappersSorted) {
			uasort($this->storageWrappers, function ($a, $b) {
				return $b['priority'] - $a['priority'];
			});

			$this->wrappersSorted = true;
			$this->wrappersList = array_column($this->storageWrappers, 'wrapper');
		}

		foreach ($this->wrappersList as $wrapper) {
			$storage = $wrapper($mountPoint->getMountPoint(), $storage, $mountPoint);
			if (!($storage instanceof IStorage)) {
				throw new \Exception('Invalid result from storage wrapper');
			}
		}
		return $storage;
	}
}
