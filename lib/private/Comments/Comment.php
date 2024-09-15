<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\Comments;

use OCP\Comments\IComment;
use OCP\Comments\IllegalIDChangeException;
use OCP\Comments\MessageTooLongException;

class Comment implements IComment {
	protected array $data = [
		'id' => '',
		'parentId' => '0',
		'topmostParentId' => '0',
		'childrenCount' => 0,
		'message' => '',
		'verb' => '',
		'actorType' => '',
		'actorId' => '',
		'objectType' => '',
		'objectId' => '',
		'referenceId' => null,
		'creationDT' => null,
		'latestChildDT' => null,
		'reactions' => null,
		'expire_date' => null,
	];

	/**
	 * Comment constructor.
	 *
	 * @param array $data	optional, array with keys according to column names from
	 * 						the comments database scheme
	 */
	public function __construct(array $data = null) {
		if (is_array($data)) {
			$this->fromArray($data);
		}
	}

	/**
	 * Returns the ID of the comment
	 *
	 * It may return an empty string, if the comment was not stored.
	 * It is expected that the concrete Comment implementation gives an ID
	 * by itself (e.g. after saving).
	 *
	 * @since 9.0.0
	 */
	public function getId(): string {
		return $this->data['id'];
	}

	/**
	 * Sets the ID of the comment and returns itself
	 *
	 * It is only allowed to set the ID only, if the current id is an empty
	 * string (which means it is not stored in a database, storage or whatever
	 * the concrete implementation does), or vice versa. Changing a given ID is
	 * not permitted and must result in an IllegalIDChangeException.
	 *
	 * @param string $id
	 * @return IComment
	 * @throws IllegalIDChangeException
	 * @since 9.0.0
	 */
	public function setId($id): IComment {
		if (!is_string($id)) {
			throw new \InvalidArgumentException('String expected.');
		}

		$id = trim($id);
		if ($this->data['id'] === '' || ($this->data['id'] !== '' && $id === '')) {
			$this->data['id'] = $id;
			return $this;
		}

		throw new IllegalIDChangeException('Not allowed to assign a new ID to an already saved comment.');
	}

	/**
	 * Returns the parent ID of the comment
	 *
	 * @since 9.0.0
	 */
	public function getParentId(): string {
		return $this->data['parentId'];
	}

	/**
	 * Sets the parent ID and returns itself
	 *
	 * @param string $parentId
	 * @since 9.0.0
	 */
	public function setParentId($parentId): IComment {
		if (!is_string($parentId)) {
			throw new \InvalidArgumentException('String expected.');
		}
		$this->data['parentId'] = trim($parentId);
		return $this;
	}

	/**
	 * Returns the topmost parent ID of the comment
	 *
	 * @since 9.0.0
	 */
	public function getTopmostParentId(): string {
		return $this->data['topmostParentId'];
	}


	/**
	 * Sets the topmost parent ID and returns itself
	 *
	 * @param string $id
	 * @since 9.0.0
	 */
	public function setTopmostParentId($id): IComment {
		if (!is_string($id)) {
			throw new \InvalidArgumentException('String expected.');
		}
		$this->data['topmostParentId'] = trim($id);
		return $this;
	}

	/**
	 * Returns the number of children
	 *
	 * @since 9.0.0
	 */
	public function getChildrenCount(): int {
		return $this->data['childrenCount'];
	}

	/**
	 * Sets the number of children
	 *
	 * @param int $count
	 * @since 9.0.0
	 */
	public function setChildrenCount($count): IComment {
		if (!is_int($count)) {
			throw new \InvalidArgumentException('Integer expected.');
		}
		$this->data['childrenCount'] = $count;
		return $this;
	}

	/**
	 * Returns the message of the comment
	 * @since 9.0.0
	 */
	public function getMessage(): string {
		return $this->data['message'];
	}

	/**
	 * sets the message of the comment and returns itself
	 *
	 * @param string $message
	 * @param int $maxLength
	 * @throws MessageTooLongException
	 * @since 9.0.0
	 */
	public function setMessage($message, $maxLength = self::MAX_MESSAGE_LENGTH): IComment {
		if (!is_string($message)) {
			throw new \InvalidArgumentException('String expected.');
		}
		$message = trim($message);
		if ($maxLength && mb_strlen($message, 'UTF-8') > $maxLength) {
			throw new MessageTooLongException('Comment message must not exceed ' . $maxLength. ' characters');
		}
		$this->data['message'] = $message;
		return $this;
	}

	/**
	 * returns an array containing mentions that are included in the comment
	 *
	 * @return array each mention provides a 'type' and an 'id', see example below
	 * @since 11.0.0
	 *
	 * The return array looks like:
	 * [
	 *   [
	 *     'type' => 'user',
	 *     'id' => 'citizen4'
	 *   ],
	 *   [
	 *     'type' => 'group',
	 *     'id' => 'media'
	 *   ],
	 *   …
	 * ]
	 *
	 */
	public function getMentions(): array {
		$ok = preg_match_all("/\B(?<![^a-z0-9_\-@\.\'\s])@(\"guest\/[a-f0-9]+\"|\"group\/[a-z0-9_\-@\.\' ]+\"|\"[a-z0-9_\-@\.\' ]+\"|[a-z0-9_\-@\.\']+)/i", $this->getMessage(), $mentions);
		if (!$ok || !isset($mentions[0])) {
			return [];
		}
		$mentionIds = array_unique($mentions[0]);
		usort($mentionIds, static function ($mentionId1, $mentionId2) {
			return mb_strlen($mentionId2) <=> mb_strlen($mentionId1);
		});
		$result = [];
		foreach ($mentionIds as $mentionId) {
			$cleanId = trim(substr($mentionId, 1), '"');
			if (str_starts_with($cleanId, 'guest/')) {
				$result[] = ['type' => 'guest', 'id' => $cleanId];
			} elseif (str_starts_with($cleanId, 'group/')) {
				$result[] = ['type' => 'group', 'id' => substr($cleanId, 6)];
			} else {
				$result[] = ['type' => 'user', 'id' => $cleanId];
			}
		}
		return $result;
	}

	/**
	 * Returns the verb of the comment
	 *
	 * @since 9.0.0
	 */
	public function getVerb(): string {
		return $this->data['verb'];
	}

	/**
	 * Sets the verb of the comment, e.g. 'comment' or 'like'
	 *
	 * @param string $verb
	 * @since 9.0.0
	 */
	public function setVerb($verb): IComment {
		if (!is_string($verb) || !trim($verb)) {
			throw new \InvalidArgumentException('Non-empty String expected.');
		}
		$this->data['verb'] = trim($verb);
		return $this;
	}

	/**
	 * Returns the actor type
	 * @since 9.0.0
	 */
	public function getActorType(): string {
		return $this->data['actorType'];
	}

	/**
	 * Returns the actor ID
	 * @since 9.0.0
	 */
	public function getActorId(): string {
		return $this->data['actorId'];
	}

	/**
	 * Sets (overwrites) the actor type and id
	 *
	 * @param string $actorType e.g. 'users'
	 * @param string $actorId e.g. 'zombie234'
	 * @since 9.0.0
	 */
	public function setActor($actorType, $actorId): IComment {
		if (
			!is_string($actorType) || !trim($actorType)
			|| !is_string($actorId) || $actorId === ''
		) {
			throw new \InvalidArgumentException('String expected.');
		}
		$this->data['actorType'] = trim($actorType);
		$this->data['actorId'] = $actorId;
		return $this;
	}

	/**
	 * Returns the creation date of the comment.
	 *
	 * If not explicitly set, it shall default to the time of initialization.
	 * @since 9.0.0
	 * @throw \LogicException if creation date time is not set yet
	 */
	public function getCreationDateTime(): \DateTime {
		if (!isset($this->data['creationDT'])) {
			throw new \LogicException('Cannot get creation date before setting one or writting to database');
		}
		return $this->data['creationDT'];
	}

	/**
	 * Sets the creation date of the comment and returns itself
	 * @since 9.0.0
	 */
	public function setCreationDateTime(\DateTime $dateTime): IComment {
		$this->data['creationDT'] = $dateTime;
		return $this;
	}

	/**
	 * Returns the DateTime of the most recent child, if set, otherwise null
	 * @since 9.0.0
	 */
	public function getLatestChildDateTime(): ?\DateTime {
		return $this->data['latestChildDT'];
	}

	/**
	 * @inheritDoc
	 */
	public function setLatestChildDateTime(?\DateTime $dateTime = null): IComment {
		$this->data['latestChildDT'] = $dateTime;
		return $this;
	}

	/**
	 * Returns the object type the comment is attached to
	 * @since 9.0.0
	 */
	public function getObjectType(): string {
		return $this->data['objectType'];
	}

	/**
	 * Returns the object id the comment is attached to
	 * @since 9.0.0
	 */
	public function getObjectId(): string {
		return $this->data['objectId'];
	}

	/**
	 * Sets (overwrites) the object of the comment
	 *
	 * @param string $objectType e.g. 'files'
	 * @param string $objectId e.g. '16435'
	 * @since 9.0.0
	 */
	public function setObject($objectType, $objectId): IComment {
		if (
			!is_string($objectType) || !trim($objectType)
			|| !is_string($objectId) || trim($objectId) === ''
		) {
			throw new \InvalidArgumentException('String expected.');
		}
		$this->data['objectType'] = trim($objectType);
		$this->data['objectId'] = trim($objectId);
		return $this;
	}

	/**
	 * Returns the reference id of the comment
	 * @since 19.0.0
	 */
	public function getReferenceId(): ?string {
		return $this->data['referenceId'];
	}

	/**
	 * Sets (overwrites) the reference id of the comment
	 *
	 * @param string $referenceId e.g. sha256 hash sum
	 * @since 19.0.0
	 */
	public function setReferenceId(?string $referenceId): IComment {
		if ($referenceId === null) {
			$this->data['referenceId'] = $referenceId;
		} else {
			$referenceId = trim($referenceId);
			if ($referenceId === '') {
				throw new \InvalidArgumentException('Non empty string expected.');
			}
			$this->data['referenceId'] = $referenceId;
		}
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getReactions(): array {
		return $this->data['reactions'] ?? [];
	}

	/**
	 * @inheritDoc
	 */
	public function setReactions(?array $reactions): IComment {
		$this->data['reactions'] = $reactions;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function setExpireDate(?\DateTime $dateTime): IComment {
		$this->data['expire_date'] = $dateTime;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getExpireDate(): ?\DateTime {
		return $this->data['expire_date'];
	}

	/**
	 * sets the comment data based on an array with keys as taken from the
	 * database.
	 *
	 * @param array $data
	 */
	protected function fromArray($data): IComment {
		foreach (array_keys($data) as $key) {
			// translate DB keys to internal setter names
			$setter = 'set' . implode('', array_map('ucfirst', explode('_', $key)));
			$setter = str_replace('Timestamp', 'DateTime', $setter);

			if (method_exists($this, $setter)) {
				$this->$setter($data[$key]);
			}
		}

		foreach (['actor', 'object'] as $role) {
			if (isset($data[$role . '_type']) && isset($data[$role . '_id'])) {
				$setter = 'set' . ucfirst($role);
				$this->$setter($data[$role . '_type'], $data[$role . '_id']);
			}
		}

		return $this;
	}
}
