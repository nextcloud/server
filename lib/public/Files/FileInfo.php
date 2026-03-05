<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Storage\IStorage;

/**
 * Interface FileInfo
 *
 * @since 7.0.0
 */
#[Consumable(since: '7.0.0')]
interface FileInfo {
	/**
	 * @since 7.0.0
	 */
	public const TYPE_FILE = 'file';
	/**
	 * @since 7.0.0
	 */
	public const TYPE_FOLDER = 'dir';

	/**
	 * @const \OCP\Files\FileInfo::SPACE_NOT_COMPUTED Return value for a not computed space value
	 * @since 8.0.0
	 */
	public const SPACE_NOT_COMPUTED = -1;
	/**
	 * @const \OCP\Files\FileInfo::SPACE_UNKNOWN Return value for unknown space value
	 * @since 8.0.0
	 */
	public const SPACE_UNKNOWN = -2;
	/**
	 * @const \OCP\Files\FileInfo::SPACE_UNLIMITED Return value for unlimited space
	 * @since 8.0.0
	 */
	public const SPACE_UNLIMITED = -3;

	/**
	 * @since 9.1.0
	 */
	public const MIMETYPE_FOLDER = 'httpd/unix-directory';

	/**
	 * @const \OCP\Files\FileInfo::BLACKLIST_FILES_REGEX Return regular expression to test filenames against (blacklisting)
	 * @since 12.0.0
	 */
	public const BLACKLIST_FILES_REGEX = '\.(part|filepart)$';

	/**
	 * Get the Etag of the file or folder
	 *
	 * @return string
	 * @since 7.0.0
	 */
	public function getEtag();

	/**
	 * Get the size in bytes for the file or folder
	 *
	 * @param bool $includeMounts whether or not to include the size of any sub mounts, since 16.0.0
	 * @return int|float
	 * @since 7.0.0
	 */
	public function getSize($includeMounts = true);

	/**
	 * Get the last modified date as timestamp for the file or folder
	 *
	 * @return int
	 * @since 7.0.0
	 */
	public function getMtime();

	/**
	 * Get the name of the file or folder
	 *
	 * @return string
	 * @since 7.0.0
	 */
	public function getName();

	/**
	 * Get the path relative to the storage
	 *
	 * @return string
	 * @since 7.0.0
	 */
	public function getInternalPath();

	/**
	 * Get the absolute path
	 *
	 * @return string
	 * @since 7.0.0
	 */
	public function getPath();

	/**
	 * Get the full mimetype of the file or folder i.e. 'image/png'
	 *
	 * @since 7.0.0
	 */
	public function getMimetype(): string;

	/**
	 * Get the first part of the mimetype of the file or folder i.e. 'image'
	 *
	 * @return string
	 * @since 7.0.0
	 */
	public function getMimePart();

	/**
	 * Get the storage the file or folder is storage on
	 *
	 * @return IStorage
	 * @since 7.0.0
	 */
	public function getStorage();

	/**
	 * Get the file id of the file or folder
	 *
	 * @return int|null
	 * @since 7.0.0
	 */
	public function getId();

	/**
	 * Check whether the node is encrypted.
	 * If it is a file, then it is server side encrypted.
	 * If it is a folder, then it is end-to-end encrypted.
	 *
	 * @return bool
	 * @since 7.0.0
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
	 * @since 7.0.0 - namespace of constants has changed in 8.0.0
	 */
	public function getPermissions();

	/**
	 * Check whether this is a file or a folder
	 *
	 * @return string \OCP\Files\FileInfo::TYPE_FILE|\OCP\Files\FileInfo::TYPE_FOLDER
	 * @since 7.0.0
	 */
	public function getType();

	/**
	 * Check if the file or folder is readable
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isReadable();

	/**
	 * Check if a file is writable
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isUpdateable();

	/**
	 * Check whether new files or folders can be created inside this folder
	 *
	 * @return bool
	 * @since 8.0.0
	 */
	public function isCreatable();

	/**
	 * Check if a file or folder can be deleted
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isDeletable();

	/**
	 * Check if a file or folder can be shared
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isShareable();

	/**
	 * Check if a file or folder is shared
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isShared();

	/**
	 * Check if a file or folder is mounted
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isMounted();

	/**
	 * Get the mountpoint the file belongs to
	 *
	 * @return \OCP\Files\Mount\IMountPoint
	 * @since 8.0.0
	 */
	public function getMountPoint();

	/**
	 * Get the owner of the file
	 *
	 * @return ?\OCP\IUser
	 * @since 9.0.0
	 */
	public function getOwner();

	/**
	 * Get the stored checksum(s) for this file
	 *
	 * Checksums are stored in the format TYPE:CHECKSUM, here may be multiple checksums separated by a single space
	 * e.g. MD5:d3b07384d113edec49eaa6238ad5ff00 SHA1:f1d2d2f924e986ac86fdf7b36c94bcdf32beec15
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getChecksum();

	/**
	 * Get the extension of the file
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getExtension(): string;

	/**
	 * Get the creation date as unix timestamp
	 *
	 * If the creation time is not known, 0 will be returned
	 *
	 * creation time is not set automatically by the server and is generally only available
	 * for files uploaded by the sync clients
	 *
	 * @return int
	 * @since 18.0.0
	 */
	public function getCreationTime(): int;

	/**
	 * Get the upload date as unix timestamp
	 *
	 * If the upload time is not known, 0 will be returned
	 *
	 * Upload time will be set automatically by the server for files uploaded over DAV
	 * files created by Nextcloud apps generally do not have an the upload time set
	 *
	 * @return int
	 * @since 18.0.0
	 */
	public function getUploadTime(): int;

	/**
	 * Get the fileid or the parent folder
	 * or -1 if this item has no parent folder (because it is the root)
	 *
	 * @return int
	 * @since 28.0.0
	 */
	public function getParentId(): int;

	/**
	 * Get the metadata, if available
	 *
	 * @return array<string, int|string|bool|float|string[]|int[]>
	 * @since 28.0.0
	 */
	public function getMetadata(): array;
}
