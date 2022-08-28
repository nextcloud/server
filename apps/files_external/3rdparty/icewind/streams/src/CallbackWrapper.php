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
 *        'read'    => function($count){} (optional)
 *        'write'   => function($data){} (optional)
 *        'close'   => function(){} (optional)
 *        'readdir' => function(){} (optional)
 *     ]
 * ]
 *
 * All callbacks are called after the operation is executed on the source stream
 */
class CallbackWrapper extends Wrapper {
	/**
	 * @var callable|null
	 */
	protected $readCallback;

	/**
	 * @var callable|null
	 */
	protected $writeCallback;

	/**
	 * @var callable|null
	 */
	protected $closeCallback;

	/**
	 * @var callable|null
	 */
	protected $readDirCallBack;

	/**
	 * @var callable|null
	 */
	protected $preCloseCallback;

	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @param callable|null $read (optional)
	 * @param callable|null $write (optional)
	 * @param callable|null $close (optional)
	 * @param callable|null $readDir (optional)
	 * @param callable|null $preClose (optional)
	 * @return resource|false
	 *
	 */
	public static function wrap($source, $read = null, $write = null, $close = null, $readDir = null, $preClose = null) {
		$context = [
			'source'   => $source,
			'read'     => $read,
			'write'    => $write,
			'close'    => $close,
			'readDir'  => $readDir,
			'preClose' => $preClose,
		];
		return self::wrapSource($source, $context);
	}

	protected function open() {
		$context = $this->loadContext();

		$this->readCallback = $context['read'];
		$this->writeCallback = $context['write'];
		$this->closeCallback = $context['close'];
		$this->readDirCallBack = $context['readDir'];
		$this->preCloseCallback = $context['preClose'];
		return true;
	}

	public function dir_opendir($path, $options) {
		return $this->open();
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		return $this->open();
	}

	public function stream_read($count) {
		$result = parent::stream_read($count);
		if (is_callable($this->readCallback)) {
			call_user_func($this->readCallback, strlen($result));
		}
		return $result;
	}

	public function stream_write($data) {
		$result = parent::stream_write($data);
		if (is_callable($this->writeCallback)) {
			call_user_func($this->writeCallback, $data);
		}
		return $result;
	}

	public function stream_close() {
		if (is_callable($this->preCloseCallback)) {
			call_user_func($this->preCloseCallback, $this->source);
			// prevent further calls by potential PHP 7 GC ghosts
			$this->preCloseCallback = null;
		}
		$result = parent::stream_close();
		if (is_callable($this->closeCallback)) {
			call_user_func($this->closeCallback);
			// prevent further calls by potential PHP 7 GC ghosts
			$this->closeCallback = null;
		}
		return $result;
	}

	public function dir_readdir() {
		$result = parent::dir_readdir();
		if (is_callable($this->readDirCallBack)) {
			call_user_func($this->readDirCallBack);
		}
		return $result;
	}
}
