<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\SimpleFS;

use Icewind\Streams\CallbackWrapper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

class NewSimpleFile implements ISimpleFile {
	private Folder $parentFolder;
	private string $name;
	private ?File $file = null;

	/**
	 * File constructor.
	 */
	public function __construct(Folder $parentFolder, string $name) {
		$this->parentFolder = $parentFolder;
		$this->name = $name;
	}

	/**
	 * Get the name
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the size in bytes
	 */
	public function getSize(): int|float {
		if ($this->file) {
			return $this->file->getSize();
		} else {
			return 0;
		}
	}

	/**
	 * Get the ETag
	 */
	public function getETag(): string {
		if ($this->file) {
			return $this->file->getEtag();
		} else {
			return '';
		}
	}

	/**
	 * Get the last modification time
	 */
	public function getMTime(): int {
		if ($this->file) {
			return $this->file->getMTime();
		} else {
			return time();
		}
	}

	/**
	 * Get the content
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getContent(): string {
		if ($this->file) {
			$result = $this->file->getContent();

			if ($result === false) {
				$this->checkFile();
			}

			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Overwrite the file
	 *
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function putContent($data): void {
		try {
			if ($this->file) {
				$this->file->putContent($data);
			} else {
				$this->file = $this->parentFolder->newFile($this->name, $data);
			}
		} catch (NotFoundException $e) {
			$this->checkFile();
		}
	}

	/**
	 * Sometimes there are some issues with the AppData. Most of them are from
	 * user error. But we should handle them gracefully anyway.
	 *
	 * If for some reason the current file can't be found. We remove it.
	 * Then traverse up and check all folders if they exists. This so that the
	 * next request will have a valid appdata structure again.
	 *
	 * @throws NotFoundException
	 */
	private function checkFile(): void {
		if (!$this->file) {
			throw new NotFoundException('File not set');
		}

		$cur = $this->file;

		while ($cur->stat() === false) {
			$parent = $cur->getParent();
			try {
				$cur->delete();
			} catch (NotFoundException $e) {
				// Just continue then
			}
			$cur = $parent;
		}

		if ($cur !== $this->file) {
			throw new NotFoundException('File does not exist');
		}
	}


	/**
	 * Delete the file
	 *
	 * @throws NotPermittedException
	 */
	public function delete(): void {
		if ($this->file) {
			$this->file->delete();
		}
	}

	/**
	 * Get the MimeType
	 *
	 * @return string
	 */
	public function getMimeType(): string {
		if ($this->file) {
			return $this->file->getMimeType();
		} else {
			return 'text/plain';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtension(): string {
		if ($this->file) {
			return $this->file->getExtension();
		} else {
			return \pathinfo($this->name, PATHINFO_EXTENSION);
		}
	}

	/**
	 * Open the file as stream for reading, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function read() {
		if ($this->file) {
			return $this->file->fopen('r');
		} else {
			return fopen('php://temp', 'r');
		}
	}

	/**
	 * Open the file as stream for writing, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|bool
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function write() {
		if ($this->file) {
			return $this->file->fopen('w');
		} else {
			$source = fopen('php://temp', 'w+');
			return CallbackWrapper::wrap($source, null, null, null, null, function () use ($source) {
				rewind($source);
				$this->putContent($source);
			});
		}
	}
}
