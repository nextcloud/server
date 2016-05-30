<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Stream;

class StaticStream {
	const MODE_FILE = 0100000;

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

	public static function clear() {
		self::$data = array();
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
		return $this->url_stat($this->path);
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
			$data = array(
				'dev' => 0,
				'ino' => 0,
				'mode' => self::MODE_FILE | 0777,
				'nlink' => 1,
				'uid' => 0,
				'gid' => 0,
				'rdev' => '',
				'size' => $size,
				'atime' => $time,
				'mtime' => $time,
				'ctime' => $time,
				'blksize' => -1,
				'blocks' => -1,
			);
			return array_values($data) + $data;
		}
		return false;
	}
}
