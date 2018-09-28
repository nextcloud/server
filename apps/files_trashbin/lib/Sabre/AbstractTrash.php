<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

abstract class AbstractTrash implements ITrash {
	/** @var FileInfo */
	protected $data;

	public function __construct(FileInfo $data) {
		$this->data = $data;
	}

	public function getFilename(): string {
		return $this->data->getName();
	}

	public function getDeletionTime(): int {
		return $this->data->getMtime();
	}

	public function getFileId(): int {
		return $this->data->getId();
	}

	public function getFileInfo(): FileInfo {
		return $this->data;
	}

	public function getSize(): int {
		return $this->data->getSize();
	}

	public function getLastModified(): int {
		return $this->data->getMtime();
	}

	public function getContentType(): string {
		return $this->data->getMimetype();
	}

	public function getETag(): string {
		return $this->data->getEtag();
	}

	public function getName(): string {
		return $this->data->getName();
	}
}
