<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\StringBuffer;

/**
 * Stream optimized for write only usage
 */
class NativeWriteStream extends NativeStream {
	const CHUNK_SIZE = 1048576; // 1MB chunks

	/** @var StringBuffer */
	private $writeBuffer;

	/** @var int */
	private $pos = 0;

	public function __construct() {
		$this->writeBuffer = new StringBuffer();
	}

	public function stream_open($path, $mode, $options, &$opened_path): bool {
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
		return parent::wrapClass($state, $smbStream, $mode, $url, NativeWriteStream::class);
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$this->flushWrite();
		$result = parent::stream_seek($offset, $whence);
		if ($result) {
			$pos = parent::stream_tell();
			if ($pos === false) {
				return false;
			}
			$this->pos = $pos;
		}
		return $result;
	}

	private function flushWrite(): void {
		parent::stream_write($this->writeBuffer->flush());
	}

	public function stream_write($data) {
		$written = $this->writeBuffer->push($data);
		$this->pos += $written;

		if ($this->writeBuffer->remaining() >= self::CHUNK_SIZE) {
			$this->flushWrite();
		}

		return $written;
	}

	public function stream_close() {
		try {
			$this->flushWrite();
			$flushResult = true;
		} catch (\Exception $e) {
			$flushResult = false;
		}
		return parent::stream_close() && $flushResult;
	}

	public function stream_tell() {
		return $this->pos;
	}

	public function stream_read($count) {
		return false;
	}

	public function stream_truncate($size) {
		$this->flushWrite();
		$this->pos = $size;
		return parent::stream_truncate($size);
	}
}
