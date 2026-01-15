<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Files;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Lock\LockedException;

/**
 * Represents a file, which is a leaf node in a hierarchical structure.
 *
 * @since 6.0.0
 */
#[Consumable(since: '6.0.0')]
interface File extends Node {
	/**
	 * Get the content of the file as string.
	 *
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 * @since 6.0.0
	 */
	public function getContent(): string;

	/**
	 * Write to the file from string data
	 *
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 * @since 6.0.0
	 */
	public function putContent($data): void;

	/**
	 * Open the file as stream, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @since 6.0.0
	 */
	public function fopen(string $mode);

	/**
	 * Compute the hash of the file.
	 *
	 * Type of hash is set with $type and can be anything supported by php's hash_file
	 *
	 * @since 6.0.0
	 */
	public function hash(string $type, bool $raw = false): string;

	/**
	 * Get the stored checksum for this file,
	 *
	 * @since 9.0.0
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function getChecksum(): string;

	/**
	 * Get the extension of this file.
	 *
	 * @since 15.0.0
	 */
	public function getExtension(): string;
}
