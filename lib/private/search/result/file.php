<?php
/**
 * ownCloud
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
 *
 */

namespace OC\Search\Result;
use \OC\Files\Filesystem;
/**
 * A found file
 */
class File extends \OC\Search\Result {

	/**
	 * Type name; translated in templates
	 * @var string 
	 */
	public $type = 'file';

	/**
	 * Path to file
	 * @var string
	 */
	public $path;

	/**
	 * Size, in bytes
	 * @var int 
	 */
	public $size;

	/**
	 * Date modified, in human readable form
	 * @var string
	 */
	public $modified;

	/**
	 * File mime type
	 * @var string
	 */
	public $mime_type;

	/**
	 * File permissions:
	 * 
	 * @var string
	 */
	public $permissions;

	/**
	 * Create a new file search result
	 * @param array $data file data given by provider
	 */
	public function __construct(array $data = null) {
		$info = pathinfo($data['path']);
		$this->id = $data['fileid'];
		$this->name = $info['basename'];
		$this->link = \OCP\Util::linkTo(
			'files',
			'index.php',
			array('dir' => $info['dirname'], 'file' => $info['basename'])
		);
		$this->permissions = self::get_permissions($data['path']);
		$this->path = (strpos($data['path'], 'files') === 0) ? substr($data['path'], 5) : $data['path'];
		$this->size = $data['size'];
		$this->modified = $data['mtime'];
		$this->mime_type = $data['mimetype'];
	}

	/**
	 * Determine permissions for a given file path
	 * @param string $path
	 * @return int
	 */
	function get_permissions($path) {
		// add read permissions
		$permissions = \OCP\PERMISSION_READ;
		// get directory
		$fileinfo = pathinfo($path);
		$dir = $fileinfo['dirname'] . '/';
		// add update permissions
		if (Filesystem::isUpdatable($dir)) {
			$permissions |= \OCP\PERMISSION_UPDATE;
		}
		// add delete permissions
		if (Filesystem::isDeletable($dir)) {
			$permissions |= \OCP\PERMISSION_DELETE;
		}
		// add share permissions
		if (Filesystem::isSharable($dir)) {
			$permissions |= \OCP\PERMISSION_SHARE;
		}
		// return
		return $permissions;
	}
	
}
