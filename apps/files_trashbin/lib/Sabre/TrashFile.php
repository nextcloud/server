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

class TrashFile implements IFile, ITrash {
	/** @var string */
	private $userId;

	/** @var FileInfo */
	private $data;

	public function __construct(string $userId, FileInfo $data) {
		$this->userId = $userId;
		$this->data = $data;
	}

	public function put($data) {
		throw new Forbidden();
	}

	public function get() {
		return $this->data->getStorage()->fopen($this->data->getInternalPath().'.d'.$this->getLastModified(), 'rb');
	}

	public function getContentType(): string {
		return $this->data->getMimetype();
	}

	public function getETag(): string	 {
		return $this->data->getEtag();
	}

	public function getSize(): int {
		return $this->data->getSize();
	}

	public function delete() {
		\OCA\Files_Trashbin\Trashbin::delete($this->data->getName(), $this->userId, $this->getLastModified());
	}

	public function getName(): string {
		return $this->data->getName() . '.d' . $this->getLastModified();
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return $this->data->getMtime();
	}

	public function restore(): bool {
		return \OCA\Files_Trashbin\Trashbin::restore($this->getName(), $this->data->getName(), $this->getLastModified());
	}

	public function getFilename(): string {
		return $this->data->getName();
	}

	public function getOriginalLocation(): string {
		return $this->data['extraData'];
	}

	public function getDeletionTime(): int {
		return $this->getLastModified();
	}

	public function getFileId(): int {
		return $this->data->getId();
	}


}
