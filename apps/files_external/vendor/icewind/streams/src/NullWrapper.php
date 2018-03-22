<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Stream wrapper that does nothing, used for tests
 */
class NullWrapper extends Wrapper {
	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source) {
		$context = stream_context_create(array(
			'null' => array(
				'source' => $source)
		));
		return Wrapper::wrapSource($source, $context, 'null', '\Icewind\Streams\NullWrapper');
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext('null');
		return true;
	}

	public function dir_opendir($path, $options) {
		$this->loadContext('null');
		return true;
	}
}
