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
use Sabre\DAV\ICollection;

class TrashFolder implements ICollection, ITrash {
	/** @var string */
	private $userId;

	/** @var FileInfo */
	private $data;

	public function __construct(string $root, string $userId, FileInfo $data) {
		$this->userId = $userId;
		$this->data = $data;
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name) {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles($this->getName(), $this->userId);

		foreach ($entries as $entry) {
			if ($entry->getName() === $name) {
				if ($entry->getMimetype() === 'httpd/unix-directory') {
					return new TrashFolderFolder($this->getName(), $this->userId, $entry);
				}
				return new TrashFolderFile($this->getName(), $this->userId, $entry);
			}
		}
	}

	public function getChildren() {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles($this->getName(), $this->userId);

		$children = array_map(function (FileInfo $entry) {
			if ($entry->getMimetype() === 'httpd/unix-directory') {
				return new TrashFolderFolder($this->getName(), $this->userId, $entry);
			}
			return new TrashFolderFile($this->getName(), $this->userId, $entry);
		}, $entries);

		return $children;
	}

	public function childExists($name) {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles($this->getName(), $this->userId);

		foreach ($entries as $entry) {
			if ($entry->getName() === $name) {
				return true;
			}
		}

		return false;
	}

	public function delete() {
		\OCA\Files_Trashbin\Trashbin::delete($this->data->getName(), $this->userId, $this->getLastModified());
	}

	public function getName() {
		return $this->data->getName() . '.d' . $this->getLastModified();
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified() {
		return $this->data->getMtime();
	}

	public function restore(): bool {
		return \OCA\Files_Trashbin\Trashbin::restore($this->getName(), $this->data->getName(), $this->getLastModified());
	}


}
