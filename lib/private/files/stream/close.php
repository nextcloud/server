<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Stream;

/**
 * stream wrapper that provides a callback on stream close
 */
class Close {
	private static $callBacks = array();
	private $path = '';
	private $source;
	private static $open = array();

	public function stream_open($path, $mode, $options, &$opened_path) {
		$path = substr($path, strlen('close://'));
		$this->path = $path;
		$this->source = fopen($path, $mode);
		if (is_resource($this->source)) {
			$this->meta = stream_get_meta_data($this->source);
		}
		self::$open[] = $path;
		return is_resource($this->source);
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		return fseek($this->source, $offset, $whence) === 0;
	}

	public function stream_tell() {
		return ftell($this->source);
	}

	public function stream_read($count) {
		return fread($this->source, $count);
	}

	public function stream_write($data) {
		return fwrite($this->source, $data);
	}

	public function stream_set_option($option, $arg1, $arg2) {
		switch ($option) {
			case STREAM_OPTION_BLOCKING:
				stream_set_blocking($this->source, $arg1);
				break;
			case STREAM_OPTION_READ_TIMEOUT:
				stream_set_timeout($this->source, $arg1, $arg2);
				break;
			case STREAM_OPTION_WRITE_BUFFER:
				stream_set_write_buffer($this->source, $arg1, $arg2);
		}
	}

	public function stream_stat() {
		return fstat($this->source);
	}

	public function stream_lock($mode) {
		flock($this->source, $mode);
	}

	public function stream_flush() {
		return fflush($this->source);
	}

	public function stream_eof() {
		return feof($this->source);
	}

	public function url_stat($path) {
		$path = substr($path, strlen('close://'));
		if (file_exists($path)) {
			return stat($path);
		} else {
			return false;
		}
	}

	public function stream_close() {
		fclose($this->source);
		if (isset(self::$callBacks[$this->path])) {
			call_user_func(self::$callBacks[$this->path], $this->path);
		}
	}

	public function unlink($path) {
		$path = substr($path, strlen('close://'));
		return unlink($path);
	}

	/**
	 * @param string $path
	 */
	public static function registerCallback($path, $callback) {
		self::$callBacks[$path] = $callback;
	}
}
