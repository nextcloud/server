<?php
/**
 * SPDX-FileCopyrightText: 2014 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
 */

namespace Icewind\SMB\Exception;

class InvalidRequestException extends Exception {
	/**
	 * @var string
	 */
	protected $path;

	public function __construct(string $path = "", int $code = 0, ?\Throwable $previous = null) {
		$class = get_class($this);
		$parts = explode('\\', $class);
		$baseName = array_pop($parts);
		parent::__construct('Invalid request for ' . $path . ' (' . $baseName . ')', $code, $previous);
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
}
