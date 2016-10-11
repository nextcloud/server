<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

/**
 * stream wrapper limits the amount of data that can be written to a stream
 *
 * usage: void \OC\Files\Stream\Quota::register($id, $stream, $limit)
 * or:    resource \OC\Files\Stream\Quota::wrap($stream, $limit)
 */
class Quota {
	private static $streams = array();

	/**
	 * @var resource $source
	 */
	private $source;

	/**
	 * @var int $limit
	 */
	private $limit;

	/**
	 * @param string $id
	 * @param resource $stream
	 * @param int $limit
	 */
	public static function register($id, $stream, $limit) {
		self::$streams[$id] = array($stream, $limit);
	}

	/**
	 * remove all registered streams
	 */
	public static function clear() {
		self::$streams = array();
	}

	/**
	 * @param resource $stream
	 * @param int $limit
	 * @return resource
	 */
	static public function wrap($stream, $limit) {
		$id = uniqid();
		self::register($id, $stream, $limit);
		$meta = stream_get_meta_data($stream);
		return fopen('quota://' . $id, $meta['mode']);
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$id = substr($path, strlen('quota://'));
		if (isset(self::$streams[$id])) {
			list($this->source, $this->limit) = self::$streams[$id];
			return true;
		} else {
			return false;
		}
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		if ($whence === SEEK_END){
			// go to the end to find out last position's offset
			$oldOffset = $this->stream_tell();
			if (fseek($this->source, 0, $whence) !== 0){
				return false;
			}
			$whence = SEEK_SET;
			$offset = $this->stream_tell() + $offset;
			$this->limit += $oldOffset - $offset;
		}
		else if ($whence === SEEK_SET) {
			$this->limit += $this->stream_tell() - $offset;
		} else {
			$this->limit -= $offset;
		}
		// this wrapper needs to return "true" for success.
		// the fseek call itself returns 0 on succeess
		return fseek($this->source, $offset, $whence) === 0;
	}

	public function stream_tell() {
		return ftell($this->source);
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
		return flock($this->source, $mode);
	}

	public function stream_flush() {
		return fflush($this->source);
	}

	public function stream_eof() {
		return feof($this->source);
	}

	public function stream_close() {
		fclose($this->source);
	}
}
