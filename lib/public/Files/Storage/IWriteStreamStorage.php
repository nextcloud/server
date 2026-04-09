<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Storage;

use OCP\Files\GenericFileException;

/**
 * Interface that adds the ability to write a stream directly to file
 *
 * @since 15.0.0
 */
interface IWriteStreamStorage extends IStorage {
	/**
	 * Write the data from a stream to a file
	 *
	 * @param resource $stream
	 * @param ?int $size the size of the stream if known in advance
	 * @return int the number of bytes written
	 * @throws GenericFileException
	 * @since 15.0.0
	 */
	public function writeStream(string $path, $stream, ?int $size = null): int;
}
