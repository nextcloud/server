<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OC\IntegrityCheck\Helpers;

/**
 * Class FileAccessHelper provides a helper around file_get_contents and
 * file_put_contents
 *
 * @package OC\IntegrityCheck\Helpers
 */
class FileAccessHelper {
	/**
	 * Wrapper around file_get_contents($filename, $data)
	 *
	 * @param string $filename
	 * @return string|false
	 */
	public function file_get_contents($filename) {
		return file_get_contents($filename);
	}

	/**
	 * Wrapper around file_exists($filename)
	 *
	 * @param string $filename
	 * @return bool
	 */
	public function file_exists($filename) {
		return file_exists($filename);
	}

	/**
	 * Wrapper around file_put_contents($filename, $data)
	 *
	 * @param string $filename
	 * @param $data
	 * @return int|false
	 */
	public function file_put_contents($filename, $data) {
		return file_put_contents($filename, $data);
	}
}
