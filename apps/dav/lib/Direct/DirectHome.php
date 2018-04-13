<?php
declare(strict_types=1);
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\DAV\Direct;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class DirectHome implements ICollection {

	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(IRootFolder $rootFolder) {
		$this->rootFolder = $rootFolder;
	}

	function createFile($name, $data = null) {
		throw new Forbidden();
	}

	function createDirectory($name) {
		throw new Forbidden();
	}

	function getChild($name) {
		throw new NotFound();
	}

	function getChildren() {
		$adminFolder = $this->rootFolder->getUserFolder('admin');

		$listing = $adminFolder->getDirectoryListing();

		$res = [];
		foreach ($listing as $file) {
			if ($file instanceof File) {
				$res[] = new DirectFile($file);
			}
		}

		return $res;
	}

	function childExists($name) {
		return false;
	}

	function delete() {
		throw new Forbidden();
	}

	function getName() {
		return 'direct';
	}

	function setName($name) {
		throw new Forbidden();
	}

	function getLastModified() {
		return 0;
	}

}
