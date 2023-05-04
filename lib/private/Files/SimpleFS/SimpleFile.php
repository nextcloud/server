<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Files\SimpleFS;

use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

class SimpleFile implements ISimpleFile {
	private File $file;

	public function __construct(File $file) {
		$this->file = $file;
	}

	/**
	 * Get the name
	 */
	public function getName(): string {
		return $this->file->getName();
	}

	/**
	 * Get the size in bytes
	 */
	public function getSize(): int|float {
		return $this->file->getSize();
	}

	/**
	 * Get the ETag
	 */
	public function getETag(): string {
		return $this->file->getEtag();
	}

	/**
	 * Get the last modification time
	 */
	public function getMTime(): int {
		return $this->file->getMTime();
	}

	/**
	 * Get the content
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function getContent(): string {
		$result = $this->file->getContent();

		if ($result === false) {
			$this->checkFile();
		}

		return $result;
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
			$this->file->putContent($data);
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
		$this->file->delete();
	}

	/**
	 * Get the MimeType
	 */
	public function getMimeType(): string {
		return $this->file->getMimeType();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtension(): string {
		return $this->file->getExtension();
	}

	/**
	 * Open the file as stream for reading, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function read() {
		return $this->file->fopen('r');
	}

	/**
	 * Open the file as stream for writing, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function write() {
		return $this->file->fopen('w');
	}

	public function getId(): int {
		return $this->file->getId();
	}
}
