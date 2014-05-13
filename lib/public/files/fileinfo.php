<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCP\Files;

interface FileInfo {
	const TYPE_FILE = 'file';
	const TYPE_FOLDER = 'dir';

	/**
	 * Get the Etag of the file or folder
	 *
	 * @return string
	 */
	public function getEtag();

	/**
	 * Get the size in bytes for the file or folder
	 *
	 * @return int
	 */
	public function getSize();

	/**
	 * Get the last modified date as timestamp for the file or folder
	 *
	 * @return int
	 */
	public function getMtime();

	/**
	 * Get the name of the file or folder
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the path relative to the storage
	 *
	 * @return string
	 */
	public function getInternalPath();

	/**
	 * Get the absolute path
	 *
	 * @return string
	 */
	public function getPath();

	/**
	 * Get the full mimetype of the file or folder i.e. 'image/png'
	 *
	 * @return string
	 */
	public function getMimetype();

	/**
	 * Get the first part of the mimetype of the file or folder i.e. 'image'
	 *
	 * @return string
	 */
	public function getMimePart();

	/**
	 * Get the storage the file or folder is storage on
	 *
	 * @return \OCP\Files\Storage
	 */
	public function getStorage();

	/**
	 * Get the file id of the file or folder
	 *
	 * @return int
	 */
	public function getId();

	/**
	 * Check whether the file is encrypted
	 *
	 * @return bool
	 */
	public function isEncrypted();

	/**
	 * Get the permissions of the file or folder as bitmasked combination of the following constants
	 * \OCP\PERMISSION_CREATE
	 * \OCP\PERMISSION_READ
	 * \OCP\PERMISSION_UPDATE
	 * \OCP\PERMISSION_DELETE
	 * \OCP\PERMISSION_SHARE
	 * \OCP\PERMISSION_ALL
	 *
	 * @return int
	 */
	public function getPermissions();

	/**
	 * Check whether this is a file or a folder
	 *
	 * @return \OCP\Files\FileInfo::TYPE_FILE|\OCP\Files\FileInfo::TYPE_FOLDER
	 */
	public function getType();

	/**
	 * Check if the file or folder is readable
	 *
	 * @return bool
	 */
	public function isReadable();

	/**
	 * Check if a file is writable
	 *
	 * @return bool
	 */
	public function isUpdateable();

	/**
	 * Check if a file or folder can be deleted
	 *
	 * @return bool
	 */
	public function isDeletable();

	/**
	 * Check if a file or folder can be shared
	 *
	 * @return bool
	 */
	public function isShareable();
}
