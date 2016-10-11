<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Interface for stream wrappers that implement url functions such as unlink, stat
 */
interface Url {
	/**
	 * @param string $path
	 * @param array $options
	 * @return bool
	 */
	public function dir_opendir($path, $options);

	/**
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string &$opened_path
	 * @return bool
	 */
	public function stream_open($path, $mode, $options, &$opened_path);

	/**
	 * @param string $path
	 * @param int $mode
	 * @param int $options
	 * @return bool
	 */
	public function mkdir($path, $mode, $options);

	/**
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function rename($source, $target);

	/**
	 * @param string $path
	 * @param int $options
	 * @return bool
	 */
	public function rmdir($path, $options);

	/**
	 * @param string
	 * @return bool
	 */
	public function unlink($path);

	/**
	 * @param string $path
	 * @param int $flags
	 * @return array
	 */
	public function url_stat($path, $flags);
}
