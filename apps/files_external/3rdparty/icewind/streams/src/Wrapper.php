<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Base class for stream wrappers, wraps an existing stream
 *
 * This wrapper itself doesn't implement any functionality but is just a base class for other wrappers to extend
 */
abstract class Wrapper implements File, Directory {
	/**
	 * @var resource
	 */
	public $context;

	/**
	 * The wrapped stream
	 *
	 * @var resource
	 */
	protected $source;

	protected static function wrapSource($source, $context, $protocol, $class) {
		if (!is_resource($source)) {
			throw new \BadMethodCallException();
		}
		try {
			stream_wrapper_register($protocol, $class);
			if (self::isDirectoryHandle($source)) {
				$wrapped = opendir($protocol . '://', $context);
			} else {
				$wrapped = fopen($protocol . '://', 'r+', false, $context);
			}
		} catch (\BadMethodCallException $e) {
			stream_wrapper_unregister($protocol);
			throw $e;
		}
		stream_wrapper_unregister($protocol);
		return $wrapped;
	}

	protected static function isDirectoryHandle($resource) {
		$meta = stream_get_meta_data($resource);
		return $meta['stream_type'] == 'dir';
	}

	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @param string $name
	 * @return array
	 * @throws \Exception
	 */
	protected function loadContext($name) {
		$context = stream_context_get_options($this->context);
		if (isset($context[$name])) {
			$context = $context[$name];
		} else {
			throw new \BadMethodCallException('Invalid context, "' . $name . '" options not set');
		}
		if (isset($context['source']) and is_resource($context['source'])) {
			$this->setSourceStream($context['source']);
		} else {
			throw new \BadMethodCallException('Invalid context, source not set');
		}
		return $context;
	}

	/**
	 * @param resource $source
	 */
	protected function setSourceStream($source) {
		$this->source = $source;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$result = fseek($this->source, $offset, $whence);
		return $result == 0 ? true : false;
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
				stream_set_write_buffer($this->source, $arg1);
		}
	}

	public function stream_truncate($size) {
		return ftruncate($this->source, $size);
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
		return fclose($this->source);
	}

	public function dir_readdir() {
		return readdir($this->source);
	}

	public function dir_closedir() {
		closedir($this->source);
		return true;
	}

	public function dir_rewinddir() {
		return rewind($this->source);
	}
}
