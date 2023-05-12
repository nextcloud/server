<?php
/**
 * @copyright Copyright (c) 2021, Nextcloud GmbH.
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\Storage\Wrapper;

use Icewind\Streams\DirectoryWrapper;
use OC\Files\Filesystem;

/**
 * Normalize file names while reading directory entries
 */
class EncodingDirectoryWrapper extends DirectoryWrapper {
	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch Until return type is fixed upstream
	 * @return string|false
	 */
	public function dir_readdir() {
		$file = readdir($this->source);
		if ($file !== false && $file !== '.' && $file !== '..') {
			$file = trim(Filesystem::normalizePath($file), '/');
		}

		return $file;
	}

	/**
	 * @param resource $source
	 * @param callable $filter
	 * @return resource|false
	 */
	public static function wrap($source) {
		return self::wrapSource($source, [
			'source' => $source,
		]);
	}
}
