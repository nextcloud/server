<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

use OCP\Files\Search\ISearchQuery;

/**
 * @since 6.0.0
 */
interface Folder extends Node {
	/**
	 * Get the full path of an item in the folder within owncloud's filesystem
	 *
	 * @param string $path relative path of an item in the folder
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function getFullPath($path);

	/**
	 * Get the path of an item in the folder relative to the folder
	 *
	 * @param string $path absolute path of an item in the folder
	 * @throws \OCP\Files\NotFoundException
	 * @return string|null
	 * @since 6.0.0
	 */
	public function getRelativePath($path);

	/**
	 * check if a node is a (grand-)child of the folder
	 *
	 * @param \OCP\Files\Node $node
	 * @return bool
	 * @since 6.0.0
	 */
	public function isSubNode($node);

	/**
	 * get the content of this directory
	 *
	 * @throws \OCP\Files\NotFoundException
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function getDirectoryListing();

	/**
	 * Get the node at $path
	 *
	 * @param string $path relative path of the file or folder
	 * @return \OCP\Files\Node
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function get($path);

	/**
	 * Check if a file or folder exists in the folder
	 *
	 * @param string $path relative path of the file or folder
	 * @return bool
	 * @since 6.0.0
	 */
	public function nodeExists($path);

	/**
	 * Create a new folder
	 *
	 * @param string $path relative path of the new folder
	 * @return \OCP\Files\Folder
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function newFolder($path);

	/**
	 * Create a new file
	 *
	 * @param string $path relative path of the new file
	 * @param string|resource|null $content content for the new file, since 19.0.0
	 * @return \OCP\Files\File
	 * @throws \OCP\Files\NotPermittedException
	 * @since 6.0.0
	 */
	public function newFile($path, $content = null);

	/**
	 * search for files with the name matching $query
	 *
	 * @param string|ISearchQuery $query
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function search($query);

	/**
	 * search for files by mimetype
	 * $mimetype can either be a full mimetype (image/png) or a wildcard mimetype (image)
	 *
	 * @param string $mimetype
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function searchByMime($mimetype);

	/**
	 * search for files by tag
	 *
	 * @param string|int $tag tag name or tag id
	 * @param string $userId owner of the tags
	 * @return \OCP\Files\Node[]
	 * @since 8.0.0
	 */
	public function searchByTag($tag, $userId);

	/**
	 * search for files by system tag
	 *
	 * @param string|int $tag tag name
	 * @param string $userId user id to ensure access on returned nodes
	 * @return \OCP\Files\Node[]
	 * @since 28.0.0
	 */
	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0);

	/**
	 * get a file or folder inside the folder by its internal id
	 *
	 * This method could return multiple entries. For example once the file/folder
	 * is shared or mounted (files_external) to the user multiple times.
	 *
	 * Note that the different entries can have different permissions.
	 *
	 * @param int $id
	 * @return \OCP\Files\Node[]
	 * @since 6.0.0
	 */
	public function getById($id);

	/**
	 * get a file or folder inside the folder by its internal id
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
	 * @param int $id
	 * @return Node|null
	 * @since 29.0.0
	 */
	public function getFirstNodeById(int $id): ?Node;

	/**
	 * Get the amount of free space inside the folder
	 *
	 * @return int
	 * @since 6.0.0
	 */
	public function getFreeSpace();

	/**
	 * Check if new files or folders can be created within the folder
	 *
	 * @return bool
	 * @since 6.0.0
	 */
	public function isCreatable();

	/**
	 * Add a suffix to the name in case the file exists
	 *
	 * @param string $name
	 * @return string
	 * @throws NotPermittedException
	 * @since 8.1.0
	 */
	public function getNonExistingName($name);

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return \OCP\Files\Node[]
	 * @since 9.1.0
	 */
	public function getRecent($limit, $offset = 0);

	/**
	 * Verify if the given path is valid and allowed from this folder.
	 *
	 * @param string $path the path from this folder
	 * @param string $fileName
	 * @param bool $readonly Check only if the path is allowed for read-only access
	 * @throws InvalidPathException
	 * @since 32.0.0
	 */
	public function verifyPath($fileName, $readonly = false): void;
}
