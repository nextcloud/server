<?php
/**
 * SPDX-FileCopyrightText: 2014 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
 */

namespace Icewind\Streams;

/**
 * Interface for stream wrappers that implements a directory
 */
interface Directory {
	/**
	 * @param string $path
	 * @param array $options
	 * @return bool
	 */
	public function dir_opendir($path, $options);

	/**
	 * @return string|bool
	 */
	public function dir_readdir();

	/**
	 * @return bool
	 */
	public function dir_closedir();

	/**
	 * @return bool
	 */
	public function dir_rewinddir();
}
