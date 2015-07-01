<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

interface IMountManager {

	/**
	 * @param \OCP\Files\Mount\IMountPoint $mount
	 */
	public function addMount(IMountPoint $mount);

	/**
	 * @param string $mountPoint
	 */
	public function removeMount($mountPoint);

	/**
	 * @param string $mountPoint
	 * @param string $target
	 */
	public function moveMount($mountPoint, $target);

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return \OCP\Files\Mount\IMountPoint
	 */
	public function find($path);

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function findIn($path);

	/**
	 * Remove all registered mounts
	 */
	public function clear();

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function findByStorageId($id);

	/**
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getAll();

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function findByNumericId($id);
}
