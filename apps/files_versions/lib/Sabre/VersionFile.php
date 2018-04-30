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
namespace OCA\Files_Versions\Sabre;

use OCA\Files_Versions\Storage;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IFile;

class VersionFile implements IFile {
	/** @var array */
	private $data;

	/** @var Folder */
	private $userRoot;

	public function __construct(array $data, Folder $userRoot) {
		$this->data = $data;
		$this->userRoot = $userRoot;
	}

	public function put($data) {
		throw new Forbidden();
	}

	public function get() {
		try {
			/** @var Folder $versions */
			$versions = $this->userRoot->get('files_versions');
			/** @var File $version */
			$version = $versions->get($this->data['path'].'.v'.$this->data['version']);
		} catch (NotFoundException $e) {
			throw new NotFound();
		}

		return $version->fopen('rb');
	}

	public function getContentType(): string {
		return $this->data['mimetype'];
	}

	public function getETag(): string {
		return $this->data['version'];
	}

	public function getSize(): int {
		return $this->data['size'];
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		return $this->data['version'];
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified(): int {
		return (int)$this->data['version'];
	}

	public function rollBack(): bool {
		return Storage::rollback($this->data['path'], $this->data['version']);
	}
}
