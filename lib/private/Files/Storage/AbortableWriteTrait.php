<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Storage;

use Icewind\Streams\CallbackWrapper;

/**
 * Bookkeeping for storages that collect a write in a temporary file and only push
 * it to their backend when the stream is closed.
 *
 * Such a storage cannot tell a finished write from an abandoned one - both arrive
 * as a plain fclose() - so a writer that fails half way has to say so before it
 * closes the stream, otherwise the partial copy is committed over the existing
 * file. See {@see IAbortableWriteStorage}.
 */
trait AbortableWriteTrait {
	/**
	 * Ids of the writes currently open, per path. Several writes can be open for
	 * the same path at once, hence the list.
	 *
	 * @var array<string, list<int>>
	 */
	private array $openWrites = [];

	/** @var array<int, bool> write id => whether the write was given up on */
	private array $writeAborted = [];

	private int $lastWriteId = 0;

	/**
	 * Wrap a write handle so that closing it only commits when the write was not
	 * aborted in the meantime.
	 *
	 * @param resource $handle handle on the temporary file collecting the write
	 * @param string $path internal path, normalized the same way abortWrite() normalizes it
	 * @param string $tmpFile the temporary file behind $handle, removed when the write is aborted
	 * @param callable():void $commit hands the finished temporary file to the backend
	 * @return resource|false
	 */
	protected function wrapAbortableWrite($handle, string $path, string $tmpFile, callable $commit) {
		$writeId = $this->registerOpenWrite($path);

		return CallbackWrapper::wrap($handle, null, null, function () use ($path, $writeId, $tmpFile, $commit): void {
			if ($this->releaseOpenWrite($path, $writeId)) {
				// the file is only half written, keep what the storage already has
				if (file_exists($tmpFile)) {
					unlink($tmpFile);
				}
				return;
			}

			$commit();
		});
	}

	/**
	 * @param string $path internal path, normalized the same way wrapAbortableWrite() got it
	 */
	protected function abortOpenWrites(string $path): void {
		foreach ($this->openWrites[$path] ?? [] as $writeId) {
			$this->writeAborted[$writeId] = true;
		}
	}

	private function registerOpenWrite(string $path): int {
		$writeId = ++$this->lastWriteId;
		$this->openWrites[$path][] = $writeId;
		$this->writeAborted[$writeId] = false;

		return $writeId;
	}

	/**
	 * Forget a write that is being closed.
	 *
	 * @return bool whether it was given up on
	 */
	private function releaseOpenWrite(string $path, int $writeId): bool {
		$aborted = $this->writeAborted[$writeId] ?? false;
		unset($this->writeAborted[$writeId]);

		$stillOpen = array_values(array_filter(
			$this->openWrites[$path] ?? [],
			static fn (int $openId): bool => $openId !== $writeId,
		));
		if ($stillOpen === []) {
			unset($this->openWrites[$path]);
		} else {
			$this->openWrites[$path] = $stillOpen;
		}

		return $aborted;
	}
}
