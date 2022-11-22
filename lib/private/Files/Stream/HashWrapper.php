<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
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

use Icewind\Streams\Wrapper;

class HashWrapper extends Wrapper {
	protected $callback;
	protected $hash;

	public static function wrap($source, string $algo, callable $callback) {
		$hash = hash_init($algo);
		$context = stream_context_create([
			'hash' => [
				'source' => $source,
				'callback' => $callback,
				'hash' => $hash,
			],
		]);
		return Wrapper::wrapSource($source, $context, 'hash', self::class);
	}

	protected function open() {
		$context = $this->loadContext('hash');

		$this->callback = $context['callback'];
		$this->hash = $context['hash'];
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
		hash_update($this->hash, $result);
		return $result;
	}

	public function stream_close() {
		if (is_callable($this->callback)) {
			// if the stream is closed as a result of the end-of-request GC, the hash context might be cleaned up before this stream
			if ($this->hash instanceof \HashContext) {
				try {
					$hash = @hash_final($this->hash);
					if ($hash) {
						call_user_func($this->callback, $hash);
					}
				} catch (\Throwable $e) {
				}
			}
			// prevent further calls by potential PHP 7 GC ghosts
			$this->callback = null;
		}
		return parent::stream_close();
	}
}
