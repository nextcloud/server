<?php
/**
 * SPDX-FileCopyrightText: 2016 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
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
