<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Phil Davis <phil.davis@inf.org>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Activity;

use OCP\Activity\IEvent;
use OCP\RichObjectStrings\InvalidObjectExeption;
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
	protected $subjectParsed;
	/** @var string */
	protected $subjectRich;
	/** @var array */
	protected $subjectRichParameters;
	/** @var string */
	protected $message = '';
	/** @var array */
	protected $messageParameters = [];
	/** @var string */
	protected $messageParsed;
	/** @var string */
	protected $messageRich;
	/** @var array */
	protected $messageRichParameters;
	/** @var string */
	protected $objectType = '';
	/** @var int */
	protected $objectId = 0;
	/** @var string */
	protected $objectName = '';
	/** @var string */
	protected $link = '';
	/** @var string */
	protected $icon = '';

	/** @var IEvent */
	protected $child = null;
	/** @var IValidator */
	protected $richValidator;

	/**
	 * @param IValidator $richValidator
	 */
	public function __construct(IValidator $richValidator) {
		$this->richValidator = $richValidator;
	}

	/**
	 * Set the app of the activity
	 *
	 * @param string $app
	 * @return IEvent
	 * @throws \InvalidArgumentException if the app id is invalid
	 * @since 8.2.0
	 */
	public function setApp($app) {
		if (!is_string($app) || $app === '' || isset($app[32])) {
			throw new \InvalidArgumentException('The given app is invalid');
		}
		$this->app = (string) $app;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getApp() {
		return $this->app;
	}

	/**
	 * Set the type of the activity
	 *
	 * @param string $type
	 * @return IEvent
	 * @throws \InvalidArgumentException if the type is invalid
	 * @since 8.2.0
	 */
	public function setType($type) {
		if (!is_string($type) || $type === '' || isset($type[255])) {
			throw new \InvalidArgumentException('The given type is invalid');
		}
		$this->type = (string) $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Set the affected user of the activity
	 *
	 * @param string $affectedUser
	 * @return IEvent
	 * @throws \InvalidArgumentException if the affected user is invalid
	 * @since 8.2.0
	 */
	public function setAffectedUser($affectedUser) {
		if (!is_string($affectedUser) || $affectedUser === '' || isset($affectedUser[64])) {
			throw new \InvalidArgumentException('The given affected user is invalid');
		}
		$this->affectedUser = (string) $affectedUser;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAffectedUser() {
		return $this->affectedUser;
	}

	/**
	 * Set the author of the activity
	 *
	 * @param string $author
	 * @return IEvent
	 * @throws \InvalidArgumentException if the author is invalid
	 * @since 8.2.0
	 */
	public function setAuthor($author) {
		if (!is_string($author) || isset($author[64])) {
			throw new \InvalidArgumentException('The given author user is invalid'. serialize($author));
		}
		$this->author = (string) $author;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * Set the timestamp of the activity
	 *
	 * @param int $timestamp
	 * @return IEvent
	 * @throws \InvalidArgumentException if the timestamp is invalid
	 * @since 8.2.0
	 */
	public function setTimestamp($timestamp) {
		if (!is_int($timestamp)) {
			throw new \InvalidArgumentException('The given timestamp is invalid');
		}
		$this->timestamp = (int) $timestamp;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Set the subject of the activity
	 *
	 * @param string $subject
	 * @param array $parameters
	 * @return IEvent
	 * @throws \InvalidArgumentException if the subject or parameters are invalid
	 * @since 8.2.0
	 */
	public function setSubject($subject, array $parameters = []) {
		if (!is_string($subject) || isset($subject[255])) {
			throw new \InvalidArgumentException('The given subject is invalid');
		}
		$this->subject = (string) $subject;
		$this->subjectParameters = $parameters;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @return array
	 */
	public function getSubjectParameters() {
		return $this->subjectParameters;
	}

	/**
	 * @param string $subject
	 * @return $this
	 * @throws \InvalidArgumentException if the subject is invalid
	 * @since 11.0.0
	 */
	public function setParsedSubject($subject) {
		if (!is_string($subject) || $subject === '') {
			throw new \InvalidArgumentException('The given parsed subject is invalid');
		}
		$this->subjectParsed = $subject;
		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getParsedSubject() {
		return $this->subjectParsed;
	}

	/**
	 * @param string $subject
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the subject or parameters are invalid
	 * @since 11.0.0
	 */
	public function setRichSubject($subject, array $parameters = []) {
		if (!is_string($subject) || $subject === '') {
			throw new \InvalidArgumentException('The given parsed subject is invalid');
		}
		$this->subjectRich = $subject;

		if (!is_array($parameters)) {
			throw new \InvalidArgumentException('The given subject parameters are invalid');
		}
		$this->subjectRichParameters = $parameters;

		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getRichSubject() {
		return $this->subjectRich;
	}

	/**
	 * @return array[]
	 * @since 11.0.0
	 */
	public function getRichSubjectParameters() {
		return $this->subjectRichParameters;
	}

	/**
	 * Set the message of the activity
	 *
	 * @param string $message
	 * @param array $parameters
	 * @return IEvent
	 * @throws \InvalidArgumentException if the message or parameters are invalid
	 * @since 8.2.0
	 */
	public function setMessage($message, array $parameters = []) {
		if (!is_string($message) || isset($message[255])) {
			throw new \InvalidArgumentException('The given message is invalid');
		}
		$this->message = (string) $message;
		$this->messageParameters = $parameters;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @return array
	 */
	public function getMessageParameters() {
		return $this->messageParameters;
	}

	/**
	 * @param string $message
	 * @return $this
	 * @throws \InvalidArgumentException if the message is invalid
	 * @since 11.0.0
	 */
	public function setParsedMessage($message) {
		if (!is_string($message)) {
			throw new \InvalidArgumentException('The given parsed message is invalid');
		}
		$this->messageParsed = $message;
		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getParsedMessage() {
		return $this->messageParsed;
	}

	/**
	 * @param string $message
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the subject or parameters are invalid
	 * @since 11.0.0
	 */
	public function setRichMessage($message, array $parameters = []) {
		if (!is_string($message)) {
			throw new \InvalidArgumentException('The given parsed message is invalid');
		}
		$this->messageRich = $message;

		if (!is_array($parameters)) {
			throw new \InvalidArgumentException('The given message parameters are invalid');
		}
		$this->messageRichParameters = $parameters;

		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getRichMessage() {
		return $this->messageRich;
	}

	/**
	 * @return array[]
	 * @since 11.0.0
	 */
	public function getRichMessageParameters() {
		return $this->messageRichParameters;
	}

	/**
	 * Set the object of the activity
	 *
	 * @param string $objectType
	 * @param int $objectId
	 * @param string $objectName
	 * @return IEvent
	 * @throws \InvalidArgumentException if the object is invalid
	 * @since 8.2.0
	 */
	public function setObject($objectType, $objectId, $objectName = '') {
		if (!is_string($objectType) || isset($objectType[255])) {
			throw new \InvalidArgumentException('The given object type is invalid');
		}
		if (!is_int($objectId)) {
			throw new \InvalidArgumentException('The given object id is invalid');
		}
		if (!is_string($objectName) || isset($objectName[4000])) {
			throw new \InvalidArgumentException('The given object name is invalid');
		}
		$this->objectType = (string) $objectType;
		$this->objectId = (int) $objectId;
		$this->objectName = (string) $objectName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getObjectType() {
		return $this->objectType;
	}

	/**
	 * @return string
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	/**
	 * @return string
	 */
	public function getObjectName() {
		return $this->objectName;
	}

	/**
	 * Set the link of the activity
	 *
	 * @param string $link
	 * @return IEvent
	 * @throws \InvalidArgumentException if the link is invalid
	 * @since 8.2.0
	 */
	public function setLink($link) {
		if (!is_string($link) || isset($link[4000])) {
			throw new \InvalidArgumentException('The given link is invalid');
		}
		$this->link = (string) $link;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * @param string $icon
	 * @return $this
	 * @throws \InvalidArgumentException if the icon is invalid
	 * @since 11.0.0
	 */
	public function setIcon($icon) {
		if (!is_string($icon) || isset($icon[4000])) {
			throw new \InvalidArgumentException('The given icon is invalid');
		}
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return string
	 * @since 11.0.0
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @param IEvent $child
	 * @since 11.0.0
	 */
	public function setChildEvent(IEvent $child) {
		$this->child = $child;
	}

	/**
	 * @return IEvent|null
	 * @since 11.0.0
	 */
	public function getChildEvent() {
		return $this->child;
	}

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function isValid() {
		return
			$this->isValidCommon()
			&&
			$this->getSubject() !== ''
		;
	}

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function isValidParsed() {
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
			&&
			$this->getParsedSubject() !== ''
		;
	}

	/**
	 * @return bool
	 */
	protected function isValidCommon() {
		return
			$this->getApp() !== ''
			&&
			$this->getType() !== ''
			&&
			$this->getAffectedUser() !== ''
			&&
			$this->getTimestamp() !== 0
			/**
			 * Disabled for BC with old activities
			&&
			$this->getObjectType() !== ''
			&&
			$this->getObjectId() !== 0
			 */
		;
	}
}
