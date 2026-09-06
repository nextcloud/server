<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Stream;

use Icewind\Streams\File;
use Icewind\Streams\Wrapper;

/**
 * A stream wrapper that uses HTTP range requests to provide a seekable stream
 * for HTTP reading.
 */
class SeekableHttpStream implements File {
	private const string PROTOCOL = 'httpseek';

	/**
	 * Registers the stream wrapper using the `httpseek://` URL scheme.
	 */
	private static function registerIfNeeded(): void {
		if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
			stream_wrapper_register(
				self::PROTOCOL,
				self::class
			);
		}
	}

	/**
	 * Opens a read-only, seekable HTTP stream.
	 *
	 * The callback is called with a byte range and must return an HTTP stream
	 * for that range.
	 *
	 * @psalm-param impure-callable(string): (resource|false) $callback
	 *
	 * @return resource|false
	 */
	public static function open(callable $callback) {
		$context = stream_context_create([
			self::PROTOCOL => [
				'callback' => $callback
			],
		]);

		self::registerIfNeeded();

		return fopen(self::PROTOCOL . '://', 'r', false, $context);
	}

	/** @var resource */
	public $context;

	/** @var impure-callable(string): (resource|false) */
	private $openCallback;

	/** @var ?resource|closed-resource */
	private $current = null;

	/** Absolute offset within the remote resource represented by this stream. */
	private int $offset = 0;

	/** Total size of the remote resource represented by this stream. */
	private int $totalSize = 0;

	private bool $needReconnect = false;

	/**
	 * @param array<int, mixed> $responseHeaders
	 * @return array{begin: int, end: int, totalSize: int}|null
	 */
	private function parseContentRange(array $responseHeaders): ?array {
		foreach ($responseHeaders as $header) {
			if (!is_string($header)) {
				continue;
			}

			if (preg_match(
				'/^content-range:\s*bytes\s+(\d+)-(\d+)\/(\d+)\s*$/i',
				$header,
				$matches
			) !== 1) {
				continue;
			}

			$begin = (int)$matches[1];
			$end = (int)$matches[2];
			$totalSize = (int)$matches[3];

			if ($end < $begin || $totalSize <= $end) {
				return null;
			}

			return [
				'begin' => $begin,
				'end' => $end,
				'totalSize' => $totalSize,
			];
		}

		return null;
	}

	private function reconnect(int $start): bool {
		if ($start < 0) {
			return false;
		}

		$this->closeCurrent();

		$range = $start . '-';
		$stream = ($this->openCallback)($range);
		if ($stream === false) {
			$this->closeCurrent();
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

			$this->closeCurrent();
			throw new \Exception(
				'Failed to get source stream from stream wrapper of ' . get_class($responseHead)
			);
		}

		if (!is_array($responseHead)) {
			$this->closeCurrent();
			return false;
		}

		$contentRange = $this->parseContentRange($responseHead);

		if ($contentRange === null || $contentRange['begin'] !== $start) {
			$this->closeCurrent();
			return false;
		}

		if ($start === 0) {
			$this->totalSize = $contentRange['totalSize'];
		} elseif ($this->totalSize !== $contentRange['totalSize']) {
			$this->closeCurrent();
			return false;
		}

		$this->offset = $contentRange['begin'];
		$this->needReconnect = false;

		return true;
	}

	/**
	 * @return resource|null
	 */
	private function getCurrent() {
		if ($this->needReconnect && !$this->reconnect($this->offset)) {
			return null;
		}

		return $this->hasOpenStream() ? $this->current : null;
	}

	/**
	 * @return bool
	 *
	 * @psalm-assert-if-true resource $this->current
	 */
	private function hasOpenStream(): bool {
		return is_resource($this->current);
	}

	private function closeCurrent(): void {
		if ($this->hasOpenStream()) {
			fclose($this->current);
		}

		$this->current = null;
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$options = stream_context_get_options($this->context)[self::PROTOCOL];
		$this->openCallback = $options['callback'];

		return $this->reconnect(0);
	}

	#[\Override]
	public function stream_read($count) {
		if ($count <= 0) {
			return '';
		}

		$remaining = $this->totalSize - $this->offset;
		if ($remaining <= 0) {
			return '';
		}

		$stream = $this->getCurrent();
		if (!$stream) {
			return false;
		}

		// Bound reads by Content-Range; premature underlying EOF is not
		// explicitly detected if fewer bytes than expected are returned.
		$ret = fread($stream, min($count, $remaining));
		if ($ret === false) {
			return false;
		}

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
				if ($this->totalSize === 0) {
					return false;
				} elseif ($this->totalSize + $offset === $this->offset) {
					return true;
				} else {
					$this->offset = $this->totalSize + $offset;
				}
				break;
		}

		$this->closeCurrent();
		$this->needReconnect = true;

		return true;
	}

	#[\Override]
	public function stream_tell() {
		return $this->offset;
	}

	#[\Override]
	public function stream_stat() {
		$stream = $this->getCurrent();
		if (!$stream) {
			return false;
		}

		$stat = fstat($stream);
		if ($stat !== false) {
			$stat['size'] = $this->totalSize;
		}

		return $stat;
	}

	#[\Override]
	public function stream_eof() {
		if ($this->offset >= $this->totalSize) {
			return true;
		}

		if (!$this->getCurrent()) {
			return true;
		}

		return false;
	}

	#[\Override]
	public function stream_close() {
		$this->closeCurrent();
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
		// No-op because this is a read-only stream.
	}
}
