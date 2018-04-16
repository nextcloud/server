<?php
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
namespace OCA\Files_Trashbin\Sabre;

use OCP\Files\FileInfo;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class TrashHome implements ICollection {

	/**
	 * @var array
	 */
	private $principalInfo;

	/**
	 * FilesHome constructor.
	 *
	 * @param array $principalInfo
	 */
	public function __construct($principalInfo) {
		$this->principalInfo = $principalInfo;
	}

	public function delete() {
		throw new Forbidden('Permission denied to delete your trashbin');
	}

	public function getName(): string {
		list(,$name) = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this trashbin');
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Not allowed to create files in the trashbin');
	}

	public function createDirectory($name) {
		throw new Forbidden('Not allowed to create folders in the trashbin');
	}

	public function getChild($name) {
		list(,$userId) = \Sabre\Uri\split($this->principalInfo['uri']);

		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles('/', $userId);

		foreach ($entries as $entry) {
			if ($entry->getName() . '.d'.$entry->getMtime() === $name) {
				if ($entry->getMimetype() === 'httpd/unix-directory') {
					return new TrashFolder('/', $userId, $entry);
				}
				return new TrashFile($userId, $entry);
			}
		}

		throw new NotFound();
	}

	public function getChildren() {
		list(,$userId) = \Sabre\Uri\split($this->principalInfo['uri']);

		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles('/', $userId);

		$children = array_map(function (FileInfo $entry) use ($userId) {
			if ($entry->getMimetype() === 'httpd/unix-directory') {
				return new TrashFolder('/', $userId, $entry);
			}
			return new TrashFile($userId, $entry);
		}, $entries);

		return $children;
	}

	public function childExists($name) {
		list(,$userId) = \Sabre\Uri\split($this->principalInfo['uri']);

		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles('/', $userId);

		foreach ($entries as $entry) {
			if ($entry->getName() . '.d'.$entry->getMtime() === $name) {
				return true;
			}
		}

		return false;
	}

	public function getLastModified() {
		return 0;
	}
}
