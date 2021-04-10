<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Interface for stream wrappers that implements a file
 */
interface File {
	/**
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string $opened_path
	 * @return bool
	 */
	public function stream_open($path, $mode, $options, &$opened_path);

	/**
	 * @param int $offset
	 * @param int $whence
	 * @return bool
	 */
	public function stream_seek($offset, $whence = SEEK_SET);

	/**
	 * @return int|false
	 */
	public function stream_tell();

	/**
	 * @param int $count
	 * @return string|false
	 */
	public function stream_read($count);

	/**
	 * @param string $data
	 * @return int|false
	 */
	public function stream_write($data);

	/**
	 * @param int $option
	 * @param int $arg1
	 * @param int $arg2
	 * @return bool
	 */
	public function stream_set_option($option, $arg1, $arg2);

	/**
	 * @param int $size
	 * @return bool
	 */
	public function stream_truncate($size);

	/**
	 * @return array|false
	 */
	public function stream_stat();

	/**
	 * @param int $operation
	 * @return bool
	 */
	public function stream_lock($operation);

	/**
	 * @return bool
	 */
	public function stream_flush();

	/**
	 * @return bool
	 */
	public function stream_eof();

	/**
	 * @return bool
	 */
	public function stream_close();
}
