<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Stream;

class StaticStream {
	public $context;
	protected static $data = array();

	protected $path = '';
	protected $pointer = 0;
	protected $writable = false;

	public function stream_close() {
	}

	public function stream_eof() {
		return $this->pointer >= strlen(self::$data[$this->path]);
	}

	public function stream_flush() {
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		switch ($mode[0]) {
			case 'r':
				if (!isset(self::$data[$path])) return false;
				$this->path = $path;
				$this->writable = isset($mode[1]) && $mode[1] == '+';
				break;
			case 'w':
				self::$data[$path] = '';
				$this->path = $path;
				$this->writable = true;
				break;
			case 'a':
				if (!isset(self::$data[$path])) self::$data[$path] = '';
				$this->path = $path;
				$this->writable = true;
				$this->pointer = strlen(self::$data[$path]);
				break;
			case 'x':
				if (isset(self::$data[$path])) return false;
				$this->path = $path;
				$this->writable = true;
				break;
			case 'c':
				if (!isset(self::$data[$path])) self::$data[$path] = '';
				$this->path = $path;
				$this->writable = true;
				break;
			default:
				return false;
		}
		$opened_path = $this->path;
		return true;
	}

	public function stream_read($count) {
		$bytes = min(strlen(self::$data[$this->path]) - $this->pointer, $count);
		$data = substr(self::$data[$this->path], $this->pointer, $bytes);
		$this->pointer += $bytes;
		return $data;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$len = strlen(self::$data[$this->path]);
		switch ($whence) {
			case SEEK_SET:
				if ($offset <= $len) {
					$this->pointer = $offset;
					return true;
				}
				break;
			case SEEK_CUR:
				if ($this->pointer + $offset <= $len) {
					$this->pointer += $offset;
					return true;
				}
				break;
			case SEEK_END:
				if ($len + $offset <= $len) {
					$this->pointer = $len + $offset;
					return true;
				}
				break;
		}
		return false;
	}

	public function stream_stat() {
		$size = strlen(self::$data[$this->path]);
		$time = time();
		return array(
			0 => 0,
			'dev' => 0,
			1 => 0,
			'ino' => 0,
			2 => 0777,
			'mode' => 0777,
			3 => 1,
			'nlink' => 1,
			4 => 0,
			'uid' => 0,
			5 => 0,
			'gid' => 0,
			6 => '',
			'rdev' => '',
			7 => $size,
			'size' => $size,
			8 => $time,
			'atime' => $time,
			9 => $time,
			'mtime' => $time,
			10 => $time,
			'ctime' => $time,
			11 => -1,
			'blksize' => -1,
			12 => -1,
			'blocks' => -1,
		);
	}

	public function stream_tell() {
		return $this->pointer;
	}

	public function stream_write($data) {
		if (!$this->writable) return 0;
		$size = strlen($data);
		if ($this->stream_eof()) {
			self::$data[$this->path] .= $data;
		} else {
			self::$data[$this->path] = substr_replace(
				self::$data[$this->path],
				$data,
				$this->pointer
			);
		}
		$this->pointer += $size;
		return $size;
	}

	public function unlink($path) {
		if (isset(self::$data[$path])) {
			unset(self::$data[$path]);
		}
		return true;
	}

	public function url_stat($path) {
		if (isset(self::$data[$path])) {
			$size = strlen(self::$data[$path]);
			$time = time();
			return array(
				0 => 0,
				'dev' => 0,
				1 => 0,
				'ino' => 0,
				2 => 0777,
				'mode' => 0777,
				3 => 1,
				'nlink' => 1,
				4 => 0,
				'uid' => 0,
				5 => 0,
				'gid' => 0,
				6 => '',
				'rdev' => '',
				7 => $size,
				'size' => $size,
				8 => $time,
				'atime' => $time,
				9 => $time,
				'mtime' => $time,
				10 => $time,
				'ctime' => $time,
				11 => -1,
				'blksize' => -1,
				12 => -1,
				'blocks' => -1,
			);
		}
		return false;
	}
}
