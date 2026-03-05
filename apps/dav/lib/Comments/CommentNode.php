<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Comments;

use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\MessageTooLongException;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\PropPatch;

class CommentNode implements \Sabre\DAV\INode, \Sabre\DAV\IProperties {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	public const PROPERTY_NAME_UNREAD = '{http://owncloud.org/ns}isUnread';
	public const PROPERTY_NAME_MESSAGE = '{http://owncloud.org/ns}message';
	public const PROPERTY_NAME_ACTOR_DISPLAYNAME = '{http://owncloud.org/ns}actorDisplayName';
	public const PROPERTY_NAME_MENTIONS = '{http://owncloud.org/ns}mentions';
	public const PROPERTY_NAME_MENTION = '{http://owncloud.org/ns}mention';
	public const PROPERTY_NAME_MENTION_TYPE = '{http://owncloud.org/ns}mentionType';
	public const PROPERTY_NAME_MENTION_ID = '{http://owncloud.org/ns}mentionId';
	public const PROPERTY_NAME_MENTION_DISPLAYNAME = '{http://owncloud.org/ns}mentionDisplayName';

	/** @var array list of properties with key being their name and value their setter */
	protected $properties = [];

	/**
	 * CommentNode constructor.
	 */
	public function __construct(
		protected ICommentsManager $commentsManager,
		public IComment $comment,
		protected IUserManager $userManager,
		protected IUserSession $userSession,
		protected LoggerInterface $logger,
	) {
		$methods = get_class_methods($this->comment);
		$methods = array_filter($methods, function ($name) {
			return str_starts_with($name, 'get');
		});
		foreach ($methods as $getter) {
			if ($getter === 'getMentions') {
				continue;	// special treatment
			}
			$name = '{' . self::NS_OWNCLOUD . '}' . lcfirst(substr($getter, 3));
			$this->properties[$name] = $getter;
		}
	}

	/**
	 * returns a list of all possible property names
	 *
	 * @return array
	 */
	public static function getPropertyNames() {
		return [
			'{http://owncloud.org/ns}id',
			'{http://owncloud.org/ns}parentId',
			'{http://owncloud.org/ns}topmostParentId',
			'{http://owncloud.org/ns}childrenCount',
			'{http://owncloud.org/ns}verb',
			'{http://owncloud.org/ns}actorType',
			'{http://owncloud.org/ns}actorId',
			'{http://owncloud.org/ns}creationDateTime',
			'{http://owncloud.org/ns}latestChildDateTime',
			'{http://owncloud.org/ns}objectType',
			'{http://owncloud.org/ns}objectId',
			// re-used property names are defined as constants
			self::PROPERTY_NAME_MESSAGE,
			self::PROPERTY_NAME_ACTOR_DISPLAYNAME,
			self::PROPERTY_NAME_UNREAD,
			self::PROPERTY_NAME_MENTIONS,
			self::PROPERTY_NAME_MENTION,
			self::PROPERTY_NAME_MENTION_TYPE,
			self::PROPERTY_NAME_MENTION_ID,
			self::PROPERTY_NAME_MENTION_DISPLAYNAME,
		];
	}

	protected function checkWriteAccessOnComment() {
		$user = $this->userSession->getUser();
		if ($this->comment->getActorType() !== 'users'
			|| is_null($user)
			|| $this->comment->getActorId() !== $user->getUID()
		) {
			throw new Forbidden('Only authors are allowed to edit their comment.');
		}
	}

	/**
	 * Deleted the current node
	 *
	 * @return void
	 */
	public function delete() {
		$this->checkWriteAccessOnComment();
		$this->commentsManager->delete($this->comment->getId());
	}

	/**
	 * Returns the name of the node.
	 *
	 * This is used to generate the url.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->comment->getId();
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @throws MethodNotAllowed
	 */
	public function setName($name) {
		throw new MethodNotAllowed();
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 */
	public function getLastModified(): ?int {
		return null;
	}

	/**
	 * update the comment's message
	 *
	 * @param $propertyValue
	 * @return bool
	 * @throws BadRequest
	 * @throws \Exception
	 */
	public function updateComment($propertyValue) {
		$this->checkWriteAccessOnComment();
		try {
			$this->comment->setMessage($propertyValue);
			$this->commentsManager->save($this->comment);
			return true;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['app' => 'dav/comments', 'exception' => $e]);
			if ($e instanceof MessageTooLongException) {
				$msg = 'Message exceeds allowed character limit of ';
				throw new BadRequest($msg . IComment::MAX_MESSAGE_LENGTH, 0, $e);
			}
			throw $e;
		}
	}

	/**
	 * Updates properties on this node.
	 *
	 * This method received a PropPatch object, which contains all the
	 * information about the update.
	 *
	 * To update specific properties, call the 'handle' method on this object.
	 * Read the PropPatch documentation for more information.
	 *
	 * @param PropPatch $propPatch
	 * @return void
	 */
	public function propPatch(PropPatch $propPatch) {
		// other properties than 'message' are read only
		$propPatch->handle(self::PROPERTY_NAME_MESSAGE, [$this, 'updateComment']);
	}

	/**
	 * Returns a list of properties for this nodes.
	 *
	 * The properties list is a list of propertynames the client requested,
	 * encoded in clark-notation {xmlnamespace}tagname
	 *
	 * If the array is empty, it means 'all properties' were requested.
	 *
	 * Note that it's fine to liberally give properties back, instead of
	 * conforming to the list of requested properties.
	 * The Server class will filter out the extra.
	 *
	 * @param array $properties
	 * @return array
	 */
	public function getProperties($properties) {
		$properties = array_keys($this->properties);

		$result = [];
		foreach ($properties as $property) {
			$getter = $this->properties[$property];
			if (method_exists($this->comment, $getter)) {
				$result[$property] = $this->comment->$getter();
			}
		}

		if ($this->comment->getActorType() === 'users') {
			$user = $this->userManager->get($this->comment->getActorId());
			$displayName = is_null($user) ? null : $user->getDisplayName();
			$result[self::PROPERTY_NAME_ACTOR_DISPLAYNAME] = $displayName;
		}

		$result[self::PROPERTY_NAME_MENTIONS] = $this->composeMentionsPropertyValue();

		$unread = null;
		$user = $this->userSession->getUser();
		if (!is_null($user)) {
			$readUntil = $this->commentsManager->getReadMark(
				$this->comment->getObjectType(),
				$this->comment->getObjectId(),
				$user
			);
			if (is_null($readUntil)) {
				$unread = 'true';
			} else {
				$unread = $this->comment->getCreationDateTime() > $readUntil;
				// re-format for output
				$unread = $unread ? 'true' : 'false';
			}
		}
		$result[self::PROPERTY_NAME_UNREAD] = $unread;

		return $result;
	}

	/**
	 * transforms a mentions array as returned from IComment->getMentions to an
	 * array with DAV-compatible structure that can be assigned to the
	 * PROPERTY_NAME_MENTION property.
	 *
	 * @return array
	 */
	protected function composeMentionsPropertyValue() {
		return array_map(function ($mention) {
			try {
				$displayName = $this->commentsManager->resolveDisplayName($mention['type'], $mention['id']);
			} catch (\OutOfBoundsException $e) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
				// No displayname, upon client's discretion what to display.
				$displayName = '';
			}

			return [
				self::PROPERTY_NAME_MENTION => [
					self::PROPERTY_NAME_MENTION_TYPE => $mention['type'],
					self::PROPERTY_NAME_MENTION_ID => $mention['id'],
					self::PROPERTY_NAME_MENTION_DISPLAYNAME => $displayName,
				]
			];
		}, $this->comment->getMentions());
	}
}
