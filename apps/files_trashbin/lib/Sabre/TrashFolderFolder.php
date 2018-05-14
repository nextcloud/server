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
namespace OCA\Files_Trashbin\Sabre;

use OCP\Files\FileInfo;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class TrashFolderFolder implements ICollection, ITrash {

	/** @var string */
	private $root;

	/** @var string */
	private $userId;

	/** @var FileInfo */
	private $data;

	/** @var string */
	private $location;

	public function __construct(string $root,
								string $userId,
								FileInfo $data,
								string $location) {
		$this->root = $root;
		$this->userId = $userId;
		$this->data = $data;
		$this->location = $location;
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name): ITrash {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles($this->root . '/' . $this->getName(), $this->userId);

		foreach ($entries as $entry) {
			if ($entry->getName() === $name) {
				if ($entry->getType() === FileInfo::TYPE_FOLDER) {
					return new TrashFolderFolder($this->root . '/' . $this->getName(), $this->userId, $entry, $this->getOriginalLocation());
				}
				return new TrashFolderFile($this->root . '/' . $this->getName(), $this->userId, $entry, $this->getOriginalLocation());
			}
		}

		throw new NotFound();
	}

	public function getChildren(): array {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles($this->root . '/' . $this->getName(), $this->userId);

		$children = array_map(function (FileInfo $entry) {
			if ($entry->getType() === FileInfo::TYPE_FOLDER) {
				return new TrashFolderFolder($this->root.'/'.$this->getName(), $this->userId, $entry, $this->getOriginalLocation());
			}
			return new TrashFolderFile($this->root.'/'.$this->getName(), $this->userId, $entry, $this->getOriginalLocation());
		}, $entries);

		return $children;
	}

	public function childExists($name): bool {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles($this->root . '/' . $this->getName(), $this->userId);

		foreach ($entries as $entry) {
			if ($entry->getName() === $name) {
				return true;
			}
		}

		return false;
	}

	public function delete() {
		\OCA\Files_Trashbin\Trashbin::delete($this->root . '/' . $this->getName(), $this->userId, null);
	}

	public function getName(): string {
		return $this->data->getName();

	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return $this->data->getMtime();
	}

	public function restore(): bool {
		return \OCA\Files_Trashbin\Trashbin::restore($this->root . '/' . $this->getName(), $this->data->getName(), null);
	}

	public function getFilename(): string {
		return $this->data->getName();
	}

	public function getOriginalLocation(): string {
		return $this->location . '/' . $this->getFilename();
	}

	public function getDeletionTime(): int {
		return $this->getLastModified();
	}

	public function getSize(): int {
		return $this->data->getSize();
	}

	public function getFileId(): int {
		return $this->data->getId();
	}
}
