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

use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\IRootFolder;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class DirectHome implements ICollection {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var DirectMapper */
	private $mapper;

	public function __construct(IRootFolder $rootFolder, DirectMapper $mapper) {
		$this->rootFolder = $rootFolder;
		$this->mapper = $mapper;
	}

	function createFile($name, $data = null) {
		throw new Forbidden();
	}

	function createDirectory($name) {
		throw new Forbidden();
	}

	public function getChild($name) {
		try {
			$direct = $this->mapper->getByToken($name);

			return new DirectFile($direct, $this->rootFolder);
		} catch (DoesNotExistException $e) {
			//TODO: throttle the ip to avoid brute forcing

			throw new NotFound();
		}
	}

	function getChildren() {
		throw new MethodNotAllowed('Listing members of this collection is disabled');
	}

	function childExists($name) {
		return false;
	}

	function delete() {
		throw new Forbidden();
	}

	function getName() {
		return 'direct';
	}

	function setName($name) {
		throw new Forbidden();
	}

	function getLastModified() {
		return 0;
	}

}
