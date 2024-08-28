<?php
/**
 * SPDX-FileCopyrightText: 2014 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
 */

namespace Icewind\Streams;

/**
 * Stream wrapper that does nothing, used for tests
 */
class NullWrapper extends Wrapper {
	public static function wrap($source) {
		return self::wrapSource($source);
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext();
		return true;
	}

	public function dir_opendir($path, $options) {
		$this->loadContext();
		return true;
	}
}
