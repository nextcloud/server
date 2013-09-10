<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files;

use OC\Files\Cache\Cache;
use OC\Files\Cache\Scanner;
use OC\Files\NotFoundException;
use OC\Files\NotPermittedException;

interface Folder extends Node {
	/**
	 * @param string $path path relative to the folder
	 * @return string
	 * @throws \OC\Files\NotPermittedException
	 */
	public function getFullPath($path);

	/**
	 * @param string $path
	 * @throws \OC\Files\NotFoundException
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
	 * @throws \OC\Files\NotFoundException
	 * @return \OCP\Files\Node[]
	 */
	public function getDirectoryListing();

	/**
	 * Get the node at $path
	 *
	 * @param string $path
	 * @return \OCP\Files\Node
	 * @throws \OC\Files\NotFoundException
	 */
	public function get($path);

	/**
	 * @param string $path
	 * @return bool
	 */
	public function nodeExists($path);

	/**
	 * @param string $path
	 * @return \OCP\Files\Folder
	 * @throws NotPermittedException
	 */
	public function newFolder($path);

	/**
	 * @param string $path
	 * @return \OCP\Files\File
	 * @throws NotPermittedException
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
	 *
	 * @param string $mimetype
	 * @return \OCP\Files\Node[]
	 */
	public function searchByMime($mimetype);

	/**
	 * @param $id
	 * @return \OCP\Files\Node[]
	 */
	public function getById($id);

	public function getFreeSpace();

	/**
	 * @return bool
	 */
	public function isCreatable();
}
