<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
