<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Activity;

use OCP\Activity\Exceptions\InvalidValueException;
use OCP\Activity\IEvent;
use OCP\RichObjectStrings\InvalidObjectExeption;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;

class Event implements IEvent {
	/** @var string */
	protected $app = '';
	/** @var string */
	protected $type = '';
	/** @var string */
	protected $affectedUser = '';
	/** @var string */
	protected $author = '';
	/** @var int */
	protected $timestamp = 0;
	/** @var string */
	protected $subject = '';
	/** @var array */
	protected $subjectParameters = [];
	/** @var string */
	protected $subjectParsed = '';
	/** @var string */
	protected $subjectRich = '';
	/** @var array<string, array<string, string>> */
	protected $subjectRichParameters = [];
	/** @var string */
	protected $message = '';
	/** @var array */
	protected $messageParameters = [];
	/** @var string */
	protected $messageParsed = '';
	/** @var string */
	protected $messageRich = '';
	/** @var array<string, array<string, string>> */
	protected $messageRichParameters = [];
	/** @var string */
	protected $objectType = '';
	protected string|int $objectId = 0;
	/** @var string */
	protected $objectName = '';
	/** @var string */
	protected $link = '';
	/** @var string */
	protected $icon = '';
	/** @var bool */
	protected $generateNotification = true;

	/** @var IEvent|null */
	protected $child;

	public function __construct(
		protected IValidator $richValidator,
		protected IRichTextFormatter $richTextFormatter,
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setApp(string $app): IEvent {
		if ($app === '' || isset($app[32])) {
			throw new InvalidValueException('app');
		}
		$this->app = $app;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setType(string $type): IEvent {
		if ($type === '' || isset($type[255])) {
			throw new InvalidValueException('type');
		}
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getType(): string {
		return $this->type;
	}

	/**
	 *  {@inheritDoc}
	 */
	#[\Override]
	public function setAffectedUser(string $affectedUser): IEvent {
		if ($affectedUser === '' || isset($affectedUser[64])) {
			throw new InvalidValueException('affectedUser');
		}
		$this->affectedUser = $affectedUser;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getAffectedUser(): string {
		return $this->affectedUser;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setAuthor(string $author): IEvent {
		if (isset($author[64])) {
			throw new InvalidValueException('author');
		}
		$this->author = $author;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getAuthor(): string {
		return $this->author;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setTimestamp(int $timestamp): IEvent {
		if ($timestamp < 0) {
			throw new InvalidValueException('timestamp');
		}
		$this->timestamp = $timestamp;
		return $this;
	}

	/**
	 * @return int
	 */
	#[\Override]
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setSubject(string $subject, array $parameters = []): IEvent {
		if (isset($subject[255])) {
			throw new InvalidValueException('subject');
		}
		$this->subject = $subject;
		$this->subjectParameters = $parameters;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * @return array
	 */
	#[\Override]
	public function getSubjectParameters(): array {
		return $this->subjectParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setParsedSubject(string $subject): IEvent {
		if ($subject === '') {
			throw new InvalidValueException('parsedSubject');
		}
		$this->subjectParsed = $subject;
		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	#[\Override]
	public function getParsedSubject(): string {
		return $this->subjectParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setRichSubject(string $subject, array $parameters = []): IEvent {
		if ($subject === '') {
			throw new InvalidValueException('richSubject');
		}
		$this->subjectRich = $subject;
		$this->subjectRichParameters = $parameters;

		if ($this->subjectParsed === '') {
			try {
				$this->subjectParsed = $this->richTextFormatter->richToParsed($subject, $parameters);
			} catch (\InvalidArgumentException $e) {
				throw new InvalidValueException('richSubjectParameters', $e);
			}
		}

		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	#[\Override]
	public function getRichSubject(): string {
		return $this->subjectRich;
	}

	/**
	 * @return array<string, array<string, string>>
	 * @since 11.0.0
	 */
	#[\Override]
	public function getRichSubjectParameters(): array {
		return $this->subjectRichParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setMessage(string $message, array $parameters = []): IEvent {
		if (isset($message[255])) {
			throw new InvalidValueException('message');
		}
		$this->message = $message;
		$this->messageParameters = $parameters;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getMessage(): string {
		return $this->message;
	}

	/**
	 * @return array
	 */
	#[\Override]
	public function getMessageParameters(): array {
		return $this->messageParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setParsedMessage(string $message): IEvent {
		$this->messageParsed = $message;
		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	#[\Override]
	public function getParsedMessage(): string {
		return $this->messageParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setRichMessage(string $message, array $parameters = []): IEvent {
		$this->messageRich = $message;
		$this->messageRichParameters = $parameters;

		if ($this->messageParsed === '') {
			try {
				$this->messageParsed = $this->richTextFormatter->richToParsed($message, $parameters);
			} catch (\InvalidArgumentException $e) {
				throw new InvalidValueException('richMessageParameters', $e);
			}
		}

		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	#[\Override]
	public function getRichMessage(): string {
		return $this->messageRich;
	}

	/**
	 * @return array<string, array<string, string>>
	 * @since 11.0.0
	 */
	#[\Override]
	public function getRichMessageParameters(): array {
		return $this->messageRichParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setObject(string $objectType, string|int $objectId, string $objectName = ''): IEvent {
		if (isset($objectType[255])) {
			throw new InvalidValueException('objectType');
		}
		if (isset($objectName[4000])) {
			throw new InvalidValueException('objectName');
		}
		if (is_string($objectId) && isset($objectId[19])) {
			throw new InvalidValueException('objectId');
		}
		$this->objectType = $objectType;
		$this->objectId = $objectId;
		$this->objectName = $objectName;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getObjectType(): string {
		return $this->objectType;
	}

	/**
	 * @return int|string
	 */
	#[\Override]
	public function getObjectId(): string|int {
		return $this->objectId;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getObjectName(): string {
		return $this->objectName;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setLink(string $link): IEvent {
		if (isset($link[4000])) {
			throw new InvalidValueException('link');
		}
		$this->link = $link;
		return $this;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setIcon(string $icon): IEvent {
		if (isset($icon[4000])) {
			throw new InvalidValueException('icon');
		}
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	#[\Override]
	public function getIcon(): string {
		return $this->icon;
	}

	/**
	 * @param IEvent $child
	 * @return $this
	 * @since 11.0.0 - Since 15.0.0 returns $this
	 */
	#[\Override]
	public function setChildEvent(IEvent $child): IEvent {
		$this->child = $child;
		return $this;
	}

	/**
	 * @return IEvent|null
	 * @since 11.0.0
	 */
	#[\Override]
	public function getChildEvent() {
		return $this->child;
	}

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	#[\Override]
	public function isValid(): bool {
		return
			$this->isValidCommon()
			&& $this->getSubject() !== ''
		;
	}

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	#[\Override]
	public function isValidParsed(): bool {
		if ($this->getRichSubject() !== '' || !empty($this->getRichSubjectParameters())) {
			try {
				$this->richValidator->validate($this->getRichSubject(), $this->getRichSubjectParameters());
			} catch (InvalidObjectExeption $e) {
				return false;
			}
		}

		if ($this->getRichMessage() !== '' || !empty($this->getRichMessageParameters())) {
			try {
				$this->richValidator->validate($this->getRichMessage(), $this->getRichMessageParameters());
			} catch (InvalidObjectExeption $e) {
				return false;
			}
		}

		return
			$this->isValidCommon()
			&& $this->getParsedSubject() !== ''
		;
	}

	protected function isValidCommon(): bool {
		return
			$this->getApp() !== ''
			&& $this->getType() !== ''
			&& $this->getTimestamp() !== 0
			/**
			 * Disabled for BC with old activities
			 * &&
			 * $this->getObjectType() !== ''
			 * &&
			 * $this->getObjectId() !== 0
			 */
		;
	}

	#[\Override]
	public function setGenerateNotification(bool $generate): IEvent {
		$this->generateNotification = $generate;
		return $this;
	}

	#[\Override]
	public function getGenerateNotification(): bool {
		return $this->generateNotification;
	}
}
