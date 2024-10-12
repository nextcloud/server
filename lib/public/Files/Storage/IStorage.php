<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files\Storage;

use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IUpdater;
use OCP\Files\Cache\IWatcher;
use OCP\Files\InvalidPathException;

/**
 * Provide a common interface to all different storage options
 *
 * All paths passed to the storage are relative to the storage and should NOT have a leading slash.
 *
 * @since 9.0.0
 * @since 31.0.0 Moved the constructor to IConstructableStorage so that wrappers can use DI
 */
interface IStorage {
	/**
	 * Get the identifier for the storage,
	 * the returned id should be the same for every storage object that is created with the same parameters
	 * and two storage objects with the same id should refer to two storages that display the same files.
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getId();

	/**
	 * see https://www.php.net/manual/en/function.mkdir.php
	 * implementations need to implement a recursive mkdir
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function mkdir(string $path);

	/**
	 * see https://www.php.net/manual/en/function.rmdir.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function rmdir(string $path);

	/**
	 * see https://www.php.net/manual/en/function.opendir.php
	 *
	 * @return resource|false
	 * @since 9.0.0
	 */
	public function opendir(string $path);

	/**
	 * see https://www.php.net/manual/en/function.is-dir.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function is_dir(string $path);

	/**
	 * see https://www.php.net/manual/en/function.is-file.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function is_file(string $path);

	/**
	 * see https://www.php.net/manual/en/function.stat.php
	 * only the following keys are required in the result: size and mtime
	 *
	 * @return array|false
	 * @since 9.0.0
	 */
	public function stat(string $path);

	/**
	 * see https://www.php.net/manual/en/function.filetype.php
	 *
	 * @return string|false
	 * @since 9.0.0
	 */
	public function filetype(string $path);

	/**
	 * see https://www.php.net/manual/en/function.filesize.php
	 * The result for filesize when called on a folder is required to be 0
	 *
	 * @return int|float|false
	 * @since 9.0.0
	 */
	public function filesize(string $path);

	/**
	 * check if a file can be created in $path
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function isCreatable(string $path);

	/**
	 * check if a file can be read
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function isReadable(string $path);

	/**
	 * check if a file can be written to
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function isUpdatable(string $path);

	/**
	 * check if a file can be deleted
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function isDeletable(string $path);

	/**
	 * check if a file can be shared
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function isSharable(string $path);

	/**
	 * get the full permissions of a path.
	 * Should return a combination of the PERMISSION_ constants defined in lib/public/constants.php
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getPermissions(string $path);

	/**
	 * see https://www.php.net/manual/en/function.file-exists.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function file_exists(string $path);

	/**
	 * see https://www.php.net/manual/en/function.filemtime.php
	 *
	 * @return int|false
	 * @since 9.0.0
	 */
	public function filemtime(string $path);

	/**
	 * see https://www.php.net/manual/en/function.file-get-contents.php
	 *
	 * @return string|false
	 * @since 9.0.0
	 */
	public function file_get_contents(string $path);

	/**
	 * see https://www.php.net/manual/en/function.file-put-contents.php
	 *
	 * @return int|float|false
	 * @since 9.0.0
	 */
	public function file_put_contents(string $path, mixed $data);

	/**
	 * see https://www.php.net/manual/en/function.unlink.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function unlink(string $path);

	/**
	 * see https://www.php.net/manual/en/function.rename.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function rename(string $source, string $target);

	/**
	 * see https://www.php.net/manual/en/function.copy.php
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function copy(string $source, string $target);

	/**
	 * see https://www.php.net/manual/en/function.fopen.php
	 *
	 * @return resource|false
	 * @since 9.0.0
	 */
	public function fopen(string $path, string $mode);

	/**
	 * get the mimetype for a file or folder
	 * The mimetype for a folder is required to be "httpd/unix-directory"
	 *
	 * @return string|false
	 * @since 9.0.0
	 */
	public function getMimeType(string $path);

	/**
	 * see https://www.php.net/manual/en/function.hash-file.php
	 *
	 * @return string|false
	 * @since 9.0.0
	 */
	public function hash(string $type, string $path, bool $raw = false);

	/**
	 * see https://www.php.net/manual/en/function.disk-free-space.php
	 *
	 * @return int|float|false
	 * @since 9.0.0
	 */
	public function free_space(string $path);

	/**
	 * see https://www.php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function touch(string $path, ?int $mtime = null);

	/**
	 * get the path to a local version of the file.
	 * The local version of the file can be temporary and doesn't have to be persistent across requests
	 *
	 * @return string|false
	 * @since 9.0.0
	 */
	public function getLocalFile(string $path);

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @return bool
	 * @since 9.0.0
	 *
	 * hasUpdated for folders should return at least true if a file inside the folder is add, removed or renamed.
	 * returning true for other changes in the folder is optional
	 */
	public function hasUpdated(string $path, int $time);

	/**
	 * get the ETag for a file or folder
	 *
	 * @return string|false
	 * @since 9.0.0
	 */
	public function getETag(string $path);

	/**
	 * Returns whether the storage is local, which means that files
	 * are stored on the local filesystem instead of remotely.
	 * Calling getLocalFile() for local storages should always
	 * return the local files, whereas for non-local storages
	 * it might return a temporary file.
	 *
	 * @return bool true if the files are stored locally, false otherwise
	 * @since 9.0.0
	 */
	public function isLocal();

	/**
	 * Check if the storage is an instance of $class or is a wrapper for a storage that is an instance of $class
	 *
	 * @template T of IStorage
	 * @psalm-param class-string<T> $class
	 * @return bool
	 * @since 9.0.0
	 * @psalm-assert-if-true T $this
	 */
	public function instanceOfStorage(string $class);

	/**
	 * A custom storage implementation can return an url for direct download of a give file.
	 *
	 * For now the returned array can hold the parameter url - in future more attributes might follow.
	 *
	 * @return array|false
	 * @since 9.0.0
	 */
	public function getDirectDownload(string $path);

	/**
	 * @return void
	 * @throws InvalidPathException
	 * @since 9.0.0
	 */
	public function verifyPath(string $path, string $fileName);

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath);

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath);

	/**
	 * Test a storage for availability
	 *
	 * @since 9.0.0
	 * @return bool
	 */
	public function test();

	/**
	 * @since 9.0.0
	 * @return array [ available, last_checked ]
	 */
	public function getAvailability();

	/**
	 * @since 9.0.0
	 * @return void
	 */
	public function setAvailability(bool $isAvailable);

	/**
	 * @since 12.0.0
	 * @since 31.0.0 moved from Storage to IStorage
	 * @return bool
	 */
	public function needsPartFile();

	/**
	 * @return string|false
	 * @since 9.0.0
	 */
	public function getOwner(string $path);

	/**
	 * @return ICache
	 * @since 9.0.0
	 */
	public function getCache(string $path = '', ?IStorage $storage = null);

	/**
	 * @return IPropagator
	 * @since 9.0.0
	 */
	public function getPropagator();

	/**
	 * @return IScanner
	 * @since 9.0.0
	 */
	public function getScanner();

	/**
	 * @return IUpdater
	 * @since 9.0.0
	 */
	public function getUpdater();

	/**
	 * @return IWatcher
	 * @since 9.0.0
	 */
	public function getWatcher();

	/**
	 * Allow setting the storage owner
	 *
	 * This can be used for storages that do not have a dedicated owner, where we want to
	 * pass the user that we setup the mountpoint for along to the storage layer
	 *
	 * @param ?string $user Owner user id
	 * @return void
	 * @since 30.0.0
	 */
	public function setOwner(?string $user): void;
}
