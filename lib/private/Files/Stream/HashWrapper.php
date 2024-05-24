<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
