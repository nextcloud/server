<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Stream;

use Icewind\Streams\Wrapper;

/**
 * Stream wrapper that limits how far a stream may grow when written to.
 *
 * Each wrapped stream maintains its own remaining-byte allowance. The
 * allowance is initialized when the wrapper is opened and is adjusted to
 * account for reads, writes, and repositioning. It is not a live view of the
 * underlying storage quota, nor is it shared with other wrappers for the same
 * source stream.
 *
 * @example
 * $stream = \OC\Files\Stream\Quota::wrap($source, $limit);
 */
class Quota extends Wrapper {
	/** @var int|float $limit Remaining number of bytes that may be written. */
	private $limit;

	/**
	 * @param resource $stream
	 * @param int|float $limit
	 * @return resource|false
	 */
	public static function wrap($stream, $limit) {
		$context = stream_context_create([
			'quota' => [
				'source' => $stream,
				'limit' => $limit
			]
		]);
		return Wrapper::wrapSource($stream, $context, 'quota', self::class);
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext('quota');
		$this->source = $context['source'];
		$this->limit = $context['limit'];

		return true;
	}

	#[\Override]
	public function dir_opendir($path, $options) {
		return false;
	}

	#[\Override]
	public function stream_seek($offset, $whence = SEEK_SET) {
		$oldPosition = $this->stream_tell();

		if ($whence === SEEK_END) {
			if (fseek($this->source, 0, SEEK_END) !== 0) {
				// Best effort
				fseek($this->source, $oldPosition, SEEK_SET);
				return false;
			}

			$offset = $this->stream_tell() + $offset;
			$whence = SEEK_SET;
		}

		if (fseek($this->source, $offset, $whence) !== 0) {
			// Best effort
			fseek($this->source, $oldPosition, SEEK_SET);
			return false;
		}

		$newPosition = $this->stream_tell();
		$this->limit += $oldPosition - $newPosition;

		return true;
	}

	#[\Override]
	public function stream_read($count) {
		$data = fread($this->source, $count);
		$this->limit -= strlen($data);
		return $data;
	}

	#[\Override]
	public function stream_write($data) {
		$size = strlen($data);
		if ($this->limit <= 0) {
			return 0;
		}

		if ($size > $this->limit) {
			$data = substr($data, 0, (int)$this->limit);
		}
		$written = fwrite($this->source, $data);
		// Decrement quota by the actual number of bytes written ($written),
		// not the intended size
		$this->limit -= $written;
		return $written;
	}
}
