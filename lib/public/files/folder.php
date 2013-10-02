<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files;

interface Folder extends Node {
	/**
	 * Get the full path of an item in the folder within owncloud's filesystem
	 *
	 * @param string $path relative path of an item in the folder
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFullPath($path);

	/**
	 * Get the path of an item in the folder relative to the folder
	 *
	 * @param string $path absolute path of an item in the folder
	 * @throws \OCP\Files\NotFoundException
	 * @return string
	 */
	public function getRelativePath($path);

	/**
	 * check if a node is a (grand-)child of the folder
	 *
	 * @param \OCP\Files\Node $node
	 * @return bool
	 */
	public function isSubNode($node);

	/**
	 * get the content of this directory
	 *
	 * @throws \OCP\Files\NotFoundException
	 * @return \OCP\Files\Node[]
	 */
	public function getDirectoryListing();

	/**
	 * Get the node at $path
	 *
	 * @param string $path relative path of the file or folder
	 * @return \OCP\Files\Node
	 * @throws \OCP\Files\NotFoundException
	 */
	public function get($path);

	/**
	 * Check if a file or folder exists in the folder
	 *
	 * @param string $path relative path of the file or folder
	 * @return bool
	 */
	public function nodeExists($path);

	/**
	 * Create a new folder
	 *
	 * @param string $path relative path of the new folder
	 * @return \OCP\Files\Folder
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFolder($path);

	/**
	 * Create a new file
	 *
	 * @param string $path relative path of the new file
	 * @return \OCP\Files\File
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFile($path);

	/**
	 * search for files with the name matching $query
	 *
	 * @param string $query
	 * @return \OCP\Files\Node[]
	 */
	public function search($query);

	/**
	 * search for files by mimetype
	 * $mimetype can either be a full mimetype (image/png) or a wildcard mimetype (image)
	 *
	 * @param string $mimetype
	 * @return \OCP\Files\Node[]
	 */
	public function searchByMime($mimetype);

	/**
	 * get a file or folder inside the folder by it's internal id
	 *
	 * @param int $id
	 * @return \OCP\Files\Node[]
	 */
	public function getById($id);

	/**
	 * Get the amount of free space inside the folder
	 *
	 * @return int
	 */
	public function getFreeSpace();

	/**
	 * Check if new files or folders can be created within the folder
	 *
	 * @return bool
	 */
	public function isCreatable();
}
