<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files\Storage;

/**
 * Creates storage instances and manages and applies storage wrappers
 */
interface IStorageFactory {
	/**
	 * allow modifier storage behaviour by adding wrappers around storages
	 *
	 * $callback should be a function of type (string $mountPoint, Storage $storage) => Storage
	 *
	 * @param string $wrapperName
	 * @param callable $callback
	 * @return bool true if the wrapper was added, false if there was already a wrapper with this
	 * name registered
	 */
	public function addStorageWrapper($wrapperName, $callback);

	/**
	 * @param string|boolean $mountPoint
	 * @param string $class
	 * @param array $arguments
	 * @return \OCP\Files\Storage
	 */
	public function getInstance($mountPoint, $class, $arguments);
}
