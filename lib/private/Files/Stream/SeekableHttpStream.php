<?php
/**
 * @copyright Copyright (c) 2020, Lukas Stabe (lukas@stabe.de)
 *
 * @author Lukas Stabe <lukas@stabe.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\Stream;

use Icewind\Streams\File;
use Icewind\Streams\Wrapper;

/**
 * A stream wrapper that uses http range requests to provide a seekable stream for http reading
 */
class SeekableHttpStream implements File {
	private const PROTOCOL = 'httpseek';

	private static bool $registered = false;

	/**
	 * Registers the stream wrapper using the `httpseek://` url scheme
	 * $return void
	 */
	private static function registerIfNeeded() {
		if (!self::$registered) {
			stream_wrapper_register(
				self::PROTOCOL,
				self::class
			);
			self::$registered = true;
		}
	}

	/**
	 * Open a readonly-seekable http stream
	 *
	 * The provided callback will be called with byte range and should return an http stream for the requested range
	 *
	 * @param callable $callback
	 * @return false|resource
	 */
	public static function open(callable $callback) {
		$context = stream_context_create([
			SeekableHttpStream::PROTOCOL => [
				'callback' => $callback
			],
		]);

		SeekableHttpStream::registerIfNeeded();
		return fopen(SeekableHttpStream::PROTOCOL . '://', 'r', false, $context);
	}

	/** @var resource */
	public $context;

	/** @var callable */
	private $openCallback;

	/** @var ?resource|closed-resource */
	private $current;
	/** @var int $offset offset of the current chunk */
	private int $offset = 0;
	/** @var int $length length of the current chunk */
	private int $length = 0;
	/** @var int $totalSize size of the full stream */
	private int $totalSize = 0;
	private bool $needReconnect = false;

	private function reconnect(int $start): bool {
		$this->needReconnect = false;
		$range = $start . '-';
		if ($this->hasOpenStream()) {
			fclose($this->current);
		}

		$stream = ($this->openCallback)($range);

		if ($stream === false) {
			$this->current = null;
			return false;
		}
		$this->current = $stream;

		$responseHead = stream_get_meta_data($this->current)['wrapper_data'];

		while ($responseHead instanceof Wrapper) {
			$wrapperOptions = stream_context_get_options($responseHead->context);
			foreach ($wrapperOptions as $options) {
				if (isset($options['source']) && is_resource($options['source'])) {
					$responseHead = stream_get_meta_data($options['source'])['wrapper_data'];
					continue 2;
				}
			}
			throw new \Exception("Failed to get source stream from stream wrapper of " . get_class($responseHead));
		}

		$rangeHeaders = array_values(array_filter($responseHead, function ($v) {
			return preg_match('#^content-range:#i', $v) === 1;
		}));
		if (!$rangeHeaders) {
			$this->current = null;
			return false;
		}
		$contentRange = $rangeHeaders[0];

		$content = trim(explode(':', $contentRange)[1]);
		$range = trim(explode(' ', $content)[1]);
		$begin = intval(explode('-', $range)[0]);
		$length = intval(explode('/', $range)[1]);

		if ($begin !== $start) {
			$this->current = null;
			return false;
		}

		$this->offset = $begin;
		$this->length = $length;
		if ($start === 0) {
			$this->totalSize = $length;
		}

		return true;
	}

	/**
	 * @return ?resource
	 */
	private function getCurrent() {
		if ($this->needReconnect) {
			$this->reconnect($this->offset);
		}
		if (is_resource($this->current)) {
			return $this->current;
		} else {
			return null;
		}
	}

	/**
	 * @return bool
	 * @psalm-assert-if-true resource $this->current
	 */
	private function hasOpenStream(): bool {
		return is_resource($this->current);
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$options = stream_context_get_options($this->context)[self::PROTOCOL];
		$this->openCallback = $options['callback'];

		return $this->reconnect(0);
	}

	public function stream_read($count) {
		if (!$this->getCurrent()) {
			return false;
		}
		$ret = fread($this->getCurrent(), $count);
		$this->offset += strlen($ret);
		return $ret;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		switch ($whence) {
			case SEEK_SET:
				if ($offset === $this->offset) {
					return true;
				} else {
					$this->offset = $offset;
				}
				break;
			case SEEK_CUR:
				if ($offset === 0) {
					return true;
				} else {
					$this->offset += $offset;
				}
				break;
			case SEEK_END:
				if ($this->length === 0) {
					return false;
				} elseif ($this->length + $offset === $this->offset) {
					return true;
				} else {
					$this->offset = $this->length + $offset;
				}
				break;
		}

		if ($this->hasOpenStream()) {
			fclose($this->current);
		}
		$this->current = null;
		$this->needReconnect = true;
		return true;
	}

	public function stream_tell() {
		return $this->offset;
	}

	public function stream_stat() {
		if ($this->getCurrent()) {
			$stat = fstat($this->getCurrent());
			if ($stat) {
				$stat['size'] = $this->totalSize;
			}
			return $stat;
		} else {
			return false;
		}
	}

	public function stream_eof() {
		if ($this->getCurrent()) {
			return feof($this->getCurrent());
		} else {
			return true;
		}
	}

	public function stream_close() {
		if ($this->hasOpenStream()) {
			fclose($this->current);
		}
		$this->current = null;
	}

	public function stream_write($data) {
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	public function stream_truncate($size) {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}

	public function stream_flush() {
		return; //noop because readonly stream
	}
}
