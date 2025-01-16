<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		} elseif (!str_starts_with($path, $root . '/')) {
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
		// No null bytes
		$path = str_replace(chr(0), '', $path);
		//no windows style slashes
		$path = str_replace('\\', '/', $path);
		//add leading slash
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
		//remove duplicate slashes
		while (str_contains($path, '//')) {
			$path = str_replace('//', '/', $path);
		}
		//remove trailing slash
		$path = rtrim($path, '/');

		return $path;
	}
}
