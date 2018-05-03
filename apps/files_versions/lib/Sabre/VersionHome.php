<?php
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

use OCP\Files\IRootFolder;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;

class VersionHome implements ICollection {

	/** @var array */
	private $principalInfo;

	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(array $principalInfo, IRootFolder $rootFolder) {
		$this->principalInfo = $principalInfo;
		$this->rootFolder = $rootFolder;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		list(,$name) = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function createFile($name, $data = null) {
		throw new Forbidden();
	}

	public function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name) {
		list(,$userId) = \Sabre\Uri\split($this->principalInfo['uri']);

		if ($name === 'versions') {
			return new VersionRoot($userId, $this->rootFolder);
		}
		if ($name === 'restore') {
			return new RestoreFolder($userId);
		}
	}

	public function getChildren() {
		list(,$userId) = \Sabre\Uri\split($this->principalInfo['uri']);

		return [
			new VersionRoot($userId, $this->rootFolder),
			new RestoreFolder($userId),
		];
	}

	public function childExists($name) {
		return $name === 'versions' || $name === 'restore';
	}

	public function getLastModified() {
		return 0;
	}
}
