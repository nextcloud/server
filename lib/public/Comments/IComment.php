<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

/**
 * Interface IComment
 *
 * This class represents a comment
 *
 * @since 9.0.0
 */
interface IComment {
	/**
	 * @since 9.0.0
	 */
	public const MAX_MESSAGE_LENGTH = 1000;

	/**
	 * returns the ID of the comment
	 *
	 * It may return an empty string, if the comment was not stored.
	 * It is expected that the concrete Comment implementation gives an ID
	 * by itself (e.g. after saving).
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getId();

	/**
	 * sets the ID of the comment and returns itself
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
	public function setId($id);

	/**
	 * returns the parent ID of the comment
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getParentId();

	/**
	 * sets the parent ID and returns itself
	 * @param string $parentId
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setParentId($parentId);

	/**
	 * returns the topmost parent ID of the comment
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getTopmostParentId();


	/**
	 * sets the topmost parent ID and returns itself
	 *
	 * @param string $id
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setTopmostParentId($id);

	/**
	 * returns the number of children
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getChildrenCount();

	/**
	 * sets the number of children
	 *
	 * @param int $count
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setChildrenCount($count);

	/**
	 * returns the message of the comment
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getMessage();

	/**
	 * sets the message of the comment and returns itself
	 *
	 * When the given message length exceeds MAX_MESSAGE_LENGTH an
	 * MessageTooLongException shall be thrown.
	 *
	 * @param string $message
	 * @param int $maxLength
	 * @return IComment
	 * @throws MessageTooLongException
	 * @since 9.0.0 - $maxLength added in 16.0.2
	 */
	public function setMessage($message, $maxLength = self::MAX_MESSAGE_LENGTH);

	/**
	 * returns an array containing mentions that are included in the comment
	 *
	 * @return array each mention provides a 'type' and an 'id', see example below
	 * @psalm-return list<array{type: 'guest'|'email'|'federated_group'|'group'|'federated_team'|'team'|'federated_user'|'user', id: non-empty-lowercase-string}>
	 * @since 30.0.2 Type 'email' is supported
	 * @since 29.0.0 Types 'federated_group', 'federated_team', 'team' and 'federated_user' are supported
	 * @since 23.0.0 Type 'group' is supported
	 * @since 17.0.0 Type 'guest' is supported
	 * @since 11.0.0
	 */
	public function getMentions();

	/**
	 * returns the verb of the comment
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getVerb();

	/**
	 * sets the verb of the comment, e.g. 'comment' or 'like'
	 *
	 * @param string $verb
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setVerb($verb);

	/**
	 * returns the actor type
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getActorType();

	/**
	 * returns the actor ID
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getActorId();

	/**
	 * sets (overwrites) the actor type and id
	 *
	 * @param string $actorType e.g. 'users'
	 * @param string $actorId e.g. 'zombie234'
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setActor($actorType, $actorId);

	/**
	 * returns the creation date of the comment.
	 *
	 * If not explicitly set, it shall default to the time of initialization.
	 *
	 * @return \DateTime
	 * @since 9.0.0
	 */
	public function getCreationDateTime();

	/**
	 * sets the creation date of the comment and returns itself
	 *
	 * @param \DateTime $dateTime
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setCreationDateTime(\DateTime $dateTime);

	/**
	 * returns the date of the most recent child
	 *
	 * @return \DateTime
	 * @since 9.0.0
	 */
	public function getLatestChildDateTime();

	/**
	 * sets the date of the most recent child
	 *
	 * @param \DateTime|null $dateTime
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setLatestChildDateTime(?\DateTime $dateTime = null);

	/**
	 * returns the object type the comment is attached to
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectType();

	/**
	 * returns the object id the comment is attached to
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectId();

	/**
	 * sets (overwrites) the object of the comment
	 *
	 * @param string $objectType e.g. 'files'
	 * @param string $objectId e.g. '16435'
	 * @return IComment
	 * @since 9.0.0
	 */
	public function setObject($objectType, $objectId);

	/**
	 * returns the reference id of the comment
	 *
	 * @return string|null
	 * @since 19.0.0
	 */
	public function getReferenceId(): ?string;

	/**
	 * sets (overwrites) the reference id of the comment
	 *
	 * @param string|null $referenceId e.g. sha256 hash sum
	 * @return IComment
	 * @since 19.0.0
	 */
	public function setReferenceId(?string $referenceId): IComment;

	/**
	 * Returns the metadata of the comment
	 *
	 * @return array|null
	 * @since 29.0.0
	 */
	public function getMetaData(): ?array;

	/**
	 * Sets (overwrites) the metadata of the comment
	 * Data as a json encoded array
	 *
	 * @param array|null $metaData
	 * @return IComment
	 * @throws \JsonException When the metadata can not be converted to a json encoded string
	 * @since 29.0.0
	 */
	public function setMetaData(?array $metaData): IComment;

	/**
	 * Returns the reactions array if exists
	 *
	 * The keys is the emoji of reaction and the value is the total.
	 *
	 * @return array<string, integer> e.g. ["👍":1]
	 * @since 24.0.0
	 */
	public function getReactions(): array;

	/**
	 * Set summarized array of reactions by reaction type
	 *
	 * The keys is the emoji of reaction and the value is the total.
	 *
	 * @param array<string, integer>|null $reactions e.g. ["👍":1]
	 * @return IComment
	 * @since 24.0.0
	 */
	public function setReactions(?array $reactions): IComment;

	/**
	 * Set message expire date
	 *
	 * @param \DateTime|null $dateTime
	 * @return IComment
	 * @since 25.0.0
	 */
	public function setExpireDate(?\DateTime $dateTime): IComment;

	/**
	 * Get message expire date
	 *
	 * @return ?\DateTime
	 * @since 25.0.0
	 */
	public function getExpireDate(): ?\DateTime;
}
