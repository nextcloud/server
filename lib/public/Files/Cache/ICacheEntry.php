<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Cache;

use ArrayAccess;
use OCP\AppFramework\Attribute\Consumable;

/**
 * meta data for a file or folder
 *
 * @since 9.0.0
 * @template-extends ArrayAccess<string,mixed>
 *
 * This interface extends \ArrayAccess since v21.0.0, previous versions only
 * implemented it in the private implementation. Hence php would allow using the
 * object as array, while strictly speaking it didn't support this.
 */
#[Consumable(since: '9.0.0')]
interface ICacheEntry extends ArrayAccess {
	/**
	 * @since 9.0.0
	 */
	public const DIRECTORY_MIMETYPE = 'httpd/unix-directory';

	/**
	 * Get the numeric id of a file
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getId();

	/**
	 * Get the numeric id for the storage
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getStorageId();

	/**
	 * Get the path of the file relative to the storage root
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getPath();

	/**
	 * Get the file name
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getName();

	/**
	 * Get the full mimetype
	 *
	 * @since 9.0.0
	 */
	public function getMimeType(): string;

	/**
	 * Get the first part of the mimetype
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getMimePart();

	/**
	 * Get the file size in bytes
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getSize();

	/**
	 * Get the last modified date as unix timestamp
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getMTime();

	/**
	 * Get the last modified date on the storage as unix timestamp
	 *
	 * Note that when a file is updated we also update the mtime of all parent folders to make it visible to the user which folder has had updates most recently
	 * This can differ from the mtime on the underlying storage which usually only changes when a direct child is added, removed or renamed
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getStorageMTime();

	/**
	 * Get the etag for the file
	 *
	 * An etag is used for change detection of files and folders, an etag of a file changes whenever the content of the file changes
	 * Etag for folders change whenever a file in the folder has changed
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getEtag();

	/**
	 * Get the permissions for the file stored as bitwise combination of \OCP\Constants::PERMISSION_READ, \OCP\Constants::PERMISSION_CREATE
	 * \OCP\Constants::PERMISSION_UPDATE, \OCP\Constants::PERMISSION_DELETE and \OCP\Constants::PERMISSION_SHARE
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getPermissions();

	/**
	 * Check if the file is encrypted
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function isEncrypted();

	/**
	 * Get the metadata etag for the file
	 *
	 * @return string | null
	 * @since 18.0.0
	 */
	public function getMetadataEtag(): ?string;

	/**
	 * Get the last modified date as unix timestamp
	 *
	 * @return int | null
	 * @since 18.0.0
	 */
	public function getCreationTime(): ?int;

	/**
	 * Get the last modified date as unix timestamp
	 *
	 * @return int | null
	 * @since 18.0.0
	 */
	public function getUploadTime(): ?int;

	/**
	 * Get the unencrypted size
	 *
	 * This might be different from the result of getSize
	 *
	 * @return int
	 * @since 25.0.0
	 */
	public function getUnencryptedSize(): int;

	/**
	 * Get the file id of the parent folder
	 *
	 * @return int
	 * @since 32.0.0
	 */
	public function getParentId(): int;
}
