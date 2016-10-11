<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
	 * @param string[] $content
	 */
	public static function register($path, $content) {
		self::$dirs[$path] = $content;
	}
}
