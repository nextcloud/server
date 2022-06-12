<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\Utils;

class PathHelper {
	/**
	 * Make a path relative to a root path, or return null if the path is outside the root
	 *
	 * @param string $root
	 * @param string $path
	 * @return ?string
	 */
	public static function getRelativePath(string $root, string $path) {
		if ($root === '' or $root === '/') {
			return self::normalizePath($path);
		}
		if ($path === $root) {
			return '/';
		} elseif (strpos($path, $root . '/') !== 0) {
			return null;
		} else {
			$path = substr($path, strlen($root));
			return self::normalizePath($path);
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function normalizePath(string $path): string {
		if ($path === '' or $path === '/') {
			return '/';
		}
		//no windows style slashes
		$path = str_replace('\\', '/', $path);
		//add leading slash
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
		//remove duplicate slashes
		while (strpos($path, '//') !== false) {
			$path = str_replace('//', '/', $path);
		}
		//remove trailing slash
		$path = rtrim($path, '/');

		return $path;
	}
}
