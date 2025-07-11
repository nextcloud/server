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
		if ($whence === SEEK_END) {
			// go to the end to find out last position's offset
			$oldOffset = $this->stream_tell();
			if (fseek($this->source, 0, $whence) !== 0) {
				return false;
			}
			$whence = SEEK_SET;
			$offset = $this->stream_tell() + $offset;
			$this->limit += $oldOffset - $offset;
		} elseif ($whence === SEEK_SET) {
			$this->limit += $this->stream_tell() - $offset;
		} else {
			$this->limit -= $offset;
		}
		// this wrapper needs to return "true" for success.
		// the fseek call itself returns 0 on succeess
		return fseek($this->source, $offset, $whence) === 0;
	}

	public function stream_read($count) {
		$this->limit -= $count;
		return fread($this->source, $count);
	}

	public function stream_write($data) {
		$size = strlen($data);
		if ($size > $this->limit) {
			$data = substr($data, 0, $this->limit);
			$size = $this->limit;
		}
		$this->limit -= $size;
		return fwrite($this->source, $data);
	}
}
