<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

class Loader {
	/**
	 * @var callable[] $storageWrappers
	 */
	private $storageWrappers = array();

	/**
	 * allow modifier storage behaviour by adding wrappers around storages
	 *
	 * $callback should be a function of type (string $mountPoint, Storage $storage) => Storage
	 *
	 * @param callable $callback
	 */
	public function addStorageWrapper($wrapperName, $callback) {
		$this->storageWrappers[$wrapperName] = $callback;
	}

	public function load($mountPoint, $class, $arguments) {
		return $this->wrap($mountPoint, new $class($arguments));
	}

	public function wrap($mountPoint, $storage) {
		foreach ($this->storageWrappers as $wrapper) {
			$storage = $wrapper($mountPoint, $storage);
		}
		return $storage;
	}
}
