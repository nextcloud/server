<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Lock\LockedException;

/**
 * Interface File
 *
 * @since 6.0.0
 */
#[Consumable(since: '6.0.0')]
interface File extends Node {
	/**
	 * Get the content of the file as string
	 *
	 * @return string
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 * @since 6.0.0
	 */
	public function getContent();

	/**
	 * Write to the file from string data
	 *
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 * @since 6.0.0
	 */
	public function putContent($data);

	/**
	 * Get the mimetype of the file
	 *
	 * @since 6.0.0
	 */
	public function getMimeType(): string;

	/**
	 * Open the file as stream, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @param string $mode
	 * @return resource|false
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @since 6.0.0
	 */
	public function fopen($mode);

	/**
	 * Compute the hash of the file
	 * Type of hash is set with $type and can be anything supported by php's hash_file
	 *
	 * @param string $type
	 * @param bool $raw
	 * @return string
	 * @since 6.0.0
	 */
	public function hash($type, $raw = false);

	/**
	 * Get the stored checksum for this file
	 *
	 * @return string
	 * @since 9.0.0
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getChecksum();

	/**
	 * Get the extension of this file
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getExtension(): string;
}
