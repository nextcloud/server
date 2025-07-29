<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Notification;

use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\Notification\InvalidValueException;
use OCP\RichObjectStrings\InvalidObjectExeption;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;

class Notification implements INotification {
	/**
	 * A very small and privileged list of apps that are allowed to push during DND.
	 */
	public const PRIORITY_NOTIFICATION_APPS = [
		'spreed',
		'twofactor_nextcloud_notification',
	];

	protected string $app = '';
	protected string $user = '';
	protected \DateTime $dateTime;
	protected string $objectType = '';
	protected string $objectId = '';
	protected string $subject = '';
	protected array $subjectParameters = [];
	protected string $subjectParsed = '';
	protected string $subjectRich = '';
	protected array $subjectRichParameters = [];
	protected string $message = '';
	protected array $messageParameters = [];
	protected string $messageParsed = '';
	protected string $messageRich = '';
	protected array $messageRichParameters = [];
	protected string $link = '';
	protected string $icon = '';
	protected bool $priorityNotification = false;
	protected array $actions = [];
	protected array $actionsParsed = [];
	protected bool $hasPrimaryAction = false;
	protected bool $hasPrimaryParsedAction = false;

	public function __construct(
		protected IValidator $richValidator,
		protected IRichTextFormatter $richTextFormatter,
	) {
		$this->dateTime = new \DateTime();
		$this->dateTime->setTimestamp(0);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setApp(string $app): INotification {
		if ($app === '' || isset($app[32])) {
			throw new InvalidValueException('app');
		}
		$this->app = $app;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUser(string $user): INotification {
		if ($user === '' || isset($user[64])) {
			throw new InvalidValueException('user');
		}
		$this->user = $user;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUser(): string {
		return $this->user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setDateTime(\DateTime $dateTime): INotification {
		if ($dateTime->getTimestamp() === 0) {
			throw new InvalidValueException('dateTime');
		}
		$this->dateTime = $dateTime;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDateTime(): \DateTime {
		return $this->dateTime;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setObject(string $type, string $id): INotification {
		if ($type === '' || isset($type[64])) {
			throw new InvalidValueException('objectType');
		}
		$this->objectType = $type;

		if ($id === '' || isset($id[64])) {
			throw new InvalidValueException('objectId');
		}
		$this->objectId = $id;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getObjectType(): string {
		return $this->objectType;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getObjectId(): string {
		return $this->objectId;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setSubject(string $subject, array $parameters = []): INotification {
		if ($subject === '' || isset($subject[64])) {
			throw new InvalidValueException('subject');
		}

		$this->subject = $subject;
		$this->subjectParameters = $parameters;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSubjectParameters(): array {
		return $this->subjectParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setParsedSubject(string $subject): INotification {
		if ($subject === '') {
			throw new InvalidValueException('parsedSubject');
		}
		$this->subjectParsed = $subject;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParsedSubject(): string {
		return $this->subjectParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setRichSubject(string $subject, array $parameters = []): INotification {
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
	 * {@inheritDoc}
	 */
	public function getRichSubject(): string {
		return $this->subjectRich;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRichSubjectParameters(): array {
		return $this->subjectRichParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMessage(string $message, array $parameters = []): INotification {
		if ($message === '' || isset($message[64])) {
			throw new InvalidValueException('message');
		}

		$this->message = $message;
		$this->messageParameters = $parameters;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMessage(): string {
		return $this->message;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMessageParameters(): array {
		return $this->messageParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setParsedMessage(string $message): INotification {
		if ($message === '') {
			throw new InvalidValueException('parsedMessage');
		}
		$this->messageParsed = $message;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParsedMessage(): string {
		return $this->messageParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setRichMessage(string $message, array $parameters = []): INotification {
		if ($message === '') {
			throw new InvalidValueException('richMessage');
		}

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
	 * {@inheritDoc}
	 */
	public function getRichMessage(): string {
		return $this->messageRich;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRichMessageParameters(): array {
		return $this->messageRichParameters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setLink(string $link): INotification {
		if ($link === '' || isset($link[4000])) {
			throw new InvalidValueException('link');
		}
		$this->link = $link;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setIcon(string $icon): INotification {
		if ($icon === '' || isset($icon[4000])) {
			throw new InvalidValueException('icon');
		}
		$this->icon = $icon;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return $this->icon;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPriorityNotification(bool $priorityNotification): INotification {
		if ($priorityNotification && !in_array($this->getApp(), self::PRIORITY_NOTIFICATION_APPS, true)) {
			throw new InvalidValueException('priorityNotification');
		}

		$this->priorityNotification = $priorityNotification;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isPriorityNotification(): bool {
		return $this->priorityNotification;
	}

	/**
	 * {@inheritDoc}
	 */
	public function createAction(): IAction {
		return new Action();
	}

	/**
	 * {@inheritDoc}
	 */
	public function addAction(IAction $action): INotification {
		if (!$action->isValid()) {
			throw new InvalidValueException('action');
		}

		if ($action->isPrimary()) {
			if ($this->hasPrimaryAction) {
				throw new InvalidValueException('primaryAction');
			}

			$this->hasPrimaryAction = true;
		}

		$this->actions[] = $action;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getActions(): array {
		return $this->actions;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addParsedAction(IAction $action): INotification {
		if (!$action->isValidParsed()) {
			throw new InvalidValueException('action');
		}

		if ($action->isPrimary()) {
			if ($this->hasPrimaryParsedAction) {
				throw new InvalidValueException('primaryAction');
			}

			$this->hasPrimaryParsedAction = true;

			// Make sure the primary action is always the first one
			array_unshift($this->actionsParsed, $action);
		} else {
			$this->actionsParsed[] = $action;
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParsedActions(): array {
		return $this->actionsParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isValid(): bool {
		return
			$this->isValidCommon()
			&& $this->getSubject() !== ''
		;
	}

	/**
	 * {@inheritDoc}
	 */
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
		if ($this->isPriorityNotification() && !in_array($this->getApp(), self::PRIORITY_NOTIFICATION_APPS, true)) {
			return false;
		}

		return
			$this->getApp() !== ''
			&& $this->getUser() !== ''
			&& $this->getDateTime()->getTimestamp() !== 0
			&& $this->getObjectType() !== ''
			&& $this->getObjectId() !== ''
		;
	}
}
