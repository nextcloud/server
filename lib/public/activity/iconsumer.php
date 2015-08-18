<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

/**
 * Public interface of ownCloud for apps to use.
 * Activity/IConsumer interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Activity;

/**
 * Interface IConsumer
 *
 * @package OCP\Activity
 * @since 6.0.0
 */
interface IConsumer {
	/**
	 * @param string $app           The app where this event is associated with
	 * @param string $subject       A short description of the event
	 * @param array  $subjectParams Array with parameters that are filled in the subject
	 * @param string $message       A longer description of the event
	 * @param array  $messageParams Array with parameters that are filled in the message
	 * @param string $file          The file including path where this event is associated with
	 * @param string $link          A link where this event is associated with
	 * @param string $affectedUser  Recipient of the notification
	 * @param string $type          Type of the notification
	 * @param string $objectType    Object type can be used to filter the activities later (e.g. files)
	 * @param int    $objectId      Object id can be used to filter the activities later (e.g. the ID of the cache entry)
	 * @return null
	 * @since 6.0.0
	 * @since 8.2.0 Added $objectType and $objectId
	 */
	public function receive($app, $subject, $subjectParams, $message, $messageParams, $file, $link, $affectedUser, $type, $objectType, $objectId);
}

