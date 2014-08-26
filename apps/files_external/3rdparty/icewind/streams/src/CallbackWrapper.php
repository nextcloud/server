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
 *        'source' => resource
 *        'read'   => function($count){} (optional)
 *        'write'  => function($data){} (optional)
 *        'close'  => function(){} (optional)
 *     ]
 * ]
 *
 * All callbacks are called after the operation is executed on the source stream
 */
class CallbackWrapper extends Wrapper {
	/**
	 * @var callable
	 */
	protected $readCallback;

	/**
	 * @var callable
	 */
	protected $writeCallback;

	/**
	 * @var callable
	 */
	protected $closeCallback;

	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @param callable $read (optional)
	 * @param callable $write (optional)
	 * @param callable $close (optional)
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source, $read = null, $write = null, $close = null) {
		$context = stream_context_create(array(
			'callback' => array(
				'source' => $source,
				'read' => $read,
				'write' => $write,
				'close' => $close
			)
		));
		stream_wrapper_register('callback', '\Icewind\Streams\CallbackWrapper');
		try {
			$wrapped = fopen('callback://', 'r+', false, $context);
		} catch (\BadMethodCallException $e) {
			stream_wrapper_unregister('callback');
			throw $e;
		}
		stream_wrapper_unregister('callback');
		return $wrapped;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext('callback');

		if (isset($context['read']) and is_callable($context['read'])) {
			$this->readCallback = $context['read'];
		}
		if (isset($context['write']) and is_callable($context['write'])) {
			$this->writeCallback = $context['write'];
		}
		if (isset($context['close']) and is_callable($context['close'])) {
			$this->closeCallback = $context['close'];
		}
		return true;
	}

	public function stream_read($count) {
		$result = parent::stream_read($count);
		if ($this->readCallback) {
			call_user_func($this->readCallback, $count);
		}
		return $result;
	}

	public function stream_write($data) {
		$result = parent::stream_write($data);
		if ($this->writeCallback) {
			call_user_func($this->writeCallback, $data);
		}
		return $result;
	}

	public function stream_close() {
		$result = parent::stream_close();
		if ($this->closeCallback) {
			call_user_func($this->closeCallback);
		}
		return $result;
	}
}
