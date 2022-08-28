<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\SystemTag;

use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

/**
 * Collection containing object ids by object type
 */
class SystemTagsObjectTypeCollection implements ICollection {
	public function __construct(
		private string $objectType,
		private ISystemTagManager $tagManager,
		private ISystemTagObjectMapper $tagMapper,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		protected \Closure $childExistsFunction,
		protected \Closure $childWriteAccessFunction,
	) {
	}

	/**
	 * @param string $name
	 * @param resource|string $data Initial payload
	 *
	 * @return never
	 *
	 * @throws Forbidden
	 */
	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create nodes');
	}

	/**
	 * @param string $name
	 *
	 * @throws Forbidden
	 *
	 * @return never
	 */
	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
	}

	/**
	 * @param string $objectName
	 *
	 * @return SystemTagsObjectMappingCollection
	 * @throws NotFound
	 */
	public function getChild($objectName) {
		// make sure the object exists and is reachable
		if (!$this->childExists($objectName)) {
			throw new NotFound('Entity does not exist or is not available');
		}
		return new SystemTagsObjectMappingCollection(
			$objectName,
			$this->objectType,
			$this->userSession->getUser(),
			$this->tagManager,
			$this->tagMapper,
			$this->childWriteAccessFunction,
		);
	}

	/**
	 * @return never
	 */
	public function getChildren() {
		// do not list object ids
		throw new MethodNotAllowed();
	}

	/**
	 * Checks if a child-node with the specified name exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {
		return call_user_func($this->childExistsFunction, $name);
	}

	/**
	 * @return never
	 */
	public function delete() {
		throw new Forbidden('Permission denied to delete this collection');
	}

	public function getName() {
		return $this->objectType;
	}

	/**
	 * @param string $name
	 *
	 * @throws Forbidden
	 *
	 * @return never
	 */
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return null
	 */
	public function getLastModified() {
		return null;
	}
}
