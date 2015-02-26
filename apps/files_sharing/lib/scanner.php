<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2015 Vincent Petry <pvince81@owncloud.com>
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

/**
 * Scanner for SharedStorage
 */
class SharedScanner extends Scanner {

	/**
	 * Returns metadata from the shared storage, but
	 * with permissions from the source storage.
	 *
	 * @param string $path path of the file for which to retrieve metadata
	 *
	 * @return array an array of metadata of the file
	 */
	public function getData($path){
		$data = parent::getData($path);
		$sourcePath = $this->storage->getSourcePath($path);
		list($sourceStorage, $internalPath) = \OC\Files\Filesystem::resolvePath($sourcePath);
		$data['permissions'] = $sourceStorage->getPermissions($internalPath);
		return $data;
	}
}

