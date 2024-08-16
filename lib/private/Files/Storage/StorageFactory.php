<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;

class StorageFactory implements IStorageFactory {
	/**
	 * @var array[] [$name=>['priority'=>$priority, 'wrapper'=>$callable] $storageWrappers
	 */
	private $storageWrappers = [];

	/**
	 * allow modifier storage behaviour by adding wrappers around storages
	 *
	 * $callback should be a function of type (string $mountPoint, Storage $storage) => Storage
	 *
	 * @param string $wrapperName name of the wrapper
	 * @param callable $callback callback
	 * @param int $priority wrappers with the lower priority are applied last (meaning they get called first)
	 * @param \OCP\Files\Mount\IMountPoint[] $existingMounts existing mount points to apply the wrapper to
	 * @return bool true if the wrapper was added, false if there was already a wrapper with this
	 * name registered
	 */
	public function addStorageWrapper($wrapperName, $callback, $priority = 50, $existingMounts = []) {
		if (isset($this->storageWrappers[$wrapperName])) {
			return false;
		}

		// apply to existing mounts before registering it to prevent applying it double in MountPoint::createStorage
		foreach ($existingMounts as $mount) {
			$mount->wrapStorage($callback);
		}

		$this->storageWrappers[$wrapperName] = ['wrapper' => $callback, 'priority' => $priority];
		return true;
	}

	/**
	 * Remove a storage wrapper by name.
	 * Note: internal method only to be used for cleanup
	 *
	 * @param string $wrapperName name of the wrapper
	 * @internal
	 */
	public function removeStorageWrapper($wrapperName) {
		unset($this->storageWrappers[$wrapperName]);
	}

	/**
	 * Create an instance of a storage and apply the registered storage wrappers
	 *
	 * @param \OCP\Files\Mount\IMountPoint $mountPoint
	 * @param string $class
	 * @param array $arguments
	 * @return \OCP\Files\Storage
	 */
	public function getInstance(IMountPoint $mountPoint, $class, $arguments) {
		return $this->wrap($mountPoint, new $class($arguments));
	}

	/**
	 * @param \OCP\Files\Mount\IMountPoint $mountPoint
	 * @param \OCP\Files\Storage $storage
	 * @return \OCP\Files\Storage
	 */
	public function wrap(IMountPoint $mountPoint, $storage) {
		$wrappers = array_values($this->storageWrappers);
		usort($wrappers, function ($a, $b) {
			return $b['priority'] - $a['priority'];
		});
		/** @var callable[] $wrappers */
		$wrappers = array_map(function ($wrapper) {
			return $wrapper['wrapper'];
		}, $wrappers);
		foreach ($wrappers as $wrapper) {
			$storage = $wrapper($mountPoint->getMountPoint(), $storage, $mountPoint);
			if (!($storage instanceof \OCP\Files\Storage)) {
				throw new \Exception('Invalid result from storage wrapper');
			}
		}
		return $storage;
	}
}
