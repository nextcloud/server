<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	 * @return resource|bool
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
