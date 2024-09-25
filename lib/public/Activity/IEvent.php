<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Activity;

use OCP\Activity\Exceptions\InvalidValueException;

/**
 * Interface IEvent
 *
 * @since 8.2.0
 */
interface IEvent {
	/**
	 * Set the app of the activity
	 *
	 * @param string $app
	 * @return IEvent
	 * @throws InvalidValueException if the app id is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setApp(string $app): self;

	/**
	 * Set the type of the activity
	 *
	 * @param string $type
	 * @return IEvent
	 * @throws InvalidValueException if the type is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setType(string $type): self;

	/**
	 * Set the affected user of the activity
	 *
	 * @param string $affectedUser
	 * @return IEvent
	 * @throws InvalidValueException if the affected user is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setAffectedUser(string $affectedUser): self;

	/**
	 * Set the author of the activity
	 *
	 * @param string $author
	 * @return IEvent
	 * @throws InvalidValueException if the author is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setAuthor(string $author): self;

	/**
	 * Set the author of the activity
	 *
	 * @param int $timestamp
	 * @return IEvent
	 * @throws InvalidValueException if the timestamp is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setTimestamp(int $timestamp): self;

	/**
	 * Set the subject of the activity
	 *
	 * @param string $subject
	 * @param array $parameters
	 * @return IEvent
	 * @throws InvalidValueException if the subject or parameters are invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setSubject(string $subject, array $parameters = []): self;

	/**
	 * Set a parsed subject
	 *
	 * HTML is not allowed in the parsed subject and will be escaped
	 * automatically by the clients. You can use the RichObjectString system
	 * provided by the Nextcloud server to highlight important parameters via
	 * the setRichSubject method.
	 *
	 * See https://github.com/nextcloud/server/issues/1706 for more information.
	 *
	 * @param string $subject
	 * @return $this
	 * @throws InvalidValueException if the subject is invalid
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setParsedSubject(string $subject): self;

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getParsedSubject(): string;

	/**
	 * Set a RichObjectString subject
	 *
	 * HTML is not allowed in the rich subject and will be escaped automatically
	 * by the clients, but you can use the RichObjectString system provided by
	 * the Nextcloud server to highlight important parameters.
	 *
	 * See https://github.com/nextcloud/server/issues/1706 for more information.
	 *
	 * @param string $subject
	 * @param array<string, array<string, string>> $parameters
	 * @return $this
	 * @throws InvalidValueException if the subject or parameters are invalid
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setRichSubject(string $subject, array $parameters = []): self;

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getRichSubject(): string;

	/**
	 * @return array<string, array<string, string>>
	 * @since 11.0.0
	 */
	public function getRichSubjectParameters(): array;

	/**
	 * Set the message of the activity
	 *
	 * @param string $message
	 * @param array $parameters
	 * @return IEvent
	 * @throws InvalidValueException if the message or parameters are invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setMessage(string $message, array $parameters = []): self;

	/**
	 * Set a parsed message
	 *
	 * HTML is not allowed in the parsed message and will be escaped
	 * automatically by the clients. You can use the RichObjectString system
	 * provided by the Nextcloud server to highlight important parameters via
	 * the setRichMessage method.
	 *
	 * See https://github.com/nextcloud/server/issues/1706 for more information.
	 *
	 * @param string $message
	 * @return $this
	 * @throws InvalidValueException if the message is invalid
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setParsedMessage(string $message): self;

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getParsedMessage(): string;

	/**
	 * Set a RichObjectString message
	 *
	 * HTML is not allowed in the rich message and will be escaped automatically
	 * by the clients, but you can use the RichObjectString system provided by
	 * the Nextcloud server to highlight important parameters.
	 *
	 * See https://github.com/nextcloud/server/issues/1706 for more information.
	 *
	 * @param string $message
	 * @param array<string, array<string, string>> $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the message or parameters are invalid
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setRichMessage(string $message, array $parameters = []): self;

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getRichMessage(): string;

	/**
	 * @return array<string, array<string, string>>
	 * @since 11.0.0
	 */
	public function getRichMessageParameters(): array;

	/**
	 * Set the object of the activity
	 *
	 * @param string $objectType
	 * @param int $objectId
	 * @param string $objectName
	 * @return IEvent
	 * @throws InvalidValueException if the object is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setObject(string $objectType, int $objectId, string $objectName = ''): self;

	/**
	 * Set the link of the activity
	 *
	 * @param string $link
	 * @return IEvent
	 * @throws InvalidValueException if the link is invalid
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setLink(string $link): self;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getApp(): string;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getType(): string;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getAffectedUser(): string;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getAuthor(): string;

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getTimestamp(): int;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getSubject(): string;

	/**
	 * @return array
	 * @since 8.2.0
	 */
	public function getSubjectParameters(): array;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getMessage(): string;

	/**
	 * @return array
	 * @since 8.2.0
	 */
	public function getMessageParameters(): array;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectType(): string;

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getObjectId(): int;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectName(): string;

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getLink(): string;

	/**
	 * Set the absolute url for the icon (should be colored black or not have a color)
	 *
	 * It's automatically color inverted by clients when needed
	 *
	 * @param string $icon
	 * @return $this
	 * @throws InvalidValueException if the icon is invalid
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setIcon(string $icon): self;

	/**
	 * Get the absolute url for the icon (should be colored black or not have a color)
	 *
	 * It's automatically color inverted by clients when needed
	 *
	 * @return string
	 * @since 11.0.0
	 */
	public function getIcon(): string;

	/**
	 * @param IEvent $child
	 * @return $this
	 * @since 11.0.0 - Since 15.0.0 returns $this
	 */
	public function setChildEvent(IEvent $child): self;

	/**
	 * @return IEvent|null
	 * @since 11.0.0
	 */
	public function getChildEvent();

	/**
	 * @return bool
	 * @since 11.0.0
	 */
	public function isValid(): bool;

	/**
	 * @return bool
	 * @since 11.0.0
	 */
	public function isValidParsed(): bool;

	/**
	 * Set whether a notification should be automatically generated for this activity.
	 *
	 * Set this to `false` if the app already generates a notification for the event.
	 *
	 * @param bool $generate
	 * @return IEvent
	 * @since 20.0.0
	 */
	public function setGenerateNotification(bool $generate): self;

	/**
	 * Whether a notification should be automatically generated for this activity.
	 *
	 * @return bool
	 * @since 20.0.0
	 */
	public function getGenerateNotification(): bool;
}
