<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\StringBuffer;

/**
 * Stream optimized for read only usage
 */
class NativeReadStream extends NativeStream {
	const CHUNK_SIZE = 1048576; // 1MB chunks

	/** @var StringBuffer */
	private $readBuffer;

	public function __construct() {
		$this->readBuffer = new StringBuffer();
	}

	/** @var int */
	private $pos = 0;

	public function stream_open($path, $mode, $options, &$opened_path) {
		return parent::stream_open($path, $mode, $options, $opened_path);
	}

	/**
	 * Wrap a stream from libsmbclient-php into a regular php stream
	 *
	 * @param NativeState $state
	 * @param resource $smbStream
	 * @param string $mode
	 * @param string $url
	 * @return resource
	 */
	public static function wrap(NativeState $state, $smbStream, string $mode, string $url) {
		return parent::wrapClass($state, $smbStream, $mode, $url, NativeReadStream::class);
	}

	public function stream_read($count) {
		// php reads 8192 bytes at once
		// however due to network latency etc, it's faster to read in larger chunks
		// and buffer the result
		if (!parent::stream_eof() && $this->readBuffer->remaining() < $count) {
			$chunk = parent::stream_read(self::CHUNK_SIZE);
			if ($chunk === false) {
				return false;
			}
			$this->readBuffer->push($chunk);
		}

		$result = $this->readBuffer->read($count);

		$read = strlen($result);
		$this->pos += $read;

		return $result;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$result = parent::stream_seek($offset, $whence);
		if ($result) {
			$this->readBuffer->clear();
			$pos = parent::stream_tell();
			if ($pos === false) {
				return false;
			}
			$this->pos = $pos;
		}
		return $result;
	}

	public function stream_eof() {
		return $this->readBuffer->remaining() <= 0 && parent::stream_eof();
	}

	public function stream_tell() {
		return $this->pos;
	}

	public function stream_write($data) {
		return false;
	}

	public function stream_truncate($size) {
		return false;
	}
}
