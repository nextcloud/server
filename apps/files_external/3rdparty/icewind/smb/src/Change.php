<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 *
 */

namespace Icewind\SMB;

class Change {
	private $code;

	private $path;

	/**
	 * Change constructor.
	 *
	 * @param $code
	 * @param $path
	 */
	public function __construct($code, $path) {
		$this->code = $code;
		$this->path = $path;
	}

	/**
	 * @return integer
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
}
