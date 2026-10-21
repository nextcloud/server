<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files;

use OCP\Files\Search\ISearchQuery;

/**
 * @since 6.0.0
 */
interface Folder extends Node {
	/**
	 * Get the absolute path for a relative path inside this folder.
	 *
	 * @param string $path Relative path of an item in this folder
	 * @return string
	 * @throws \OCP\Files\NotPermittedException If the path is invalid
	 * @since 6.0.0
	 */
	public function getFullPath($path);

	/**
	 * Get the relative path for an absolute path inside this folder.
	 *
	 * @param string $path Absolute path of an item in this folder
	 * @throws \OCP\Files\NotFoundException
	 * @return string|null
	 * @since 6.0.0
	 */
	public function getRelativePath($path);

	/**
	 * Check whether a node is contained anywhere inside this folder's subtree.
	 *
	 * @param \OCP\Files\Node $node
	 * @return bool
	 * @since 6.0.0
	 */
	public function isSubNode($node);

	/**
	 * Get the contents of this folder.
	 *
	 * @param ?non-empty-string $mimetypeFilter Limit the returned content to this mimetype or mimepart
	 * @throws \OCP\Files\NotFoundException
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function getDirectoryListing(?string $mimetypeFilter = null): array;

	/**
	 * Get the node at the specified relative path in this folder.
	 *
	 * @param string $path Relative path inside this folder
	 * @return \OCP\Files\Node
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function get($path);

	/**
	 * Get or create the folder at the specified relative path.
	 *
	 * @param string $path Relative path inside this folder
	 * @throws \OCP\Files\NotPermittedException If the folder cannot be loaded or created
	 * @since 33.0.0
	 */
	public function getOrCreateFolder(string $path, int $maxRetries = 5): Folder;

	/**
	 * Check if a file or folder exists in this folder.
	 *
	 * @param string $path Relative path inside this folder
	 * @return bool
	 * @since 6.0.0
	 */
	public function nodeExists($path);

	/**
	 * Create a new folder in this folder.
	 *
	 * @param string $path Relative path inside this folder
	 * @return \OCP\Files\Folder
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function newFolder($path);

	/**
	 * Create a new file in this folder.
	 *
	 * @param string $path relative path of the new file
	 * @param string|resource|null $content content for the new file, since 19.0.0
	 * @return \OCP\Files\File
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function newFile($path, $content = null);

	/**
	 * Search for items that match the specified query within this folder.
	 *
	 * If $query is a string, it is treated as a name LIKE search (`%$query%`).
	 * If $query is an ISearchQuery, it is executed as-is.
	 *
	 * @param string|ISearchQuery $query
	 * @return \OCP\Files\Node[]
	 * @throws \InvalidArgumentException When attempting to search by owner outside a user's home folder.
	 * @since 6.0.0
	 */
	public function search($query);

	/**
	 * Search for files by mimetype within this folder.
	 *
	 * $mimetype can either be a full mimetype (image/png) or a wildcard mimetype (image)
	 *
	 * @param string $mimetype
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function searchByMime($mimetype);

	/**
	 * Search for files by tag within this folder.
	 *
	 * @param string|int $tag tag name or tag id
	 * @param string $userId owner of the tags
	 * @return \OCP\Files\Node[]
	 * @since 8.0.0
	 */
	public function searchByTag($tag, $userId);

	/**
	 * Search for files by system tag within this folder.
	 *
	 * @param string|int $tag tag name
	 * @param string $userId user id to ensure access on returned nodes
	 * @return \OCP\Files\Node[]
	 * @since 28.0.0
	 */
	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0);

	/**
	 * Retrieve the node(s) matching the specified internal ID within this folder.
	 *
	 * This method can return multiple nodes. For example, if the file or folder
	 * is shared or mounted (files_external) to the user multiple times.
	 *
	 * Please note that permissions may vary by entry.
	 *
	 * @param int $id
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function getById($id);

	/**
	 * Retrieve an arbitrary node matching the specified internal ID within this folder.
	 *
	 * Unlike getById, this method returns only a single node, even if the user has
	 * access to the file or folder in multiple ways.
	 *
	 * There is no guarantee which node is returned. The returned node might, for
	 * example, have fewer permissions than other nodes for the same file.
	 *
	 * Apps requiring accurate information about the user's access should use getById
	 * instead and select the correct node from the result.
	 *
	 * @param int $id
	 * @return Node|null
	 * @since 29.0.0
	 */
	public function getFirstNodeById(int $id): ?Node;

	/**
	 * Get the amount of free space inside the folder.
	 *
	 * @return int
	 * @since 6.0.0
	 */
	public function getFreeSpace();

	/**
	 * Check if new files or folders can be created within the folder.
	 *
	 * @return bool
	 * @since 6.0.0
	 */
	#[\Override]
	public function isCreatable();

	/**
	 * Return a non-existing filename by appending a numeric suffix if needed.
	 *
	 * @param string $filename
	 * @return string
	 * @throws NotPermittedException
	 * @since 8.1.0
	 */
	public function getNonExistingName($filename);

	/**
	 * Retrieve recently modified files and empty folders.
	 *
	 * Results are ordered by modification time (descending). Non-empty folders
	 * are excluded from the result set.
	 *
	 * When called with an offset of 0 and a limit of at most 100, the search is
	 * restricted to items modified within the last two weeks for faster retrieval.
	 *
	 * @param int $limit Maximum number of results to return
	 * @param int $offset Number of results to skip
	 * @return \OCP\Files\Node[]
	 * @since 9.1.0
	 */
	public function getRecent($limit, $offset = 0);

	/**
	 * Verify that a file name is valid and allowed inside this folder.
	 *
	 * This checks whether the resulting path is permitted for read-only or
	 * read-write access.
	 *
	 * @param string $fileName The file name to validate relative to this folder
	 * @param bool $readonly Set to true to check only for read-only access
	 * @throws InvalidPathException If the file name or resulting path is invalid or not allowed
	 * @since 32.0.0
	 */
	public function verifyPath($fileName, $readonly = false): void;
}
