<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

/**
 * Provide a common interface to all different storage options
 *
 * All paths passed to the storage are relative to the storage and should NOT have a leading slash.
 */
interface Storage extends \OCP\Files\Storage {

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache($path = '');

	/**
	 * get a scanner instance for the storage
	 *
	 * @param string $path
	 * @return \OC\Files\Cache\Scanner
	 */
	public function getScanner($path = '');


	/**
	 * get the user id of the owner of a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getOwner($path);

	/**
	 * get a watcher instance for the cache
	 *
	 * @param string $path
	 * @return \OC\Files\Cache\Watcher
	 */
	public function getWatcher($path = '');

	/**
	 * @return \OC\Files\Cache\Storage
	 */
	public function getStorageCache();

}
