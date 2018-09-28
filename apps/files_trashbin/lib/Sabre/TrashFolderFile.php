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
use Sabre\DAV\IFile;

class TrashFolderFile extends AbstractTrash implements IFile, ITrash {
	/** @var string */
	private $root;

	/** @var string */
	private $userId;

	/** @var string */
	private $location;

	public function __construct(string $root,
								string $userId,
								FileInfo $data,
								string $location) {
		$this->root = $root;
		$this->userId = $userId;
		$this->location = $location;
		parent::__construct($data);
	}

	public function put($data) {
		throw new Forbidden();
	}

	public function get() {
		return $this->data->getStorage()->fopen($this->data->getInternalPath(), 'rb');
	}

	public function delete() {
		\OCA\Files_Trashbin\Trashbin::delete($this->root . '/' . $this->getName(), $this->userId, null);
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function restore(): bool {
		return \OCA\Files_Trashbin\Trashbin::restore($this->root . '/' . $this->getName(), $this->data->getName(), null);
	}

	public function getOriginalLocation(): string {
		return $this->location . '/' . $this->getFilename();
	}
}
