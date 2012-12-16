<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace OC\Files\Cache;

class Shared_Scanner extends Scanner {

	public function __construct(\OC\Files\Storage\Storage $storage) {
	
	}

	/**
	 * get all the metadata of a file or folder
	 * *
	 *
	 * @param string $path
	 * @return array with metadata of the file
	 */
	public function getData($path) {
		// Not a valid action for Shared Scanner
	}

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @return array with metadata of the scanned file
	 */
	public function scanFile($file) {
		// Not a valid action for Shared Scanner
	}

	/**
	 * scan all the files in a folder and store them in the cache
	 *
	 * @param string $path
	 * @param SCAN_RECURSIVE/SCAN_SHALLOW $recursive
	 * @return int the size of the scanned folder or -1 if the size is unknown at this stage
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE) {
		// Not a valid action for Shared Scanner
	}

	/**
	 * walk over any folders that are not fully scanned yet and scan them
	 */
	public function backgroundScan() {
		// Not a valid action for Shared Scanner
	}

}