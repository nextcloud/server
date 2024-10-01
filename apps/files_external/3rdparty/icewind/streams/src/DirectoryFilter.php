<?php
/**
 * SPDX-FileCopyrightText: 2015 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
 */

namespace Icewind\Streams;

/**
 * Wrapper allows filtering of directories
 *
 * The filter callback will be called for each entry in the folder
 * when the callback return false the entry will be filtered out
 */
class DirectoryFilter extends DirectoryWrapper {
	/**
	 * @var callable
	 */
	private $filter;

	/**
	 * @param string $path
	 * @param array $options
	 * @return bool
	 */
	public function dir_opendir($path, $options) {
		$context = $this->loadContext();
		$this->filter = $context['filter'];
		return true;
	}

	/**
	 * @return string
	 */
	public function dir_readdir() {
		$file = readdir($this->source);
		$filter = $this->filter;
		// keep reading until we have an accepted entry or we're at the end of the folder
		while ($file !== false && $filter($file) === false) {
			$file = readdir($this->source);
		}
		return $file;
	}

	/**
	 * @param resource $source
	 * @param callable $filter
	 * @return resource|false
	 */
	public static function wrap($source, callable $filter) {
		return self::wrapSource($source, [
			'source' => $source,
			'filter' => $filter
		]);
	}
}
