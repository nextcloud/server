<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCP\Files\Cache;

/**
 * Low level access to the file cache.
 *
 * This is intended for use cases where data from the filecache needs to be loaded, but the full filesystem apis are
 * insufficient or too inefficient for the use-case.
 *
 * @since 29.0.0
 */
interface IFileAccess {
	/**
	 * Get a filecache data by file id from a specific storage.
	 *
	 * This is preferred over `getByFileId` when the storage id is known as it
	 * can be more efficient in some setups.
	 *
	 * @param int $fileId
	 * @param int $storageId
	 * @return ICacheEntry|null
	 *
	 * @since 29.0.0
	 */
	public function getByFileIdInStorage(int $fileId, int $storageId): ?ICacheEntry;

	/**
	 * Get a filecache data by path and storage id.
	 *
	 * @param string $path
	 * @param int $storageId
	 * @return ICacheEntry|null
	 *
	 * @since 29.0.0
	 */
	public function getByPathInStorage(string $path, int $storageId): ?ICacheEntry;

	/**
	 * Get a filecache data by file id.
	 *
	 * If the storage id is known then `getByFileIdInStorage` is preferred as it can be more efficient in some setups.
	 *
	 * @param int $fileId
	 * @return ICacheEntry|null
	 *
	 * @since 29.0.0
	 */
	public function getByFileId(int $fileId): ?ICacheEntry;

	/**
	 * Get filecache data by file ids.
	 *
	 * If the storage id is known then `getByFileIdsInStorage` is preferred as it can be more efficient in some setups.
	 *
	 * @param int[] $fileIds
	 * @return array<int, ICacheEntry>
	 *
	 * @since 29.0.0
	 */
	public function getByFileIds(array $fileIds): array;

	/**
	 * Get filecache data by file ids from a specific storage.
	 *
	 * This is prefered over `getByFileIds` when the storage id is known as it
	 * can be more efficient in some setups.
	 *
	 * @param int[] $fileIds
	 * @param int $storageId
	 * @return array<int, ICacheEntry>
	 *
	 * @since 29.0.0
	 */
	public function getByFileIdsInStorage(array $fileIds, int $storageId): array;
}
