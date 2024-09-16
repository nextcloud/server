<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Storage;

use OCP\Lock\ILockingProvider;

/**
 * Provide a common interface to all different storage options
 *
 * All paths passed to the storage are relative to the storage and should NOT have a leading slash.
 */
interface Storage extends \OCP\Files\Storage {
	/**
	 * @inheritDoc
	 * @return Cache
	 */
	public function getCache($path = '', $storage = null);

	/**
	 * @inheritDoc
	 * @return Scanner
	 */
	public function getScanner($path = '', $storage = null);


	/**
	 * get the user id of the owner of a file or folder
	 *
	 * @param string $path
	 * @return string
	 */
	public function getOwner($path);

	/**
	 * @inheritDoc
	 * @return Watcher
	 */
	public function getWatcher($path = '', $storage = null);

	/**
	 * @inheritDoc
	 * @return Propagator
	 */
	public function getPropagator($storage = null);

	/**
	 * @inheritDoc
	 * @return Updater
	 */
	public function getUpdater($storage = null);

	/**
	 * @return \OC\Files\Cache\Storage
	 */
	public function getStorageCache();

	/**
	 * @param string $path
	 * @return array|null
	 */
	public function getMetaData($path);

	/**
	 * @param string $path The path of the file to acquire the lock for
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @param \OCP\Lock\ILockingProvider $provider
	 * @throws \OCP\Lock\LockedException
	 */
	public function acquireLock($path, $type, ILockingProvider $provider);

	/**
	 * @param string $path The path of the file to release the lock for
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @param \OCP\Lock\ILockingProvider $provider
	 * @throws \OCP\Lock\LockedException
	 */
	public function releaseLock($path, $type, ILockingProvider $provider);

	/**
	 * @param string $path The path of the file to change the lock for
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @param \OCP\Lock\ILockingProvider $provider
	 * @throws \OCP\Lock\LockedException
	 */
	public function changeLock($path, $type, ILockingProvider $provider);

	/**
	 * Get the contents of a directory with metadata
	 *
	 * @param string $directory
	 * @return \Traversable an iterator, containing file metadata
	 *
	 * The metadata array will contain the following fields
	 *
	 * - name
	 * - mimetype
	 * - mtime
	 * - size
	 * - etag
	 * - storage_mtime
	 * - permissions
	 */
	public function getDirectoryContent($directory): \Traversable;
}
