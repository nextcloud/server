<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Comments;

use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IProperties;
use Sabre\DAV\PropPatch;

/**
 * Class EntityCollection
 *
 * this represents a specific holder of comments, identified by an entity type
 * (class member $name) and an entity id (class member $id).
 *
 * @package OCA\DAV\Comments
 */
class EntityCollection extends RootCollection implements IProperties {
	public const PROPERTY_NAME_READ_MARKER = '{http://owncloud.org/ns}readMarker';

	/**
	 * @param string $id
	 * @param string $name
	 * @param ICommentsManager $commentsManager
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		protected $id,
		$name,
		ICommentsManager $commentsManager,
		IUserManager $userManager,
		IUserSession $userSession,
		protected LoggerInterface $logger,
	) {
		foreach (['id', 'name'] as $property) {
			$$property = trim($$property);
			if (empty($$property) || !is_string($$property)) {
				throw new \InvalidArgumentException('"' . $property . '" parameter must be non-empty string');
			}
		}
		$this->name = $name;
		$this->commentsManager = $commentsManager;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
	}

	/**
	 * returns the ID of this entity
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
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
		try {
			$comment = $this->commentsManager->get($name);
			if ($comment->getObjectType() !== $this->name
				|| $comment->getObjectId() !== $this->id) {
				throw new NotFound();
			}
			return new CommentNode(
				$this->commentsManager,
				$comment,
				$this->userManager,
				$this->userSession,
				$this->logger
			);
		} catch (NotFoundException $e) {
			throw new NotFound();
		}
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 */
	public function getChildren() {
		return $this->findChildren();
	}

	/**
	 * Returns an array of comment nodes. Result can be influenced by offset,
	 * limit and date time parameters.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param \DateTime|null $datetime
	 * @return CommentNode[]
	 */
	public function findChildren($limit = 0, $offset = 0, ?\DateTime $datetime = null) {
		$comments = $this->commentsManager->getForObject($this->name, $this->id, $limit, $offset, $datetime);
		$result = [];
		foreach ($comments as $comment) {
			$result[] = new CommentNode(
				$this->commentsManager,
				$comment,
				$this->userManager,
				$this->userSession,
				$this->logger
			);
		}
		return $result;
	}

	/**
	 * Checks if a child-node with the specified name exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function childExists($name) {
		try {
			$comment = $this->commentsManager->get($name);
			return $comment->getObjectType() === $this->name
				&& $comment->getObjectId() === $this->id;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * Sets the read marker to the specified date for the logged in user
	 */
	public function setReadMarker(?string $value): bool {
		$dateTime = new \DateTime($value ?? 'now');
		$user = $this->userSession->getUser();
		$this->commentsManager->setReadMark($this->name, $this->id, $dateTime, $user);
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function propPatch(PropPatch $propPatch) {
		$propPatch->handle(self::PROPERTY_NAME_READ_MARKER, [$this, 'setReadMarker']);
	}

	/**
	 * @inheritdoc
	 */
	public function getProperties($properties) {
		$marker = null;
		$user = $this->userSession->getUser();
		if (!is_null($user)) {
			$marker = $this->commentsManager->getReadMark($this->name, $this->id, $user);
		}
		return [self::PROPERTY_NAME_READ_MARKER => $marker];
	}
}
