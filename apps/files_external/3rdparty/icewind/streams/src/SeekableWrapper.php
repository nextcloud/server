<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Wrapper that provides callbacks for write, read and close
 *
 * The following options should be passed in the context when opening the stream
 * [
 *     'callback' => [
 *        'source'  => resource
 *     ]
 * ]
 *
 * All callbacks are called after the operation is executed on the source stream
 */
class SeekableWrapper extends Wrapper {
	/**
	 * @var resource
	 */
	protected $cache;

	/**
	 * Wraps a stream to make it seekable
	 *
	 * @param resource $source
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source) {
		$context = stream_context_create(array(
			'callback' => array(
				'source' => $source
			)
		));
		return Wrapper::wrapSource($source, $context, 'callback', '\Icewind\Streams\SeekableWrapper');
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext('callback');
		$this->cache = fopen('php://temp', 'w+');
		return true;
	}

	protected function readTill($position) {
		$current = ftell($this->source);
		if ($position > $current) {
			$data = parent::stream_read($position - $current);
			$cachePosition = ftell($this->cache);
			fseek($this->cache, $current);
			fwrite($this->cache, $data);
			fseek($this->cache, $cachePosition);
		}
	}

	public function stream_read($count) {
		$current = ftell($this->cache);
		$this->readTill($current + $count);
		return fread($this->cache, $count);
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		if ($whence === SEEK_SET) {
			$target = $offset;
		} else if ($whence === SEEK_CUR) {
			$current = ftell($this->cache);
			$target = $current + $offset;
		} else {
			return false;
		}
		$this->readTill($target);
		return fseek($this->cache, $target) === 0;
	}

	public function stream_tell() {
		return ftell($this->cache);
	}

	public function stream_eof() {
		return parent::stream_eof() and (ftell($this->source) === ftell($this->cache));
	}
}
