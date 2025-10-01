<?php
/**
 * SPDX-FileCopyrightText: 2019 Roeland Jago Douma <roeland@famdouma.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
