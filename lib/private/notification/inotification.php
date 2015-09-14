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

/**
 * Interface INotification
 *
 * @package OC\Notification
 * @since 8.2.0
 *
 * DEVELOPER NOTE:
 * The notification api is experimental only in 8.2.0! Do not start using it,
 * if you can not prepare an update for the next version afterwards.
 */
interface INotification {
	/**
	 * @param string $app
	 * @return $this
	 * @throws \InvalidArgumentException if the app id are invalid
	 * @since 8.2.0
	 */
	public function setApp($app);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getApp();

	/**
	 * @param string $user
	 * @return $this
	 * @throws \InvalidArgumentException if the user id are invalid
	 * @since 8.2.0
	 */
	public function setUser($user);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getUser();

	/**
	 * @param int $timestamp
	 * @return $this
	 * @throws \InvalidArgumentException if the timestamp are invalid
	 * @since 8.2.0
	 */
	public function setTimestamp($timestamp);

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getTimestamp();

	/**
	 * @param string $type
	 * @param int $id
	 * @return $this
	 * @throws \InvalidArgumentException if the object type or id are invalid
	 * @since 8.2.0
	 */
	public function setObject($type, $id);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectType();

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getObjectId();

	/**
	 * @param string $subject
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the subject or parameters are invalid
	 * @since 8.2.0
	 */
	public function setSubject($subject, array $parameters = []);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getSubject();

	/**
	 * @return string[]
	 * @since 8.2.0
	 */
	public function getSubjectParameters();

	/**
	 * @param string $subject
	 * @return $this
	 * @throws \InvalidArgumentException if the subject are invalid
	 * @since 8.2.0
	 */
	public function setParsedSubject($subject);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getParsedSubject();

	/**
	 * @param string $message
	 * @param array $parameters
	 * @return $this
	 * @throws \InvalidArgumentException if the message or parameters are invalid
	 * @since 8.2.0
	 */
	public function setMessage($message, array $parameters = []);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getMessage();

	/**
	 * @return string[]
	 * @since 8.2.0
	 */
	public function getMessageParameters();

	/**
	 * @param string $message
	 * @return $this
	 * @throws \InvalidArgumentException if the message are invalid
	 * @since 8.2.0
	 */
	public function setParsedMessage($message);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getParsedMessage();

	/**
	 * @param string $link
	 * @return $this
	 * @throws \InvalidArgumentException if the link are invalid
	 * @since 8.2.0
	 */
	public function setLink($link);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getLink();

	/**
	 * @param string $icon
	 * @return $this
	 * @throws \InvalidArgumentException if the icon are invalid
	 * @since 8.2.0
	 */
	public function setIcon($icon);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getIcon();

	/**
	 * @return IAction
	 * @since 8.2.0
	 */
	public function createAction();

	/**
	 * @param IAction $action
	 * @return $this
	 * @throws \InvalidArgumentException if the action are invalid
	 * @since 8.2.0
	 */
	public function addAction(IAction $action);

	/**
	 * @return IAction[]
	 * @since 8.2.0
	 */
	public function getActions();

	/**
	 * @param IAction $action
	 * @return $this
	 * @throws \InvalidArgumentException if the action are invalid
	 * @since 8.2.0
	 */
	public function addParsedAction(IAction $action);

	/**
	 * @return IAction[]
	 * @since 8.2.0
	 */
	public function getParsedActions();

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function isValid();

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function isValidParsed();
}
