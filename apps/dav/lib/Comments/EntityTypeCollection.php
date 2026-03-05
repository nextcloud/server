<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Comments;

use OCP\Comments\ICommentsManager;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;

/**
 * Class EntityTypeCollection
 *
 * This is collection on the type of things a user can leave comments on, for
 * example: 'files'.
 *
 * Its children are instances of EntityCollection (representing a specific
 * object, for example the file by id).
 *
 * @package OCA\DAV\Comments
 */
class EntityTypeCollection extends RootCollection {
	public function __construct(
		string $name,
		ICommentsManager $commentsManager,
		protected IUserManager $userManager,
		IUserSession $userSession,
		protected LoggerInterface $logger,
		protected \Closure $childExistsFunction,
	) {
		$name = trim($name);
		if (empty($name)) {
			throw new \InvalidArgumentException('"name" parameter must be non-empty string');
		}
		$this->name = $name;
		$this->commentsManager = $commentsManager;
		$this->userSession = $userSession;
	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * This method must throw Sabre\DAV\Exception\NotFound if the node does not
	 * exist.
	 *
	 * @param string $name
	 * @return \Sabre\DAV\INode
	 * @throws NotFound
	 */
	public function getChild($name) {
		if (!$this->childExists($name)) {
			throw new NotFound('Entity does not exist or is not available');
		}
		return new EntityCollection(
			$name,
			$this->name,
			$this->commentsManager,
			$this->userManager,
			$this->userSession,
			$this->logger
		);
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 * @throws MethodNotAllowed
	 */
	public function getChildren() {
		throw new MethodNotAllowed('No permission to list folder contents');
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
}
