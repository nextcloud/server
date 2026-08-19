<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Storage;

/**
 * Storages that only hand a write to their backend once the stream is closed, so
 * closing a stream the writer gave up on would commit half a file over whatever
 * is already there.
 */
interface IAbortableWriteStorage {
	/**
	 * Give up on the write that is currently open for $path: what has been written
	 * so far is thrown away instead of being committed when the stream is closed,
	 * leaving the existing file untouched.
	 *
	 * Does nothing when no write is open for the path.
	 *
	 * @param string $path internal path of the file being written
	 */
	public function abortWrite(string $path): void;
}
