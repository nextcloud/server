<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * This interface allows to manage simple files.
 *
 * This interface must not be implemented in your application but
 * instead should be used as a service and injected in your code with
 * dependency injection.
 *
 * @since 11.0.0
 */
interface ISimpleFile {
	/**
	 * Get the name
	 *
	 * @since 11.0.0
	 */
	public function getName(): string;

	/**
	 * Get the size in bytes
	 *
	 * @since 11.0.0
	 */
	public function getSize(): int|float;

	/**
	 * Get the ETag
	 *
	 * @since 11.0.0
	 */
	public function getETag(): string;

	/**
	 * Get the last modification time
	 *
	 * @since 11.0.0
	 */
	public function getMTime(): int;

	/**
	 * Get the content
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @since 11.0.0
	 */
	public function getContent(): string;

	/**
	 * Overwrite the file
	 *
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @since 11.0.0
	 */
	public function putContent($data): void;

	/**
	 * Delete the file
	 *
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function delete(): void;

	/**
	 * Get the MimeType
	 *
	 * @since 11.0.0
	 */
	public function getMimeType(): string;

	/**
	 * @since 24.0.0
	 */
	public function getExtension(): string;

	/**
	 * Open the file as stream for reading, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function read();

	/**
	 * Open the file as stream for writing, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|bool
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function write();
}
