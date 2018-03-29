<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Exception;

class InvalidRequestException extends Exception {
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @param string $path
	 * @param int $code
	 */
	public function __construct($path, $code = 0) {
		$class = get_class($this);
		$parts = explode('\\', $class);
		$baseName = array_pop($parts);
		parent::__construct('Invalid request for ' . $path . ' (' . $baseName . ')', $code);
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
}
