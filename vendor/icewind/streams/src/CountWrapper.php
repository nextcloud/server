<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\Streams;

/**
 * Wrapper that counts the amount of data read and written
 *
 * The following options should be passed in the context when opening the stream
 * [
 *     'callback' => [
 *        'source'  => resource
 *        'callback'    => function($readCount, $writeCount){}
 *     ]
 * ]
 *
 * The callback will be called when the stream is closed
 */
class CountWrapper extends Wrapper {
	/**
	 * @var int
	 */
	protected $readCount = 0;

	/**
	 * @var int
	 */
	protected $writeCount = 0;

	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @param callable $callback
	 * @return resource|false
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source, $callback) {
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException('Invalid or missing callback');
		}
		return self::wrapSource($source, [
			'source'   => $source,
			'callback' => $callback
		]);
	}

	protected function open() {
		$context = $this->loadContext();
		$this->callback = $context['callback'];
		return true;
	}

    public function stream_seek($offset, $whence = SEEK_SET) {
        if ($whence === SEEK_SET) {
            $this->readCount = $offset;
            $this->writeCount = $offset;
        } else if ($whence === SEEK_CUR) {
            $this->readCount += $offset;
            $this->writeCount += $offset;
        }
        return parent::stream_seek($offset, $whence);
    }

	public function dir_opendir($path, $options) {
		return $this->open();
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		return $this->open();
	}

	public function stream_read($count) {
		$result = parent::stream_read($count);
		$this->readCount += strlen($result);
		return $result;
	}

	public function stream_write($data) {
		$result = parent::stream_write($data);
		$this->writeCount += strlen($data);
		return $result;
	}

	public function stream_close() {
		$result = parent::stream_close();
		call_user_func($this->callback, $this->readCount, $this->writeCount);
		return $result;
	}
}
