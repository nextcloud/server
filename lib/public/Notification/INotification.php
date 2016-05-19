<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCP\Notification;

/**
 * Interface INotification
 *
 * @package OCP\Notification
 * @since 9.0.0
 */
interface INotification {
	/**
	 * @param string $app
	 * @return $this
	 * @throws \InvalidArgumentException if the app id are invalid
	 * @since 9.0.0
	 */
	public function setApp($app);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getApp();

	/**
	 * @param string $user
	 * @return $this
	 * @throws \InvalidArgumentException if the user id are invalid
	 * @since 9.0.0
	 */
	public function setUser($user);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getUser();

	/**
	 * @param \DateTime $dateTime
	 * @return $this
	 * @throws \InvalidArgumentException if the $dateTime is invalid
	 * @since 9.0.0
	 */
	public function setDateTime(\DateTime $dateTime);

	/**
	 * @return \DateTime
	 * @since 9.0.0
	 */
	public function getDateTime();

	/**
	 * @param string $type
	 * @param string $id
	 * @return $this
	 * @throws \InvalidArgumentException if the object type or id is invalid
	 * @since 9.0.0
	 */
	public function setObject($type, $id);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectType();

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getObjectId();

	/**
	 * @param string $subject
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the subject or parameters are invalid
	 * @since 9.0.0
	 */
	public function setSubject($subject, array $parameters = []);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getSubject();

	/**
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getSubjectParameters();

	/**
	 * @param string $subject
	 * @return $this
	 * @throws \InvalidArgumentException if the subject are invalid
	 * @since 9.0.0
	 */
	public function setParsedSubject($subject);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getParsedSubject();

	/**
	 * @param string $message
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the message or parameters are invalid
	 * @since 9.0.0
	 */
	public function setMessage($message, array $parameters = []);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getMessage();

	/**
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getMessageParameters();

	/**
	 * @param string $message
	 * @return $this
	 * @throws \InvalidArgumentException if the message are invalid
	 * @since 9.0.0
	 */
	public function setParsedMessage($message);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getParsedMessage();

	/**
	 * @param string $link
	 * @return $this
	 * @throws \InvalidArgumentException if the link are invalid
	 * @since 9.0.0
	 */
	public function setLink($link);

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getLink();

	/**
	 * @return IAction
	 * @since 9.0.0
	 */
	public function createAction();

	/**
	 * @param IAction $action
	 * @return $this
	 * @throws \InvalidArgumentException if the action are invalid
	 * @since 9.0.0
	 */
	public function addAction(IAction $action);

	/**
	 * @return IAction[]
	 * @since 9.0.0
	 */
	public function getActions();

	/**
	 * @param IAction $action
	 * @return $this
	 * @throws \InvalidArgumentException if the action are invalid
	 * @since 9.0.0
	 */
	public function addParsedAction(IAction $action);

	/**
	 * @return IAction[]
	 * @since 9.0.0
	 */
	public function getParsedActions();

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isValid();

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isValidParsed();
}
