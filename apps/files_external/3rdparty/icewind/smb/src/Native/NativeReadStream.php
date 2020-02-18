<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

/**
 * Stream optimized for read only usage
 */
class NativeReadStream extends NativeStream {
	const CHUNK_SIZE = 1048576; // 1MB chunks
	/**
	 * @var resource
	 */
	private $readBuffer = null;

	private $bufferSize = 0;

	private $pos = 0;

	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->readBuffer = fopen('php://memory', 'r+');

		return parent::stream_open($path, $mode, $options, $opened_path);
	}

	/**
	 * Wrap a stream from libsmbclient-php into a regular php stream
	 *
	 * @param \Icewind\SMB\NativeState $state
	 * @param resource $smbStream
	 * @param string $mode
	 * @param string $url
	 * @return resource
	 */
	public static function wrap($state, $smbStream, $mode, $url) {
		stream_wrapper_register('nativesmb', NativeReadStream::class);
		$context = stream_context_create([
			'nativesmb' => [
				'state'  => $state,
				'handle' => $smbStream,
				'url'    => $url
			]
		]);
		$fh = fopen('nativesmb://', $mode, false, $context);
		stream_wrapper_unregister('nativesmb');
		return $fh;
	}

	public function stream_read($count) {
		// php reads 8192 bytes at once
		// however due to network latency etc, it's faster to read in larger chunks
		// and buffer the result
		if (!parent::stream_eof() && $this->bufferSize < $count) {
			$remaining = $this->readBuffer;
			$this->readBuffer = fopen('php://memory', 'r+');
			$this->bufferSize = 0;
			stream_copy_to_stream($remaining, $this->readBuffer);
			$this->bufferSize += fwrite($this->readBuffer, parent::stream_read(self::CHUNK_SIZE));
			fseek($this->readBuffer, 0);
		}

		$result = fread($this->readBuffer, $count);
		$this->bufferSize -= $count;

		$read = strlen($result);
		$this->pos += $read;

		return $result;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$result = parent::stream_seek($offset, $whence);
		if ($result) {
			$this->readBuffer = fopen('php://memory', 'r+');
			$this->bufferSize = 0;
			$this->pos = parent::stream_tell();
		}
		return $result;
	}

	public function stream_eof() {
		return $this->bufferSize <= 0 && parent::stream_eof();
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
