<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Comments;

use OCP\Comments\CommentsEntityEvent;
use OCP\Comments\ICommentsManager;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RootCollection implements ICollection {

	/** @var EntityTypeCollection[]|null */
	private $entityTypeCollections;

	/** @var ICommentsManager */
	protected $commentsManager;

	/** @var string */
	protected $name = 'comments';

	protected LoggerInterface $logger;

	/** @var IUserManager */
	protected $userManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var EventDispatcherInterface */
	protected $dispatcher;

	public function __construct(
		ICommentsManager $commentsManager,
		IUserManager $userManager,
		IUserSession $userSession,
		EventDispatcherInterface $dispatcher,
		LoggerInterface $logger) {
		$this->commentsManager = $commentsManager;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->dispatcher = $dispatcher;
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

		$event = new CommentsEntityEvent(CommentsEntityEvent::EVENT_ENTITY);
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
	 * @return int
	 */
	public function getLastModified() {
		return null;
	}
}
