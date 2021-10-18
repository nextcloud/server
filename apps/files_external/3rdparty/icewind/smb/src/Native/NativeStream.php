<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\InvalidRequestException;
use Icewind\Streams\File;
use InvalidArgumentException;

abstract class NativeStream implements File {
	/**
	 * @var resource
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	public $context;

	/**
	 * @var NativeState
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $state;

	/**
	 * @var resource
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $handle;

	/**
	 * @var bool
	 */
	protected $eof = false;

	/**
	 * @var string
	 */
	protected $url = '';

	/**
	 * Wrap a stream from libsmbclient-php into a regular php stream
	 *
	 * @param NativeState $state
	 * @param resource $smbStream
	 * @param string $mode
	 * @param string $url
	 * @param class-string<NativeStream> $class
	 * @return resource
	 */
	protected static function wrapClass(NativeState $state, $smbStream, string $mode, string $url, string $class) {
		if (stream_wrapper_register('nativesmb', $class) === false) {
			throw new Exception("Failed to register stream wrapper");
		}
		$context = stream_context_create([
			'nativesmb' => [
				'state'  => $state,
				'handle' => $smbStream,
				'url'    => $url
			]
		]);
		$fh = fopen('nativesmb://', $mode, false, $context);
		if (stream_wrapper_unregister('nativesmb') === false) {
			throw new Exception("Failed to unregister stream wrapper");
		}
		return $fh;
	}

	public function stream_close() {
		try {
			return $this->state->close($this->handle, $this->url);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function stream_eof() {
		return $this->eof;
	}

	public function stream_flush() {
		return false;
	}


	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = stream_context_get_options($this->context);
		if (!isset($context['nativesmb']) || !is_array($context['nativesmb'])) {
			throw new InvalidArgumentException("context not set");
		}
		$state = $context['nativesmb']['state'];
		if (!$state instanceof NativeState) {
			throw new InvalidArgumentException("invalid context set");
		}
		$this->state = $state;
		$handle = $context['nativesmb']['handle'];
		if (!is_resource($handle)) {
			throw new InvalidArgumentException("invalid context set");
		}
		$this->handle = $handle;
		$url = $context['nativesmb']['url'];
		if (!is_string($url)) {
			throw new InvalidArgumentException("invalid context set");
		}
		$this->url = $url;
		return true;
	}

	public function stream_read($count) {
		$result = $this->state->read($this->handle, $count, $this->url);
		if (strlen($result) < $count) {
			$this->eof = true;
		}
		return $result;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$this->eof = false;
		try {
			return $this->state->lseek($this->handle, $offset, $whence, $this->url) !== false;
		} catch (InvalidRequestException $e) {
			return false;
		}
	}

	/**
	 * @return array{"mtime": int, "size": int, "mode": int}|false
	 */
	public function stream_stat() {
		try {
			return $this->state->stat($this->url);
		} catch (Exception $e) {
			return false;
		}
	}

	public function stream_tell() {
		return $this->state->lseek($this->handle, 0, SEEK_CUR, $this->url);
	}

	public function stream_write($data) {
		return $this->state->write($this->handle, $data, $this->url);
	}

	public function stream_truncate($size) {
		return $this->state->ftruncate($this->handle, $size, $this->url);
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}
}
