<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Stream;

use Icewind\Streams\Wrapper;

/**
 * stream wrapper limits the amount of data that can be written to a stream
 *
 * usage: resource \OC\Files\Stream\Quota::wrap($stream, $limit)
 */
class Quota extends Wrapper {
	/**
	 * @var int $limit
	 */
	private $limit;

	/**
	 * @param resource $stream
	 * @param int $limit
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

	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext('quota');
		$this->source = $context['source'];
		$this->limit = $context['limit'];

		return true;
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		// this wrapper needs to return "true" for success.
		// the fseek call itself returns 0 on succeess
		return fseek($this->source, $offset, $whence) === 0;
	}

	public function stream_read($count) {
		// Do not decrement $this->limit for reads
		return fread($this->source, $count);
	}

	public function stream_write($data) {
		$size = strlen($data);

		// Get current pointer and file size
		$curPos = ftell($this->source);
		fseek($this->source, 0, SEEK_END);
		$fileSize = ftell($this->source);
		fseek($this->source, $curPos, SEEK_SET);

		$writeEnd = $curPos + $size;

		// Calculate how many bytes are "new" (beyond end of existing)
		$newBytes = max(0, $writeEnd - $fileSize);

		// Enforce quota for new bytes only
		if ($newBytes > $this->limit) {
			 // Only this many new bytes are permitted:
			$allowedNewBytes = $this->limit;
			// Adjust write size to fit quota
			// Calculate max amount we can write, given cursor position and allowed new bytes
			$allowedSize = $size - ($newBytes - $allowedNewBytes);

			if ($allowedSize <= 0) {
				return 0; // No new bytes allowed
			}
			$data = substr($data, 0, $allowedSize);
			$size = $allowedSize;
			// Recalculate position/write end after truncation (for safety)
			$writeEnd = $curPos + $size;
			$newBytes = max(0, $writeEnd - $fileSize);
		}

		$written = fwrite($this->source, $data);

		// Decrement limit by actually written new bytes
		// (Extra safety: recalculate actual new bytes in case fwrite was truncated)
		$actualWriteEnd = ftell($this->source);
		$actualFileSize = max($fileSize, $actualWriteEnd);
		$actualNewBytes = max(0, $actualFileSize - $fileSize);
		// Decrement quota by the actual number of bytes written ($written),
		// not the intended size
		$this->limit -= $actualNewBytes;

		return $written;
	}
}
