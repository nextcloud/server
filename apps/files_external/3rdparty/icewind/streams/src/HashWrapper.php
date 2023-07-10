<?php
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

abstract class HashWrapper extends Wrapper {

	/**
	 * @var callable|null
	 */
	private $callback;

	/**
	 * @var resource|\HashContext
	 */
	private $hashContext;

	/**
	 * Wraps a stream to make it seekable
	 *
	 * @param resource $source
	 * @param string $hash
	 * @param callable $callback
	 * @return resource|false
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source, $hash, $callback) {
		$context = [
			'hash'     => $hash,
			'callback' => $callback,
		];
		return self::wrapSource($source, $context);
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext();
		$this->callback = $context['callback'];
		$this->hashContext = hash_init($context['hash']);
		return true;
	}

	protected function updateHash($data) {
		hash_update($this->hashContext, $data);
	}

	public function stream_close() {
		$hash = hash_final($this->hashContext);
		if ($this->hashContext !== false && is_callable($this->callback)) {
			call_user_func($this->callback, $hash);
		}
		return parent::stream_close();
	}
}
