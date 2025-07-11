<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	/**
	 * Retrieves files stored in a specific storage that have a specified ancestor in the file hierarchy.
	 * Allows filtering by mime types, encryption status, and limits the number of results.
	 *
	 * @param int $storageId The ID of the storage to search within.
	 * @param int $folderId The file ID of the ancestor to base the search on.
	 * @param int $fileIdCursor The last processed file ID. Only files with a higher ID will be included. Defaults to 0.
	 * @param int $maxResults The maximum number of results to retrieve. If set to 0, all matching files will be retrieved.
	 * @param list<int> $mimeTypeIds An array of mime types to filter the results. If empty, no mime type filtering will be applied.
	 * @param bool $endToEndEncrypted Whether to include EndToEndEncrypted files
	 * @param bool $serverSideEncrypted Whether to include ServerSideEncrypted files
	 * @return \Generator<ICacheEntry> A generator yielding matching files as cache entries.
	 * @throws \OCP\DB\Exception
	 *
	 * @since 32.0.0
	 */
	public function getByAncestorInStorage(int $storageId, int $folderId, int $fileIdCursor = 0, int $maxResults = 100, array $mimeTypeIds = [], bool $endToEndEncrypted = true, bool $serverSideEncrypted = true): \Generator;

	/**
	 * Retrieves a list of all distinct mounts.
	 * Allows filtering by specific mount providers.
	 * Optionally rewrites home directory root paths to avoid cache and trashbin.
	 *
	 * @param list<string> $mountProviders An array of mount provider class names to filter. If empty, all providers will be included.
	 * @param bool $onlyUserFilesMounts Whether to rewrite the root IDs for home directories to only include user files and to only consider mounts with mount points in the user files.
	 * @return \Generator<array{storage_id: int, root_id: int, overridden_root: int}> A generator yielding mount configurations as an array containing 'storage_id', 'root_id', and 'override_root'.
	 * @throws \OCP\DB\Exception
	 *
	 * @since 32.0.0
	 */
	public function getDistinctMounts(array $mountProviders = [], bool $onlyUserFilesMounts = true): \Generator;
}
