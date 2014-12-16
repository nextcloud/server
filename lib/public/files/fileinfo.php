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

	/*
	 * @const \OCP\Files\FileInfo::SPACE_NOT_COMPUTED Return value for a not computed space value
	 */
	const SPACE_NOT_COMPUTED = -1;
	/*
	 * @const \OCP\Files\FileInfo::SPACE_UNKNOWN Return value for unknown space value
	 */
	const SPACE_UNKNOWN = -2;
	/*
	 * @const \OCP\Files\FileInfo::SPACE_UNKNOWN Return value for unlimited space
	 */
	const SPACE_UNLIMITED = -3;

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
	 * \OCP\Constants::PERMISSION_CREATE
	 * \OCP\Constants::PERMISSION_READ
	 * \OCP\Constants::PERMISSION_UPDATE
	 * \OCP\Constants::PERMISSION_DELETE
	 * \OCP\Constants::PERMISSION_SHARE
	 * \OCP\Constants::PERMISSION_ALL
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
	 * Check whether new files or folders can be created inside this folder
	 *
	 * @return bool
	 */
	public function isCreatable();

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

	/**
	 * Check if a file or folder is shared
	 *
	 * @return bool
	 */
	public function isShared();

	/**
	 * Check if a file or folder is mounted
	 *
	 * @return bool
	 */
	public function isMounted();

	/**
	 * Get the mountpoint the file belongs to
	 *
	 * @return \OCP\Files\Mount\IMountPoint
	 */
	public function getMountPoint();
}
