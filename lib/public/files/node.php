<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files;

interface Node {
	/**
	 * Move the file or folder to a new location
	 *
	 * @param string $targetPath the absolute target path
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OCP\Files\Node
	 */
	public function move($targetPath);

	/**
	 * Delete the file or folder
	 */
	public function delete();

	/**
	 * Cope the file or folder to a new location
	 *
	 * @param string $targetPath the absolute target path
	 * @return \OCP\Files\Node
	 */
	public function copy($targetPath);

	/**
	 * Change the modified date of the file or folder
	 * If $mtime is omitted the current time will be used
	 *
	 * @param int $mtime (optional) modified date as unix timestamp
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function touch($mtime = null);

	/**
	 * Get the storage backend the file or folder is stored on
	 *
	 * @return \OC\Files\Storage\Storage
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getStorage();

	/**
	 * Get the full path of the file or folder
	 *
	 * @return string
	 */
	public function getPath();

	/**
	 * Get the path of the file or folder relative to the mountpoint of it's storage
	 *
	 * @return string
	 */
	public function getInternalPath();

	/**
	 * Get the internal file id for the file or folder
	 *
	 * @return int
	 */
	public function getId();

	/**
	 * Get metadata of the file or folder
	 * The returned array contains the following values:
	 *  - mtime
	 *  - size
	 *
	 * @return array
	 */
	public function stat();

	/**
	 * Get the modified date of the file or folder as unix timestamp
	 *
	 * @return int
	 */
	public function getMTime();

	/**
	 * Get the size of the file or folder in bytes
	 *
	 * @return int
	 */
	public function getSize();

	/**
	 * Get the Etag of the file or folder
	 * The Etag is an string id used to detect changes to a file or folder,
	 * every time the file or folder is changed the Etag will change to
	 *
	 * @return string
	 */
	public function getEtag();


	/**
	 * Get the permissions of the file or folder as a combination of one or more of the following constants:
	 *  - \OCP\PERMISSION_READ
	 *  - \OCP\PERMISSION_UPDATE
	 *  - \OCP\PERMISSION_CREATE
	 *  - \OCP\PERMISSION_DELETE
	 *  - \OCP\PERMISSION_SHARE
	 *
	 * @return int
	 */
	public function getPermissions();

	/**
	 * Check if the file or folder is readable
	 *
	 * @return bool
	 */
	public function isReadable();

	/**
	 * Check if the file or folder is writable
	 *
	 * @return bool
	 */
	public function isUpdateable();

	/**
	 * Check if the file or folder is deletable
	 *
	 * @return bool
	 */
	public function isDeletable();

	/**
	 * Check if the file or folder is shareable
	 *
	 * @return bool
	 */
	public function isShareable();

	/**
	 * Get the parent folder of the file or folder
	 *
	 * @return Folder
	 */
	public function getParent();

	/**
	 * Get the filename of the file or folder
	 *
	 * @return string
	 */
	public function getName();
}
