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

class TrashRoot implements ICollection {

	/** @var string */
	private $userId;

	public function __construct(string $userId) {
		$this->userId = $userId;
	}

	public function delete() {
		\OCA\Files_Trashbin\Trashbin::deleteAll();
	}

	public function getName(): string {
		return 'trash';
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

	public function getChild($name): ITrash {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles('/', $this->userId);

		foreach ($entries as $entry) {
			if ($entry->getName() . '.d'.$entry->getMtime() === $name) {
				if ($entry->getType() === FileInfo::TYPE_FOLDER) {
					return new TrashFolder('/', $this->userId, $entry);
				}
				return new TrashFile($this->userId, $entry);
			}
		}

		throw new NotFound();
	}

	public function getChildren(): array {
			$entries = \OCA\Files_Trashbin\Helper::getTrashFiles('/', $this->userId);

		$children = array_map(function (FileInfo $entry) {
			if ($entry->getType() === FileInfo::TYPE_FOLDER) {
				return new TrashFolder('/', $this->userId, $entry);
			}
			return new TrashFile($this->userId, $entry);
		}, $entries);

		return $children;
	}

	public function childExists($name): bool {
		$entries = \OCA\Files_Trashbin\Helper::getTrashFiles('/', $this->userId);

		foreach ($entries as $entry) {
			if ($entry->getName() . '.d'.$entry->getMtime() === $name) {
				return true;
			}
		}

		return false;
	}

	public function getLastModified(): int {
		return 0;
	}
}
