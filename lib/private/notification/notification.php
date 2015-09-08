<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Notification;


class Notification implements INotification {
	/** @var string */
	protected $app;

	/** @var string */
	protected $user;

	/** @var int */
	protected $timestamp;

	/** @var string */
	protected $objectType;

	/** @var int */
	protected $objectId;

	/** @var string */
	protected $subject;

	/** @var array */
	protected $subjectParameters;

	/** @var string */
	protected $subjectParsed;

	/** @var string */
	protected $message;

	/** @var array */
	protected $messageParameters;

	/** @var string */
	protected $messageParsed;

	/** @var string */
	protected $link;

	/** @var string */
	protected $icon;

	/** @var array */
	protected $actions;

	/** @var array */
	protected $actionsParsed;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->app = '';
		$this->user = '';
		$this->timestamp = 0;
		$this->objectType = '';
		$this->objectId = 0;
		$this->subject = '';
		$this->subjectParameters = [];
		$this->subjectParsed = '';
		$this->message = '';
		$this->messageParameters = [];
		$this->messageParsed = '';
		$this->link = '';
		$this->icon = '';
		$this->actions = [];
		$this->actionsParsed = [];
	}

	/**
	 * @param string $app
	 * @return $this
	 * @throws \InvalidArgumentException if the app id is invalid
	 * @since 8.2.0
	 */
	public function setApp($app) {
		if (!is_string($app) || $app === '' || isset($app[32])) {
			throw new \InvalidArgumentException('The given app name is invalid');
		}
		$this->app = $app;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getApp() {
		return $this->app;
	}

	/**
	 * @param string $user
	 * @return $this
	 * @throws \InvalidArgumentException if the user id is invalid
	 * @since 8.2.0
	 */
	public function setUser($user) {
		if (!is_string($user) || $user === '' || isset($user[64])) {
			throw new \InvalidArgumentException('The given user id is invalid');
		}
		$this->user = $user;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param int $timestamp
	 * @return $this
	 * @throws \InvalidArgumentException if the timestamp is invalid
	 * @since 8.2.0
	 */
	public function setTimestamp($timestamp) {
		if (!is_int($timestamp)) {
			throw new \InvalidArgumentException('The given timestamp is invalid');
		}
		$this->timestamp = $timestamp;
		return $this;
	}

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @param string $type
	 * @param int $id
	 * @return $this
	 * @throws \InvalidArgumentException if the object type or id is invalid
	 * @since 8.2.0
	 */
	public function setObject($type, $id) {
		if (!is_string($type) || $type === '' || isset($type[64])) {
			throw new \InvalidArgumentException('The given object type is invalid');
		}
		$this->objectType = $type;

		if (!is_int($id)) {
			throw new \InvalidArgumentException('The given object id is invalid');
		}
		$this->objectId = $id;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectType() {
		return $this->objectType;
	}

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	/**
	 * @param string $subject
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the subject or parameters are invalid
	 * @since 8.2.0
	 */
	public function setSubject($subject, array $parameters = []) {
		if (!is_string($subject) || $subject === '' || isset($subject[64])) {
			throw new \InvalidArgumentException('The given subject is invalid');
		}
		$this->subject = $subject;

		if (!is_array($parameters)) {
			throw new \InvalidArgumentException('The given subject parameters are invalid');
		}
		$this->subjectParameters = $parameters;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @return string[]
	 * @since 8.2.0
	 */
	public function getSubjectParameters() {
		return $this->subjectParameters;
	}

	/**
	 * @param string $subject
	 * @return $this
	 * @throws \InvalidArgumentException if the subject are invalid
	 * @since 8.2.0
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
	 * @since 8.2.0
	 */
	public function getParsedSubject() {
		return $this->subjectParsed;
	}

	/**
	 * @param string $message
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the message or parameters are invalid
	 * @since 8.2.0
	 */
	public function setMessage($message, array $parameters = []) {
		if (!is_string($message) || $message === '' || isset($message[64])) {
			throw new \InvalidArgumentException('The given message is invalid');
		}
		$this->message = $message;

		if (!is_array($parameters)) {
			throw new \InvalidArgumentException('The given message parameters are invalid');
		}
		$this->messageParameters = $parameters;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @return string[]
	 * @since 8.2.0
	 */
	public function getMessageParameters() {
		return $this->messageParameters;
	}

	/**
	 * @param string $message
	 * @return $this
	 * @throws \InvalidArgumentException if the message are invalid
	 * @since 8.2.0
	 */
	public function setParsedMessage($message) {
		if (!is_string($message) || $message === '') {
			throw new \InvalidArgumentException('The given parsed message is invalid');
		}
		$this->messageParsed = $message;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getParsedMessage() {
		return $this->messageParsed;
	}

	/**
	 * @param string $link
	 * @return $this
	 * @throws \InvalidArgumentException if the link are invalid
	 * @since 8.2.0
	 */
	public function setLink($link) {
		if (!is_string($link) || $link === '' || isset($link[4000])) {
			throw new \InvalidArgumentException('The given link is invalid');
		}
		$this->link = $link;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * @param string $icon
	 * @return $this
	 * @throws \InvalidArgumentException if the icon are invalid
	 * @since 8.2.0
	 */
	public function setIcon($icon) {
		if (!is_string($icon) || $icon === '' || isset($icon[64])) {
			throw new \InvalidArgumentException('The given icon is invalid');
		}
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @return IAction
	 * @since 8.2.0
	 */
	public function createAction() {
		return new Action();
	}

	/**
	 * @param IAction $action
	 * @return $this
	 * @throws \InvalidArgumentException if the action are invalid
	 * @since 8.2.0
	 */
	public function addAction(IAction $action) {
		if (!$action->isValid()) {
			throw new \InvalidArgumentException('The given action is invalid');
		}
		$this->actions[] = $action;
		return $this;
	}

	/**
	 * @return IAction[]
	 * @since 8.2.0
	 */
	public function getActions() {
		return $this->actions;
	}

	/**
	 * @param IAction $action
	 * @return $this
	 * @throws \InvalidArgumentException if the action are invalid
	 * @since 8.2.0
	 */
	public function addParsedAction(IAction $action) {
		if (!$action->isValidParsed()) {
			throw new \InvalidArgumentException('The given parsed action is invalid');
		}
		$this->actionsParsed[] = $action;
		return $this;
	}

	/**
	 * @return IAction[]
	 * @since 8.2.0
	 */
	public function getParsedActions() {
		return $this->actionsParsed;
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
			$this->getUser() !== ''
			&&
			$this->getTimestamp() !== 0
			&&
			$this->getObjectType() !== ''
			&&
			$this->getObjectId() !== 0
		;
	}
}
