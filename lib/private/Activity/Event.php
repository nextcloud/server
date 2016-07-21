<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

class Event implements IEvent {
	/** @var array */
	protected $data = [
		'app' => null,
		'type' => null,
		'affected_user' => null,
		'author' => null,
		'timestamp' => null,
		'subject' => null,
		'subject_parameters' => null,
		'message' => '',
		'message_parameters' => [],
		'object_type' => '',
		'object_id' => 0,
		'object_name' => '',
		'link' => '',
	];

	/**
	 * Set the app of the activity
	 *
	 * @param string $app
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setApp($app) {
		$this->data['app'] = (string) $app;
		return $this;
	}

	/**
	 * Set the type of the activity
	 *
	 * @param string $type
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setType($type) {
		$this->data['type'] = (string) $type;
		return $this;
	}

	/**
	 * Set the affected user of the activity
	 *
	 * @param string $affectedUser
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setAffectedUser($affectedUser) {
		$this->data['affected_user'] = (string) $affectedUser;
		return $this;
	}

	/**
	 * Set the author of the activity
	 *
	 * @param string $author
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setAuthor($author) {
		$this->data['author'] = (string) $author;
		return $this;
	}

	/**
	 * Set the timestamp of the activity
	 *
	 * @param int $timestamp
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setTimestamp($timestamp) {
		$this->data['timestamp'] = (int) $timestamp;
		return $this;
	}

	/**
	 * Set the subject of the activity
	 *
	 * @param string $subject
	 * @param array $parameters
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setSubject($subject, array $parameters = []) {
		$this->data['subject'] = (string) $subject;
		$this->data['subject_parameters'] = $parameters;
		return $this;
	}

	/**
	 * Set the message of the activity
	 *
	 * @param string $message
	 * @param array $parameters
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setMessage($message, array $parameters = []) {
		$this->data['message'] = (string) $message;
		$this->data['message_parameters'] = $parameters;
		return $this;
	}

	/**
	 * Set the object of the activity
	 *
	 * @param string $objectType
	 * @param int $objectId
	 * @param string $objectName
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setObject($objectType, $objectId, $objectName = '') {
		$this->data['object_type'] = (string) $objectType;
		$this->data['object_id'] = (int) $objectId;
		$this->data['object_name'] = (string) $objectName;
		return $this;
	}

	/**
	 * Set the link of the activity
	 *
	 * @param string $link
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setLink($link) {
		$this->data['link'] = (string) $link;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getApp() {
		return $this->data['app'];
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->data['type'];
	}

	/**
	 * @return string
	 */
	public function getAffectedUser() {
		return $this->data['affected_user'];
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->data['author'];
	}

	/**
	 * @return int
	 */
	public function getTimestamp() {
		return $this->data['timestamp'];
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->data['subject'];
	}

	/**
	 * @return array
	 */
	public function getSubjectParameters() {
		return $this->data['subject_parameters'];
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->data['message'];
	}

	/**
	 * @return array
	 */
	public function getMessageParameters() {
		return $this->data['message_parameters'];
	}

	/**
	 * @return string
	 */
	public function getObjectType() {
		return $this->data['object_type'];
	}

	/**
	 * @return string
	 */
	public function getObjectId() {
		return $this->data['object_id'];
	}

	/**
	 * @return string
	 */
	public function getObjectName() {
		return $this->data['object_name'];
	}

	/**
	 * @return string
	 */
	public function getLink() {
		return $this->data['link'];
	}
}
