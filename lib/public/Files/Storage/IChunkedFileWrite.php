<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace OCP\Files\Storage;

use OCP\Files\GenericFileException;

/**
 * @since 26.0.0
 */
interface IChunkedFileWrite extends IStorage {
	/**
	 * @param string $targetPath Relative target path in the storage
	 * @return string writeToken to be used with the other methods to uniquely identify the file write operation
	 * @throws GenericFileException
	 * @since 26.0.0
	 */
	public function startChunkedWrite(string $targetPath): string;

	/**
	 * @param string $targetPath
	 * @param string $writeToken
	 * @param string $chunkId
	 * @param resource $data
	 * @param int|null $size
	 * @throws GenericFileException
	 * @since 26.0.0
	 */
	public function putChunkedWritePart(string $targetPath, string $writeToken, string $chunkId, $data, ?int $size = null): ?array;

	/**
	 * @param string $targetPath
	 * @param string $writeToken
	 * @return int
	 * @throws GenericFileException
	 * @since 26.0.0
	 */
	public function completeChunkedWrite(string $targetPath, string $writeToken): int;

	/**
	 * @param string $targetPath
	 * @param string $writeToken
	 * @throws GenericFileException
	 * @since 26.0.0
	 */
	public function cancelChunkedWrite(string $targetPath, string $writeToken): void;
}
