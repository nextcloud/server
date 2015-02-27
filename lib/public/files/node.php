<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2013 Robin Appelman icewind@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * Files/Node interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Files;

interface Node extends FileInfo {
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
	 * @return void
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
	 * @return void
	 */
	public function touch($mtime = null);

	/**
	 * Get the storage backend the file or folder is stored on
	 *
	 * @return \OCP\Files\Storage
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
	 * @throws InvalidPathException
	 * @throws NotFoundException
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
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getMTime();

	/**
	 * Get the size of the file or folder in bytes
	 *
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getSize();

	/**
	 * Get the Etag of the file or folder
	 * The Etag is an string id used to detect changes to a file or folder,
	 * every time the file or folder is changed the Etag will change to
	 *
	 * @return string
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getEtag();


	/**
	 * Get the permissions of the file or folder as a combination of one or more of the following constants:
	 *  - \OCP\Constants::PERMISSION_READ
	 *  - \OCP\Constants::PERMISSION_UPDATE
	 *  - \OCP\Constants::PERMISSION_CREATE
	 *  - \OCP\Constants::PERMISSION_DELETE
	 *  - \OCP\Constants::PERMISSION_SHARE
	 *
	 * @return int
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getPermissions();

	/**
	 * Check if the file or folder is readable
	 *
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isReadable();

	/**
	 * Check if the file or folder is writable
	 *
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isUpdateable();

	/**
	 * Check if the file or folder is deletable
	 *
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function isDeletable();

	/**
	 * Check if the file or folder is shareable
	 *
	 * @return bool
	 * @throws InvalidPathException
	 * @throws NotFoundException
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
