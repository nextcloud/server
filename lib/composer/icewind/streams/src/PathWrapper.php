<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * A string-like object that maps to an existing stream when opened
 */
class PathWrapper extends NullWrapper {
	/**
	 * @param resource $source
	 * @return Path|string
	 */
	public static function getPath($source) {
		return new Path(NullWrapper::class, [
			NullWrapper::getProtocol() => ['source' => $source]
		]);
	}
}
