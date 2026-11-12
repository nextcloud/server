<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use Icewind\Streams\Wrapper;

/**
 * Read-only stream wrapper that keeps errors of the source stream away from
 * an archive streamer, so the archive entry being written can be completed.
 *
 * Exceptions and failed reads of the source end the stream instead of being
 * propagated. In fixed size mode the stream delivers exactly the declared
 * size: missing data is filled with null bytes and extra data is dropped, as
 * required by formats that write the entry size before its content (tar).
 *
 * The callback receives the wrapper when the stream is closed.
 */
class ArchiveEntryStream extends Wrapper {
	private int|float $size = 0;
	private bool $fixedSize = false;
	/** @var callable(ArchiveEntryStream): void */
	private $callback;
	private int|float $bytesRead = 0;
	private int|float $bytesDelivered = 0;
	private bool $sourceEnded = false;
	private bool $failed = false;
	private bool $truncated = false;
	private ?\Throwable $throwable = null;

	/**
	 * @param resource $source
	 * @param int|float $size the size of the entry being streamed
	 * @param bool $fixedSize if enabled and the streamed size differs from $size the entry will be either 0-padded or truncated
	 * @param callable(ArchiveEntryStream): void $callback called when the stream is closed
	 * @return resource|false
	 */
	public static function wrap($source, int|float $size, bool $fixedSize, callable $callback) {
		return self::wrapSource($source, [
			'source' => $source,
			'size' => $size,
			'fixedSize' => $fixedSize,
			'callback' => $callback,
		]);
	}

	#[\Override]
	public function dir_opendir($path, $options) {
		return false;
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext();
		$this->size = $context['size'];
		$this->fixedSize = $context['fixedSize'];
		$this->callback = $context['callback'];
		return true;
	}

	#[\Override]
	public function stream_read($count) {
		if ($this->fixedSize) {
			$count = (int)min($count, $this->size - $this->bytesDelivered);
			if ($count <= 0) {
				return '';
			}
		}

		$data = $this->readSource($count);
		if ($this->fixedSize && $data === '') {
			$data = str_repeat("\0", $count);
		}

		$this->bytesDelivered += strlen($data);
		return $data;
	}

	#[\Override]
	public function stream_eof() {
		if ($this->fixedSize) {
			return $this->bytesDelivered >= $this->size;
		}
		return $this->failed || $this->sourceEnded;
	}

	#[\Override]
	public function stream_write($data) {
		return false;
	}

	#[\Override]
	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}

	#[\Override]
	public function stream_close() {
		if ($this->fixedSize && !$this->failed && !$this->sourceEnded) {
			// check whether the source holds more data than the declared size
			try {
				$extra = fread($this->source, 1);
				$this->truncated = $extra !== false && $extra !== '';
			} catch (\Throwable) {
			}
		}

		$result = parent::stream_close();
		($this->callback)($this);
		return $result;
	}

	/**
	 * Bytes read from the source stream.
	 */
	public function getBytesRead(): int|float {
		return $this->bytesRead;
	}

	/**
	 * Whether reading from the source stream failed before it ended.
	 */
	public function hasFailed(): bool {
		return $this->failed;
	}

	/**
	 * The throwable thrown by the source stream, if any.
	 */
	public function getThrowable(): ?\Throwable {
		return $this->throwable;
	}

	/**
	 * Whether the source held more data than the declared size (fixed size mode only).
	 */
	public function isTruncated(): bool {
		return $this->truncated;
	}

	private function readSource(int $count): string {
		if ($this->failed || $this->sourceEnded) {
			return '';
		}

		try {
			$data = fread($this->source, $count);
		} catch (\Throwable $e) {
			$this->throwable = $e;
			$data = false;
		}

		if ($data === false) {
			$this->failed = true;
			return '';
		}
		if ($data === '') {
			$this->sourceEnded = true;
		}

		$this->bytesRead += strlen($data);
		return $data;
	}
}
