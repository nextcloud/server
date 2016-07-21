<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Files\Mount;

/**
 * Interface IMountManager
 *
 * Manages all mounted storages in the system
 * @since 8.2.0
 */
interface IMountManager {

	/**
	 * Add a new mount
	 *
	 * @param \OCP\Files\Mount\IMountPoint $mount
	 * @since 8.2.0
	 */
	public function addMount(IMountPoint $mount);

	/**
	 * Remove a mount
	 *
	 * @param string $mountPoint
	 * @since 8.2.0
	 */
	public function removeMount($mountPoint);

	/**
	 * Change the location of a mount
	 *
	 * @param string $mountPoint
	 * @param string $target
	 * @since 8.2.0
	 */
	public function moveMount($mountPoint, $target);

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return \OCP\Files\Mount\IMountPoint
	 * @since 8.2.0
	 */
	public function find($path);

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 8.2.0
	 */
	public function findIn($path);

	/**
	 * Remove all registered mounts
	 *
	 * @since 8.2.0
	 */
	public function clear();

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 8.2.0
	 */
	public function findByStorageId($id);

	/**
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 8.2.0
	 */
	public function getAll();

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 8.2.0
	 */
	public function findByNumericId($id);
}
