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
use OC\Files\Filesystem;
use OCP\Files\FileInfo;

/**
 * A found file
 */
class File extends \OCP\Search\Result {

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
	 * @param FileInfo $data file data given by provider
	 */
	public function __construct(FileInfo $data) {

		$path = $this->getRelativePath($data->getPath());

		$info = pathinfo($path);
		$this->id = $data->getId();
		$this->name = $info['basename'];
		$this->link = \OCP\Util::linkTo(
			'files',
			'index.php',
			array('dir' => $info['dirname'], 'scrollto' => $info['basename'])
		);
		$this->permissions = $data->getPermissions();
		$this->path = $path;
		$this->size = $data->getSize();
		$this->modified = $data->getMtime();
		$this->mime = $data->getMimetype();
	}

	/**
	 * converts a path relative to the users files folder
	 * eg /user/files/foo.txt -> /foo.txt
	 * @param string $path
	 * @return string relative path
	 */
	protected function getRelativePath ($path) {
		$root = \OC::$server->getUserFolder();
		return $root->getRelativePath($path);
	}

}
