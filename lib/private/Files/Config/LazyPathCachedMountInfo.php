<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\Config;

use OCP\IUser;

class LazyPathCachedMountInfo extends CachedMountInfo {
	// we don't allow \ in paths so it makes a great placeholder
	private const PATH_PLACEHOLDER = '\\PLACEHOLDER\\';

	/** @var callable(CachedMountInfo): string */
	protected $rootInternalPathCallback;

	/**
	 * @param IUser $user
	 * @param int $storageId
	 * @param int $rootId
	 * @param string $mountPoint
	 * @param string $mountProvider
	 * @param int|null $mountId
	 * @param callable(CachedMountInfo): string $rootInternalPathCallback
	 * @throws \Exception
	 */
	public function __construct(
		IUser $user,
		int $storageId,
		int $rootId,
		string $mountPoint,
		string $mountProvider,
		?int $mountId = null,
		callable $rootInternalPathCallback,
	) {
		parent::__construct($user, $storageId, $rootId, $mountPoint, $mountProvider, $mountId, self::PATH_PLACEHOLDER);
		$this->rootInternalPathCallback = $rootInternalPathCallback;
	}

	public function getRootInternalPath(): string {
		if ($this->rootInternalPath === self::PATH_PLACEHOLDER) {
			$this->rootInternalPath = ($this->rootInternalPathCallback)($this);
		}
		return $this->rootInternalPath;
	}
}
