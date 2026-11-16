<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Stream;

use Icewind\Streams\File;
use Icewind\Streams\Wrapper;
use OCP\Http\Client\IResponse;

/**
 * A stream wrapper that uses http range requests to provide a seekable stream for http reading
 */
class SeekableHttpStream implements File {
	private const string PROTOCOL = 'httpseek';

	/**
	 * Registers the stream wrapper using the `httpseek://` url scheme
	 * $return void
	 */
	private static function registerIfNeeded() {
		if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
			stream_wrapper_register(
				self::PROTOCOL,
				self::class
			);
		}
	}

	/**
	 * Open a readonly-seekable http stream
	 *
	 * The provided callback will be called with byte range and should return an http stream opened by the http:// wrapper
	 * or a streamed response for the requested range
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

		$result = ($this->openCallback)($range);

		if ($result instanceof IResponse) {
			$stream = $result->getBody();
			$content = $result->getHeader('Content-Range');
		} else {
			$stream = $result;
			$content = is_resource($stream) ? $this->getContentRangeFromWrapperData($stream) : '';
		}

		if (!is_resource($stream) || $content === '') {
			$this->current = null;
			return false;
		}
		$this->current = $stream;

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
	 * Get the Content-Range header from the response head of a stream opened by the http:// wrapper.
	 *
	 * @param resource $stream
	 */
	private function getContentRangeFromWrapperData($stream): string {
		$responseHead = stream_get_meta_data($stream)['wrapper_data'];

		while ($responseHead instanceof Wrapper) {
			$wrapperOptions = stream_context_get_options($responseHead->context);
			foreach ($wrapperOptions as $options) {
				if (isset($options['source']) && is_resource($options['source'])) {
					$responseHead = stream_get_meta_data($options['source'])['wrapper_data'];
					continue 2;
				}
			}
			throw new \Exception('Failed to get source stream from stream wrapper of ' . get_class($responseHead));
		}

		foreach ($responseHead ?? [] as $header) {
			if (preg_match('#^content-range:#i', $header) === 1) {
				return trim(explode(':', $header, 2)[1]);
			}
		}
		return '';
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

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$options = stream_context_get_options($this->context)[self::PROTOCOL];
		$this->openCallback = $options['callback'];

		return $this->reconnect(0);
	}

	#[\Override]
	public function stream_read($count) {
		if (!$this->getCurrent()) {
			return false;
		}
		$ret = fread($this->getCurrent(), $count);
		$this->offset += strlen($ret);
		return $ret;
	}

	#[\Override]
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

	#[\Override]
	public function stream_tell() {
		return $this->offset;
	}

	#[\Override]
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

	#[\Override]
	public function stream_eof() {
		if ($this->getCurrent()) {
			return feof($this->getCurrent());
		} else {
			return true;
		}
	}

	#[\Override]
	public function stream_close() {
		if ($this->hasOpenStream()) {
			fclose($this->current);
		}
		$this->current = null;
	}

	#[\Override]
	public function stream_write($data) {
		return false;
	}

	#[\Override]
	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	#[\Override]
	public function stream_truncate($size) {
		return false;
	}

	#[\Override]
	public function stream_lock($operation) {
		return false;
	}

	#[\Override]
	public function stream_flush() {
		return; //noop because readonly stream
	}
}
