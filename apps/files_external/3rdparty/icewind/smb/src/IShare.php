<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\AlreadyExistsException;
use Icewind\SMB\Exception\InvalidRequestException;
use Icewind\SMB\Exception\InvalidTypeException;
use Icewind\SMB\Exception\NotFoundException;

interface IShare {
	/**
	 * Get the name of the share
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Download a remote file
	 *
	 * @param string $source remove file
	 * @param string $target local file
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function get(string $source, string $target): bool;

	/**
	 * Upload a local file
	 *
	 * @param string $source local file
	 * @param string $target remove file
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function put(string $source, string $target): bool;

	/**
	 * Open a readable stream to a remote file
	 *
	 * @param string $source
	 * @return resource a read only stream with the contents of the remote file
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function read(string $source);

	/**
	 * Open a writable stream to a remote file
	 * Note: This method will truncate the file to 0bytes
	 *
	 * @param string $target
	 * @return resource a write only stream to upload a remote file
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function write(string $target);

	/**
	 * Open a writable stream to a remote file and set the cursor to the end of the file
	 *
	 * @param string $target
	 * @return resource a write only stream to upload a remote file
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 * @throws InvalidRequestException
	 */
	public function append(string $target);

	/**
	 * Rename a remote file
	 *
	 * @param string $from
	 * @param string $to
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws AlreadyExistsException
	 */
	public function rename(string $from, string $to): bool;

	/**
	 * Delete a file on the share
	 *
	 * @param string $path
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function del(string $path): bool;

	/**
	 * List the content of a remote folder
	 *
	 * @param string $path
	 * @return IFileInfo[]
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function dir(string $path): array;

	/**
	 * @param string $path
	 * @return IFileInfo
	 *
	 * @throws NotFoundException
	 */
	public function stat(string $path): IFileInfo;

	/**
	 * Create a folder on the share
	 *
	 * @param string $path
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws AlreadyExistsException
	 */
	public function mkdir(string $path): bool;

	/**
	 * Remove a folder on the share
	 *
	 * @param string $path
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws InvalidTypeException
	 */
	public function rmdir(string $path): bool;

	/**
	 * @param string $path
	 * @param int $mode a combination of FileInfo::MODE_READONLY, FileInfo::MODE_ARCHIVE, FileInfo::MODE_SYSTEM and FileInfo::MODE_HIDDEN, FileInfo::NORMAL
	 * @return mixed
	 */
	public function setMode(string $path, int $mode);

	/**
	 * @param string $path
	 * @return INotifyHandler
	 */
	public function notify(string $path);

	/**
	 * Get the IServer instance for this share
	 *
	 * @return IServer
	 */
	public function getServer(): IServer;
}
