<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Search\ISearchQuery;

/**
 * Folder interface.
 *
 * Represents a container node that can hold files, subfolders,
 * or other nodes in a hierarchical structure.
 *
 * @since 6.0.0
 */
#[Consumable(since: '6.0.0')]
interface Folder extends Node {
	/**
	 * Get the full path of an item in the folder within owncloud's filesystem
	 *
	 * @param string $path relative path of an item in the folder
	 * @return string
	 * @throws NotPermittedException
	 * @since 6.0.0
	 */
	public function getFullPath(string $path): string;

	/**
	 * Get the path of an item in the folder relative to the folder
	 *
	 * @param string $path absolute path of an item in the folder
	 * @throws NotFoundException
	 * @since 6.0.0
	 */
	public function getRelativePath(string $path): ?string;

	/**
	 * Check if a node is a (grand-)child of the folder.
	 *
	 * @since 6.0.0
	 */
	public function isSubNode(Node $node): bool;

	/**
	 * Get the content of this directory.
	 *
	 * @return Node[]
	 * @throws NotFoundException
	 * @since 6.0.0
	 */
	public function getDirectoryListing(): array;

	/**
	 * Get the node at $path.
	 *
	 * @param string $path relative path of the file or folder
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @since 6.0.0
	 */
	public function get(string $path): Node;

	/**
	 * Get or create new folder if the folder does not already exist.
	 *
	 * @param string $path relative path of the file or folder
	 * @throw \OCP\Files\NotPermittedException
	 * @since 33.0.0
	 */
	public function getOrCreateFolder(string $path, int $maxRetries = 5): Folder;

	/**
	 * Check if a file or folder exists in the folder
	 *
	 * @param string $path relative path of the file or folder
	 * @since 6.0.0
	 */
	public function nodeExists(string $path): bool;

	/**
	 * Create a new folder
	 *
	 * @param string $path relative path of the new folder
	 * @throws NotPermittedException
	 * @since 6.0.0
	 */
	public function newFolder(string $path): Folder;

	/**
	 * Create a new file
	 *
	 * @param string $path relative path of the new file
	 * @param string|resource|null $content content for the new file, since 19.0.0
	 * @throws NotPermittedException
	 * @since 6.0.0
	 */
	public function newFile(string $path, $content = null): File;

	/**
	 * Search for files with the name matching $query.
	 *
	 * @return Node[]
	 * @since 6.0.0
	 */
	public function search(string|ISearchQuery $query): array;

	/**
	 * Search for files by mimetype.
	 *
	 * @param string $mimetype can either be a full mimetype (image/png) or a wildcard mimetype (image)
	 * @return Node[]
	 * @since 6.0.0
	 */
	public function searchByMime(string $mimetype): array;

	/**
	 * Search for files by tag.
	 *
	 * @param string|int $tag tag name or tag id
	 * @param string $userId owner of the tags
	 * @return Node[]
	 * @since 8.0.0
	 */
	public function searchByTag(string|int $tag, string $userId): array;

	/**
	 * Search for files by system tag.
	 *
	 * @param string $tag tag name
	 * @param string $userId user id to ensure access on returned nodes
	 * @return Node[]
	 * @since 28.0.0
	 */
	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0): array;

	/**
	 * Get a file or folder inside the folder by its internal id.
	 *
	 * This method could return multiple entries. For example once the file/folder
	 * is shared or mounted (files_external) to the user multiple times.
	 *
	 * Note that the different entries can have different permissions.
	 *
	 * @return Node[]
	 * @since 6.0.0
	 */
	public function getById(int $id): array;

	/**
	 * Get a file or folder inside the folder by its internal id.
	 *
	 * Unlike getById, this method only returns a single node even if the user has
	 * access to the file with the requested id multiple times.
	 *
	 * This method provides no guarantee about which of the nodes in returned and the
	 * returned node might, for example, have less permissions than other nodes for the same file
	 *
	 * Apps that require accurate information about the users access to the file should use getById
	 * instead of pick the correct node out of the result.
	 *
	 * @since 29.0.0
	 */
	public function getFirstNodeById(int $id): ?Node;

	/**
	 * Get the amount of free space inside the folder.
	 *
	 * @since 6.0.0
	 */
	public function getFreeSpace(): int|float|false;

	/**
	 * Check if new files or folders can be created within the folder.
	 *
	 * @since 6.0.0
	 */
	public function isCreatable(): bool;

	/**
	 * Add a suffix to the name in case the file exists.
	 *
	 * @throws NotPermittedException
	 * @since 8.1.0
	 */
	public function getNonExistingName(string $name): string;

	/**
	 * Get recent files and folders.
	 *
	 * @return Node[]
	 * @since 9.1.0
	 */
	public function getRecent(int $limit, int $offset = 0): array;

	/**
	 * Verify if the given fileName is valid and allowed from this folder.
	 *
	 * @param string $fileName
	 * @param bool $readonly Check only if the path is allowed for read-only access
	 * @throws InvalidPathException
	 * @since 32.0.0
	 */
	public function verifyPath(string $fileName, bool $readonly = false): void;
}
