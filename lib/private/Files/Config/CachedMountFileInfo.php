<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\Config;

use OCP\Files\Config\ICachedMountFileInfo;
use OCP\IUser;

class CachedMountFileInfo extends CachedMountInfo implements ICachedMountFileInfo {
	private string $internalPath;

	public function __construct(
		IUser $user,
		int $storageId,
		int $rootId,
		string $mountPoint,
		?int $mountId,
		string $mountProvider,
		string $rootInternalPath,
		string $internalPath
	) {
		parent::__construct($user, $storageId, $rootId, $mountPoint, $mountProvider, $mountId, $rootInternalPath);
		$this->internalPath = $internalPath;
	}

	public function getInternalPath(): string {
		if ($this->getRootInternalPath()) {
			return substr($this->internalPath, strlen($this->getRootInternalPath()) + 1);
		} else {
			return $this->internalPath;
		}
	}

	public function getPath(): string {
		return $this->getMountPoint() . $this->getInternalPath();
	}
}
