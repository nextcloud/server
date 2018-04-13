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

namespace OCA\DAV\Direct;

use OCP\Files\File;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\IFile;

class DirectFile implements IFile {
	/** @var File */
	private $file;

	public function __construct(File $file) {
		$this->file = $file;
	}

	function put($data) {
		throw new Forbidden();
	}

	function get() {
		// TODO: Implement get() method.
	}

	function getContentType() {
		// TODO: Implement getContentType() method.
	}

	function getETag() {
		return $this->file->getEtag();
		// TODO: Implement getETag() method.
	}

	function getSize() {
		return $this->file->getSize();
		// TODO: Implement getSize() method.
	}

	function delete() {
		throw new Forbidden();
		// TODO: Implement delete() method.
	}

	function getName() {
		return $this->file->getName();
	}

	function setName($name) {
		throw new Forbidden();
	}

	function getLastModified() {
		return $this->file->getMTime();
		// TODO: Implement getLastModified() method.
	}

}
