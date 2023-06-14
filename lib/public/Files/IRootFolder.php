<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCP\Files;

use OC\Hooks\Emitter;
use OC\User\NoUserException;

/**
 * Interface IRootFolder
 *
 * @since 8.0.0
 */
interface IRootFolder extends Folder, Emitter {
	/**
	 * Returns a view to user's files folder
	 *
	 * @param string $userId user ID
	 * @return Folder
	 * @throws NoUserException
	 * @throws NotPermittedException
	 *
	 * @since 8.2.0
	 */
	public function getUserFolder($userId);

	/**
	 * Get a file or folder by fileid, inside a parent path
	 *
	 * @param int $id
	 * @param string $path
	 * @return Node[]
	 *
	 * @since 24.0.0
	 */
	public function getByIdInPath(int $id, string $path);

	/**
	 * @param \OC\Files\Storage\Storage $storage
	 * @param string $mountPoint
	 * @param array $arguments
	 */
	public function mount($storage, $mountPoint, $arguments = []);

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\MountPoint
	 */
	public function getMount($mountPoint);

	/**
	 * @param string $mountPoint
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	public function getMountsIn($mountPoint);

	/**
	 * @param string $storageId
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	public function getMountByStorageId($storageId);

	/**
	 * @param int $numericId
	 * @return MountPoint[]
	 */
	public function getMountByNumericStorageId($numericId);

	/**
	 * @param \OC\Files\Mount\MountPoint $mount
	 */
	public function unMount($mount);
}
