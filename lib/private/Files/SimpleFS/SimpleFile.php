<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Files\SimpleFS;

use OCP\Files\File;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

class SimpleFile implements ISimpleFile  {

	/** @var File $file */
	private $file;

	/**
	 * File constructor.
	 *
	 * @param File $file
	 */
	public function __construct(File $file) {
		$this->file = $file;
	}

	/**
	 * Get the name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->file->getName();
	}

	/**
	 * Get the size in bytes
	 *
	 * @return int
	 */
	public function getSize() {
		return $this->file->getSize();
	}

	/**
	 * Get the ETag
	 *
	 * @return string
	 */
	public function getETag() {
		return $this->file->getEtag();
	}

	/**
	 * Get the last modification time
	 *
	 * @return int
	 */
	public function getMTime() {
		return $this->file->getMTime();
	}

	/**
	 * Get the content
	 *
	 * @return string
	 */
	public function getContent() {
		return $this->file->getContent();
	}

	/**
	 * Overwrite the file
	 *
	 * @param string $data
	 * @throws NotPermittedException
	 */
	public function putContent($data) {
		$this->file->putContent($data);
	}

	/**
	 * Delete the file
	 *
	 * @throws NotPermittedException
	 */
	public function delete() {
		$this->file->delete();
	}

	/**
	 * Get the MimeType
	 *
	 * @return string
	 */
	public function getMimeType() {
		return $this->file->getMimeType();
	}
}
