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

/**
 * Public interface of ownCloud for apps to use.
 * Activity/IEvent interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Activity;

/**
 * Interface IEvent
 *
 * @package OCP\Activity
 * @since 8.2.0
 */
interface IEvent {
	/**
	 * Set the app of the activity
	 *
	 * @param string $app
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setApp($app);

	/**
	 * Set the type of the activity
	 *
	 * @param string $type
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setType($type);

	/**
	 * Set the affected user of the activity
	 *
	 * @param string $user
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setAffectedUser($user);

	/**
	 * Set the author of the activity
	 *
	 * @param string $author
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setAuthor($author);

	/**
	 * Set the author of the activity
	 *
	 * @param int $timestamp
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setTimestamp($timestamp);

	/**
	 * Set the subject of the activity
	 *
	 * @param string $subject
	 * @param array $parameters
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setSubject($subject, array $parameters = []);

	/**
	 * Set the message of the activity
	 *
	 * @param string $message
	 * @param array $parameters
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setMessage($message, array $parameters = []);

	/**
	 * Set the object of the activity
	 *
	 * @param string $objectType
	 * @param int $objectId
	 * @param string $objectName
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setObject($objectType, $objectId, $objectName = '');

	/**
	 * Set the link of the activity
	 *
	 * @param string $link
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function setLink($link);

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getApp();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getType();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getAffectedUser();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getAuthor();

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getTimestamp();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getSubject();

	/**
	 * @return array
	 * @since 8.2.0
	 */
	public function getSubjectParameters();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getMessage();

	/**
	 * @return array
	 * @since 8.2.0
	 */
	public function getMessageParameters();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectType();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectId();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getObjectName();

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getLink();
}
