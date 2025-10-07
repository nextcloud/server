<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Constants;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IUser;

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
	 * Get the Etag of the file or folder.
	 *
	 * The Etag is a string id used to detect changes to a file or folder,
	 * every time the file or folder is changed the Etag will change to
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 7.0.0
	 */
	public function getEtag(): string;

	/**
	 * Get the size of the file or folder in bytes.
	 *
	 * @param bool $includeMounts whether or not to include the size of any sub mounts, since 16.0.0
	 * @since 7.0.0
	 */
	public function getSize(bool $includeMounts = true): int|float;

	/**
	 * Get the modified date of the file or folder as unix timestamp.
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 *
	 * @since 7.0.0
	 */
	public function getMtime(): int;

	/**
	 * Get the name of the file or folder.
	 *
	 * @since 7.0.0
	 */
	public function getName(): string;

	/**
	 * Get the path of the file or folder relative to the mountpoint of its storage.
	 *
	 * @since 7.0.0
	 */
	public function getInternalPath(): string;

	/**
	 * Get the full path of the file or folder.
	 *
	 * @since 7.0.0
	 */
	public function getPath(): string;

	/**
	 * Get the full mimetype of the file or folder i.e. 'image/png'
	 *
	 * @since 7.0.0
	 */
	public function getMimetype(): string;

	/**
	 * Get the first part of the mimetype of the file or folder i.e. 'image'
	 *
	 * @since 7.0.0
	 */
	public function getMimePart(): string;

	/**
	 * Get the storage the file or folder is storage on.
	 *
	 * @throws NotFoundException
	 * @since 7.0.0
	 */
	public function getStorage(): IStorage;

	/**
	 * Get the internal file id for the file or folder.
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 7.0.0
	 */
	public function getId(): int;

	/**
	 * Check whether the node is encrypted.
	 *
	 * If it is a file, then it is server side encrypted.
	 * If it is a folder, then it is end-to-end encrypted.
	 *
	 * @since 7.0.0
	 */
	public function isEncrypted(): bool;

	/**
	 * Get the permissions of the file or folder as bit-masked combination of the
	 * following constants.
	 *
	 * Constants::PERMISSION_CREATE
	 * Constants::PERMISSION_READ
	 * Constants::PERMISSION_UPDATE
	 * Constants::PERMISSION_DELETE
	 * Constants::PERMISSION_SHARE
	 * Constants::PERMISSION_ALL
	 *
	 * @return int-mask-of<Constants::PERMISSION_*>
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 7.0.0 - namespace of constants has changed in 8.0.0
	 */
	public function getPermissions(): int;

	/**
	 * Check whether this is a file or a folder
	 *
	 * @return FileInfo::TYPE_FILE|FileInfo::TYPE_FOLDER
	 * @since 7.0.0
	 */
	public function getType(): string;

	/**
	 * Check if the file or folder is readable.
	 *
	 * @throws NotFoundException
	 * @throws InvalidPathException
	 * @since 7.0.0
	 */
	public function isReadable(): bool;

	/**
	 * Check if the file or folder is writable.
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 7.0.0
	 */
	public function isUpdateable(): bool;

	/**
	 * Check whether new files or folders can be created inside this folder
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 8.0.0
	 */
	public function isCreatable(): bool;

	/**
	 * Check if a file or folder can be deleted
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 7.0.0
	 */
	public function isDeletable(): bool;

	/**
	 * Check if the file or folder is shareable.
	 *
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @since 7.0.0
	 */
	public function isShareable(): bool;

	/**
	 * Check if a file or folder is shared.
	 *
	 * @since 7.0.0
	 */
	public function isShared(): bool;

	/**
	 * Check if a file or folder is mounted
	 *
	 * @since 7.0.0
	 */
	public function isMounted(): bool;

	/**
	 * Get the mountpoint the file belongs to.
	 *
	 * @since 8.0.0
	 */
	public function getMountPoint(): IMountPoint;

	/**
	 * Get the owner of the file.
	 *
	 * @since 9.0.0
	 */
	public function getOwner(): ?IUser;

	/**
	 * Get the stored checksum(s) for this file.
	 *
	 * Checksums are stored in the format TYPE:CHECKSUM, here may be multiple checksums separated by a single space
	 * e.g. MD5:d3b07384d113edec49eaa6238ad5ff00 SHA1:f1d2d2f924e986ac86fdf7b36c94bcdf32beec15
	 *
	 * @since 9.0.0
	 */
	public function getChecksum(): string;

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
	 * Get the fileid or the parent folder  or -1 if this item has no parent folder
	 * (because it is the root).
	 *
	 * @since 28.0.0
	 */
	public function getParentId(): int;

	/**
	 * Get the metadata, if available.
	 *
	 * @return array<string, int|string|bool|float|string[]|int[]>
	 * @since 28.0.0
	 */
	public function getMetadata(): array;
}
