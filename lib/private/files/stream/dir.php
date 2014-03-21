<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Stream;

class Dir {
	private static $dirs = array();
	private $name;
	private $index;

	public function dir_opendir($path, $options) {
		$this->name = substr($path, strlen('fakedir://'));
		$this->index = 0;
		if (!isset(self::$dirs[$this->name])) {
			self::$dirs[$this->name] = array();
		}
		return true;
	}

	public function dir_readdir() {
		if ($this->index >= count(self::$dirs[$this->name])) {
			return false;
		}
		$filename = self::$dirs[$this->name][$this->index];
		$this->index++;
		return $filename;
	}

	public function dir_closedir() {
		$this->name = '';
		return true;
	}

	public function dir_rewinddir() {
		$this->index = 0;
		return true;
	}

	/**
	 * @param string $path
	 */
	public static function register($path, $content) {
		self::$dirs[$path] = $content;
	}
}
