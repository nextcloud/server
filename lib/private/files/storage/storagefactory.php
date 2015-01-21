<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

use OCP\Files\Storage\IStorageFactory;

class StorageFactory implements IStorageFactory {
	/**
	 * @var callable[] $storageWrappers
	 */
	private $storageWrappers = array();

	/**
	 * allow modifier storage behaviour by adding wrappers around storages
	 *
	 * $callback should be a function of type (string $mountPoint, Storage $storage) => Storage
	 *
	 * @param string $wrapperName
	 * @param callable $callback
	 * @return true if the wrapper was added, false if there was already a wrapper with this
	 * name registered
	 */
	public function addStorageWrapper($wrapperName, $callback) {
		if (isset($this->storageWrappers[$wrapperName])) {
			return false;
		}
		$this->storageWrappers[$wrapperName] = $callback;
		return true;
	}

	/**
	 * Create an instance of a storage and apply the registered storage wrappers
	 *
	 * @param string|boolean $mountPoint
	 * @param string $class
	 * @param array $arguments
	 * @return \OCP\Files\Storage
	 */
	public function getInstance($mountPoint, $class, $arguments) {
		return $this->wrap($mountPoint, new $class($arguments));
	}

	/**
	 * @param string|boolean $mountPoint
	 * @param \OCP\Files\Storage $storage
	 * @return \OCP\Files\Storage
	 */
	public function wrap($mountPoint, $storage) {
		foreach ($this->storageWrappers as $wrapper) {
			$storage = $wrapper($mountPoint, $storage);
		}
		return $storage;
	}
}
