<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV\Activity;


use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Class Backend
 *
 * @package OCA\DAV\CalDAV\Activity
 */
class Backend {

	/** @var CalDavBackend */
	protected  $calDavBackend;

	/** @var IActivityManager */
	protected $activityManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
	protected $userSession;

	/**
	 * @param CalDavBackend $calDavBackend
	 * @param IActivityManager $activityManager
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 */
	public function __construct(CalDavBackend $calDavBackend, IActivityManager $activityManager, IGroupManager $groupManager, IUserSession $userSession) {
		$this->calDavBackend = $calDavBackend;
		$this->activityManager = $activityManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
	}

	/**
	 * Creates activities when a calendar was creates
	 *
	 * @param int $calendarId
	 * @param array $properties
	 */
	public function addCalendar($calendarId, array $properties) {
		$this->triggerActivity(Extension::SUBJECT_ADD, $calendarId, $properties);
	}

	/**
	 * Creates activities when a calendar was updated
	 *
	 * @param int $calendarId
	 * @param array $properties
	 */
	public function updateCalendar($calendarId, array $properties) {
		$this->triggerActivity(Extension::SUBJECT_UPDATE, $calendarId, $properties);
	}

	/**
	 * Creates activities when a calendar was deleted
	 *
	 * @param int $calendarId
	 */
	public function deleteCalendar($calendarId) {
		$this->triggerActivity(Extension::SUBJECT_DELETE, $calendarId);
	}


	/**
	 * Creates activities for all related users when a calendar was touched
	 *
	 * @param string $action
	 * @param int $calendarId
	 * @param array $changedProperties
	 */
	protected function triggerActivity($action, $calendarId, array $changedProperties = []) {
		$properties = $this->calDavBackend->getCalendarById($calendarId);
		if (!isset($properties['principaluri'])) {
			return;
		}

		$principal = explode('/', $properties['principaluri']);
		$owner = $principal[2];

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject(Extension::CALENDAR, $calendarId)
			->setType(Extension::CALENDAR)
			->setAuthor($currentUser);

		$changedVisibleInformation = array_intersect([
			'{DAV:}displayname',
			'{http://apple.com/ns/ical/}calendar-color'
		], array_keys($changedProperties));

		if ($action === Extension::SUBJECT_UPDATE && empty($changedVisibleInformation)) {
			$users = [$owner];
		} else {
			$users = $this->getUsersForCalendar($calendarId);
			$users[] = $owner;
		}

		foreach ($users as $user) {
			$event->setAffectedUser($user)
				->setSubject(
					$user === $currentUser ? $action . '_self' : $action,
					[
						$currentUser,
						$properties['{DAV:}displayname'],
					]
				);
			$this->activityManager->publish($event);
		}
	}

	/**
	 * Creates activities for all related users when a calendar was (un-)shared
	 *
	 * @param Calendar $calendar
	 * @param array $add
	 * @param array $remove
	 */
	public function updateCalendarShares(Calendar $calendar, array $add, array $remove) {
		$calendarId = $calendar->getResourceId();
		$shares = $this->calDavBackend->getShares($calendarId);

		$properties = $this->calDavBackend->getCalendarById($calendarId);
		$principal = explode('/', $properties['principaluri']);
		$owner = $principal[2];

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject(Extension::CALENDAR, $calendarId)
			->setType(Extension::CALENDAR)
			->setAuthor($currentUser);

		foreach ($remove as $principal) {
			// principal:principals/users/test
			$parts = explode(':', $principal, 2);
			if ($parts[0] !== 'principal') {
				continue;
			}
			$principal = explode('/', $parts[1]);

			if ($principal[1] === 'users') {
				$this->triggerActivityUser(
					$principal[2],
					$event,
					$properties,
					Extension::SUBJECT_UNSHARE_USER,
					Extension::SUBJECT_DELETE . '_self'
				);

				if ($owner !== $principal[2]) {
					$parameters = [
						$principal[2],
						$properties['{DAV:}displayname'],
					];

					if ($owner === $event->getAuthor()) {
						$subject = Extension::SUBJECT_UNSHARE_USER . '_you';
					} else if ($principal[2] === $event->getAuthor()) {
						$subject = Extension::SUBJECT_UNSHARE_USER . '_self';
					} else {
						$event->setAffectedUser($event->getAuthor())
							->setSubject(Extension::SUBJECT_UNSHARE_USER . '_you', $parameters);
						$this->activityManager->publish($event);

						$subject = Extension::SUBJECT_UNSHARE_USER . '_by';
						$parameters[] = $event->getAuthor();
					}

					$event->setAffectedUser($owner)
						->setSubject($subject, $parameters);
					$this->activityManager->publish($event);
				}
			} else if ($principal[1] === 'groups') {
				$this->triggerActivityGroup($principal[2], $event, $properties, Extension::SUBJECT_UNSHARE_USER);

				$parameters = [
					$principal[2],
					$properties['{DAV:}displayname'],
				];

				if ($owner === $event->getAuthor()) {
					$subject = Extension::SUBJECT_UNSHARE_GROUP . '_you';
				} else {
					$event->setAffectedUser($event->getAuthor())
						->setSubject(Extension::SUBJECT_UNSHARE_GROUP . '_you', $parameters);
					$this->activityManager->publish($event);

					$subject = Extension::SUBJECT_UNSHARE_GROUP . '_by';
					$parameters[] = $event->getAuthor();
				}

				$event->setAffectedUser($owner)
					->setSubject($subject, $parameters);
				$this->activityManager->publish($event);
			}
		}

		foreach ($add as $share) {
			if ($this->isAlreadyShared($share['href'], $shares)) {
				continue;
			}

			// principal:principals/users/test
			$parts = explode(':', $share['href'], 2);
			if ($parts[0] !== 'principal') {
				continue;
			}
			$principal = explode('/', $parts[1]);

			if ($principal[1] === 'users') {
				$this->triggerActivityUser($principal[2], $event, $properties, Extension::SUBJECT_SHARE_USER);

				if ($owner !== $principal[2]) {
					$parameters = [
						$principal[2],
						$properties['{DAV:}displayname'],
					];

					if ($owner === $event->getAuthor()) {
						$subject = Extension::SUBJECT_SHARE_USER . '_you';
					} else {
						$event->setAffectedUser($event->getAuthor())
							->setSubject(Extension::SUBJECT_SHARE_USER . '_you', $parameters);
						$this->activityManager->publish($event);

						$subject = Extension::SUBJECT_SHARE_USER . '_by';
						$parameters[] = $event->getAuthor();
					}

					$event->setAffectedUser($owner)
						->setSubject($subject, $parameters);
					$this->activityManager->publish($event);
				}
			} else if ($principal[1] === 'groups') {
				$this->triggerActivityGroup($principal[2], $event, $properties, Extension::SUBJECT_SHARE_USER);

				$parameters = [
					$principal[2],
					$properties['{DAV:}displayname'],
				];

				if ($owner === $event->getAuthor()) {
					$subject = Extension::SUBJECT_SHARE_GROUP . '_you';
				} else {
					$event->setAffectedUser($event->getAuthor())
						->setSubject(Extension::SUBJECT_SHARE_GROUP . '_you', $parameters);
					$this->activityManager->publish($event);

					$subject = Extension::SUBJECT_SHARE_GROUP . '_by';
					$parameters[] = $event->getAuthor();
				}

				$event->setAffectedUser($owner)
					->setSubject($subject, $parameters);
				$this->activityManager->publish($event);
			}
		}
	}

	/**
	 * Checks if a calendar is already shared with a principal
	 *
	 * @param string $principal
	 * @param array[] $shares
	 * @return bool
	 */
	protected function isAlreadyShared($principal, $shares) {
		foreach ($shares as $share) {
			if ($principal === $share['href']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Creates the given activity for all members of the given group
	 *
	 * @param string $gid
	 * @param IEvent $event
	 * @param array $properties
	 * @param string $subject
	 */
	protected function triggerActivityGroup($gid, IEvent $event, array $properties, $subject) {
		$group = $this->groupManager->get($gid);

		if ($group instanceof IGroup) {
			foreach ($group->getUsers() as $user) {
				// Exclude current user
				if ($user->getUID() !== $event->getAuthor()) {
					$this->triggerActivityUser($user->getUID(), $event, $properties, $subject);
				}
			}
		}
	}

	/**
	 * Creates the given activity for the given user
	 *
	 * @param string $user
	 * @param IEvent $event
	 * @param array $properties
	 * @param string $subject
	 * @param string $subjectSelf
	 */
	protected function triggerActivityUser($user, IEvent $event, array $properties, $subject, $subjectSelf = '') {
		$event->setAffectedUser($user)
			->setSubject(
				$user === $event->getAuthor() && $subjectSelf ? $subjectSelf : $subject,
				[
					$event->getAuthor(),
					$properties['{DAV:}displayname'],
				]
			);

		$this->activityManager->publish($event);
	}

	/**
	 * Get all users that have access to a given calendar
	 *
	 * @param int $calendarId
	 * @return string[]
	 */
	protected function getUsersForCalendar($calendarId) {
		$users = $groups = [];
		$shares = $this->calDavBackend->getShares($calendarId);
		foreach ($shares as $share) {
			$prinical = explode('/', $share['{http://owncloud.org/ns}principal']);
			if ($prinical[1] === 'users') {
				$users[] = $prinical[2];
			} else if ($prinical[1] === 'groups') {
				$groups[] = $prinical[2];
			}
		}

		if (!empty($groups)) {
			foreach ($groups as $gid) {
				$group = $this->groupManager->get($gid);
				if ($group instanceof IGroup) {
					foreach ($group->getUsers() as $user) {
						$users[] = $user->getUID();
					}
				}
			}
		}

		return $users;
	}
}
