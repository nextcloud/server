<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Comments;

use OCP\Comments\CommentsEntityEvent;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class RootCollection implements ICollection {
	/** @var EntityTypeCollection[]|null */
	private ?array $entityTypeCollections = null;
	protected string $name = 'comments';

	public function __construct(
		protected ICommentsManager $commentsManager,
		protected IUserManager $userManager,
		protected IUserSession $userSession,
		protected IEventDispatcher $dispatcher,
		protected LoggerInterface $logger,
	) {
	}

	/**
	 * initializes the collection. At this point of time, we need the logged in
	 * user. Since it is not the case when the instance is created, we cannot
	 * have this in the constructor.
	 *
	 * @throws NotAuthenticated
	 */
	protected function initCollections() {
		if ($this->entityTypeCollections !== null) {
			return;
		}
		$user = $this->userSession->getUser();
		if (is_null($user)) {
			throw new NotAuthenticated();
		}

		$event = new CommentsEntityEvent();
		$this->dispatcher->dispatchTyped($event);
		$this->dispatcher->dispatch(CommentsEntityEvent::EVENT_ENTITY, $event);

		$this->entityTypeCollections = [];
		foreach ($event->getEntityCollections() as $entity => $entityExistsFunction) {
			$this->entityTypeCollections[$entity] = new EntityTypeCollection(
				$entity,
				$this->commentsManager,
				$this->userManager,
				$this->userSession,
				$this->logger,
				$entityExistsFunction
			);
		}
	}

	/**
	 * Creates a new file in the directory
	 *
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 * @return null|string
	 * @throws Forbidden
	 */
	public function createFile($name, $data = null) {
		throw new Forbidden('Cannot create comments by id');
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @throws Forbidden
	 */
	public function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
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
		$this->initCollections();
		if (isset($this->entityTypeCollections[$name])) {
			return $this->entityTypeCollections[$name];
		}
		throw new NotFound('Entity type "' . $name . '" not found."');
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 */
	public function getChildren() {
		$this->initCollections();
		assert(!is_null($this->entityTypeCollections));
		return $this->entityTypeCollections;
	}

	/**
	 * Checks if a child-node with the specified name exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {
		$this->initCollections();
		assert(!is_null($this->entityTypeCollections));
		return isset($this->entityTypeCollections[$name]);
	}

	/**
	 * Deleted the current node
	 *
	 * @throws Forbidden
	 */
	public function delete() {
		throw new Forbidden('Permission denied to delete this collection');
	}

	/**
	 * Returns the name of the node.
	 *
	 * This is used to generate the url.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @throws Forbidden
	 */
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return ?int
	 */
	public function getLastModified() {
		return null;
	}
}
