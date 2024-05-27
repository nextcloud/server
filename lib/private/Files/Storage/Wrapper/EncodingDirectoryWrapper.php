<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use Icewind\Streams\DirectoryWrapper;
use OC\Files\Filesystem;

/**
 * Normalize file names while reading directory entries
 */
class EncodingDirectoryWrapper extends DirectoryWrapper {
	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch Until return type is fixed upstream
	 * @return string|false
	 */
	public function dir_readdir() {
		$file = readdir($this->source);
		if ($file !== false && $file !== '.' && $file !== '..') {
			$file = trim(Filesystem::normalizePath($file), '/');
		}

		return $file;
	}

	/**
	 * @param resource $source
	 * @param callable $filter
	 * @return resource|false
	 */
	public static function wrap($source) {
		return self::wrapSource($source, [
			'source' => $source,
		]);
	}
}
