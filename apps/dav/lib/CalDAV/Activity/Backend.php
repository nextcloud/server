<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV\Activity;

use OCA\DAV\CalDAV\Activity\Provider\Calendar;
use OCA\DAV\CalDAV\Activity\Provider\Event;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Sabre\VObject\Reader;

/**
 * Class Backend
 *
 * @package OCA\DAV\CalDAV\Activity
 */
class Backend {

	/** @var IActivityManager */
	protected $activityManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var IAppManager */
	protected $appManager;

	/** @var IUserManager */
	protected $userManager;

	public function __construct(IActivityManager $activityManager, IGroupManager $groupManager, IUserSession $userSession, IAppManager $appManager, IUserManager $userManager) {
		$this->activityManager = $activityManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
		$this->userManager = $userManager;
	}

	/**
	 * Creates activities when a calendar was creates
	 *
	 * @param array $calendarData
	 */
	public function onCalendarAdd(array $calendarData) {
		$this->triggerCalendarActivity(Calendar::SUBJECT_ADD, $calendarData);
	}

	/**
	 * Creates activities when a calendar was updated
	 *
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $properties
	 */
	public function onCalendarUpdate(array $calendarData, array $shares, array $properties) {
		$this->triggerCalendarActivity(Calendar::SUBJECT_UPDATE, $calendarData, $shares, $properties);
	}

	/**
	 * Creates activities when a calendar was moved to trash
	 *
	 * @param array $calendarData
	 * @param array $shares
	 */
	public function onCalendarMovedToTrash(array $calendarData, array $shares): void {
		$this->triggerCalendarActivity(Calendar::SUBJECT_MOVE_TO_TRASH, $calendarData, $shares);
	}

	/**
	 * Creates activities when a calendar was restored
	 *
	 * @param array $calendarData
	 * @param array $shares
	 */
	public function onCalendarRestored(array $calendarData, array $shares): void {
		$this->triggerCalendarActivity(Calendar::SUBJECT_RESTORE, $calendarData, $shares);
	}

	/**
	 * Creates activities when a calendar was deleted
	 *
	 * @param array $calendarData
	 * @param array $shares
	 */
	public function onCalendarDelete(array $calendarData, array $shares): void {
		$this->triggerCalendarActivity(Calendar::SUBJECT_DELETE, $calendarData, $shares);
	}

	/**
	 * Creates activities when a calendar was (un)published
	 *
	 * @param array $calendarData
	 * @param bool $publishStatus
	 */
	public function onCalendarPublication(array $calendarData, bool $publishStatus): void {
		$this->triggerCalendarActivity($publishStatus ? Calendar::SUBJECT_PUBLISH : Calendar::SUBJECT_UNPUBLISH, $calendarData);
	}

	/**
	 * Creates activities for all related users when a calendar was touched
	 *
	 * @param string $action
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $changedProperties
	 */
	protected function triggerCalendarActivity($action, array $calendarData, array $shares = [], array $changedProperties = []) {
		if (!isset($calendarData['principaluri'])) {
			return;
		}

		$principal = explode('/', $calendarData['principaluri']);
		$owner = array_pop($principal);

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('calendar', (int) $calendarData['id'])
			->setType('calendar')
			->setAuthor($currentUser);

		$changedVisibleInformation = array_intersect([
			'{DAV:}displayname',
			'{http://apple.com/ns/ical/}calendar-color'
		], array_keys($changedProperties));

		if (empty($shares) || ($action === Calendar::SUBJECT_UPDATE && empty($changedVisibleInformation))) {
			$users = [$owner];
		} else {
			$users = $this->getUsersForShares($shares);
			$users[] = $owner;
		}

		foreach ($users as $user) {
			if ($action === Calendar::SUBJECT_DELETE && !$this->userManager->userExists($user)) {
				// Avoid creating calendar_delete activities for deleted users
				continue;
			}

			$event->setAffectedUser($user)
				->setSubject(
					$user === $currentUser ? $action . '_self' : $action,
					[
						'actor' => $currentUser,
						'calendar' => [
							'id' => (int) $calendarData['id'],
							'uri' => $calendarData['uri'],
							'name' => $calendarData['{DAV:}displayname'],
						],
					]
				);
			$this->activityManager->publish($event);
		}
	}

	/**
	 * Creates activities for all related users when a calendar was (un-)shared
	 *
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $add
	 * @param array $remove
	 */
	public function onCalendarUpdateShares(array $calendarData, array $shares, array $add, array $remove) {
		$principal = explode('/', $calendarData['principaluri']);
		$owner = $principal[2];

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('calendar', (int) $calendarData['id'])
			->setType('calendar')
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
					$calendarData,
					Calendar::SUBJECT_UNSHARE_USER,
					Calendar::SUBJECT_DELETE . '_self'
				);

				if ($owner !== $principal[2]) {
					$parameters = [
						'actor' => $event->getAuthor(),
						'calendar' => [
							'id' => (int) $calendarData['id'],
							'uri' => $calendarData['uri'],
							'name' => $calendarData['{DAV:}displayname'],
						],
						'user' => $principal[2],
					];

					if ($owner === $event->getAuthor()) {
						$subject = Calendar::SUBJECT_UNSHARE_USER . '_you';
					} elseif ($principal[2] === $event->getAuthor()) {
						$subject = Calendar::SUBJECT_UNSHARE_USER . '_self';
					} else {
						$event->setAffectedUser($event->getAuthor())
							->setSubject(Calendar::SUBJECT_UNSHARE_USER . '_you', $parameters);
						$this->activityManager->publish($event);

						$subject = Calendar::SUBJECT_UNSHARE_USER . '_by';
					}

					$event->setAffectedUser($owner)
						->setSubject($subject, $parameters);
					$this->activityManager->publish($event);
				}
			} elseif ($principal[1] === 'groups') {
				$this->triggerActivityGroup($principal[2], $event, $calendarData, Calendar::SUBJECT_UNSHARE_USER);

				$parameters = [
					'actor' => $event->getAuthor(),
					'calendar' => [
						'id' => (int) $calendarData['id'],
						'uri' => $calendarData['uri'],
						'name' => $calendarData['{DAV:}displayname'],
					],
					'group' => $principal[2],
				];

				if ($owner === $event->getAuthor()) {
					$subject = Calendar::SUBJECT_UNSHARE_GROUP . '_you';
				} else {
					$event->setAffectedUser($event->getAuthor())
						->setSubject(Calendar::SUBJECT_UNSHARE_GROUP . '_you', $parameters);
					$this->activityManager->publish($event);

					$subject = Calendar::SUBJECT_UNSHARE_GROUP . '_by';
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
				$this->triggerActivityUser($principal[2], $event, $calendarData, Calendar::SUBJECT_SHARE_USER);

				if ($owner !== $principal[2]) {
					$parameters = [
						'actor' => $event->getAuthor(),
						'calendar' => [
							'id' => (int) $calendarData['id'],
							'uri' => $calendarData['uri'],
							'name' => $calendarData['{DAV:}displayname'],
						],
						'user' => $principal[2],
					];

					if ($owner === $event->getAuthor()) {
						$subject = Calendar::SUBJECT_SHARE_USER . '_you';
					} else {
						$event->setAffectedUser($event->getAuthor())
							->setSubject(Calendar::SUBJECT_SHARE_USER . '_you', $parameters);
						$this->activityManager->publish($event);

						$subject = Calendar::SUBJECT_SHARE_USER . '_by';
					}

					$event->setAffectedUser($owner)
						->setSubject($subject, $parameters);
					$this->activityManager->publish($event);
				}
			} elseif ($principal[1] === 'groups') {
				$this->triggerActivityGroup($principal[2], $event, $calendarData, Calendar::SUBJECT_SHARE_USER);

				$parameters = [
					'actor' => $event->getAuthor(),
					'calendar' => [
						'id' => (int) $calendarData['id'],
						'uri' => $calendarData['uri'],
						'name' => $calendarData['{DAV:}displayname'],
					],
					'group' => $principal[2],
				];

				if ($owner === $event->getAuthor()) {
					$subject = Calendar::SUBJECT_SHARE_GROUP . '_you';
				} else {
					$event->setAffectedUser($event->getAuthor())
						->setSubject(Calendar::SUBJECT_SHARE_GROUP . '_you', $parameters);
					$this->activityManager->publish($event);

					$subject = Calendar::SUBJECT_SHARE_GROUP . '_by';
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
					'actor' => $event->getAuthor(),
					'calendar' => [
						'id' => (int) $properties['id'],
						'uri' => $properties['uri'],
						'name' => $properties['{DAV:}displayname'],
					],
				]
			);

		$this->activityManager->publish($event);
	}

	/**
	 * Creates activities when a calendar object was created/updated/deleted
	 *
	 * @param string $action
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 */
	public function onTouchCalendarObject($action, array $calendarData, array $shares, array $objectData) {
		if (!isset($calendarData['principaluri'])) {
			return;
		}

		$principal = explode('/', $calendarData['principaluri']);
		$owner = array_pop($principal);

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$classification = $objectData['classification'] ?? CalDavBackend::CLASSIFICATION_PUBLIC;
		$object = $this->getObjectNameAndType($objectData);

		if (!$object) {
			return;
		}

		$action = $action . '_' . $object['type'];

		if ($object['type'] === 'todo' && str_starts_with($action, Event::SUBJECT_OBJECT_UPDATE) && $object['status'] === 'COMPLETED') {
			$action .= '_completed';
		} elseif ($object['type'] === 'todo' && str_starts_with($action, Event::SUBJECT_OBJECT_UPDATE) && $object['status'] === 'NEEDS-ACTION') {
			$action .= '_needs_action';
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('calendar', (int) $calendarData['id'])
			->setType($object['type'] === 'event' ? 'calendar_event' : 'calendar_todo')
			->setAuthor($currentUser);

		$users = $this->getUsersForShares($shares);
		$users[] = $owner;

		// Users for share can return the owner itself if the calendar is published
		foreach (array_unique($users) as $user) {
			if ($classification === CalDavBackend::CLASSIFICATION_PRIVATE && $user !== $owner) {
				// Private events are only shown to the owner
				continue;
			}

			$params = [
				'actor' => $event->getAuthor(),
				'calendar' => [
					'id' => (int) $calendarData['id'],
					'uri' => $calendarData['uri'],
					'name' => $calendarData['{DAV:}displayname'],
				],
				'object' => [
					'id' => $object['id'],
					'name' => $classification === CalDavBackend::CLASSIFICATION_CONFIDENTIAL && $user !== $owner ? 'Busy' : $object['name'],
					'classified' => $classification === CalDavBackend::CLASSIFICATION_CONFIDENTIAL && $user !== $owner,
				],
			];

			if ($object['type'] === 'event' && !str_contains($action, Event::SUBJECT_OBJECT_DELETE) && $this->appManager->isEnabledForUser('calendar')) {
				$params['object']['link']['object_uri'] = $objectData['uri'];
				$params['object']['link']['calendar_uri'] = $calendarData['uri'];
				$params['object']['link']['owner'] = $owner;
			}


			$event->setAffectedUser($user)
				->setSubject(
					$user === $currentUser ? $action . '_self' : $action,
					$params
				);

			$this->activityManager->publish($event);
		}
	}

	/**
	 * Creates activities when a calendar object was moved
	 */
	public function onMovedCalendarObject(array $sourceCalendarData, array $targetCalendarData, array $sourceShares, array $targetShares, array $objectData): void {
		if (!isset($targetCalendarData['principaluri'])) {
			return;
		}

		$sourcePrincipal = explode('/', $sourceCalendarData['principaluri']);
		$sourceOwner = array_pop($sourcePrincipal);

		$targetPrincipal = explode('/', $targetCalendarData['principaluri']);
		$targetOwner = array_pop($targetPrincipal);

		if ($sourceOwner !== $targetOwner) {
			$this->onTouchCalendarObject(
				Event::SUBJECT_OBJECT_DELETE,
				$sourceCalendarData,
				$sourceShares,
				$objectData
			);
			$this->onTouchCalendarObject(
				Event::SUBJECT_OBJECT_ADD,
				$targetCalendarData,
				$targetShares,
				$objectData
			);
			return;
		}

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $targetOwner;
		}

		$classification = $objectData['classification'] ?? CalDavBackend::CLASSIFICATION_PUBLIC;
		$object = $this->getObjectNameAndType($objectData);

		if (!$object) {
			return;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('calendar', (int) $targetCalendarData['id'])
			->setType($object['type'] === 'event' ? 'calendar_event' : 'calendar_todo')
			->setAuthor($currentUser);

		$users = $this->getUsersForShares(array_intersect($sourceShares, $targetShares));
		$users[] = $targetOwner;

		// Users for share can return the owner itself if the calendar is published
		foreach (array_unique($users) as $user) {
			if ($classification === CalDavBackend::CLASSIFICATION_PRIVATE && $user !== $targetOwner) {
				// Private events are only shown to the owner
				continue;
			}

			$params = [
				'actor' => $event->getAuthor(),
				'sourceCalendar' => [
					'id' => (int) $sourceCalendarData['id'],
					'uri' => $sourceCalendarData['uri'],
					'name' => $sourceCalendarData['{DAV:}displayname'],
				],
				'targetCalendar' => [
					'id' => (int) $targetCalendarData['id'],
					'uri' => $targetCalendarData['uri'],
					'name' => $targetCalendarData['{DAV:}displayname'],
				],
				'object' => [
					'id' => $object['id'],
					'name' => $classification === CalDavBackend::CLASSIFICATION_CONFIDENTIAL && $user !== $targetOwner ? 'Busy' : $object['name'],
					'classified' => $classification === CalDavBackend::CLASSIFICATION_CONFIDENTIAL && $user !== $targetOwner,
				],
			];

			if ($object['type'] === 'event' && $this->appManager->isEnabledForUser('calendar')) {
				$params['object']['link']['object_uri'] = $objectData['uri'];
				$params['object']['link']['calendar_uri'] = $targetCalendarData['uri'];
				$params['object']['link']['owner'] = $targetOwner;
			}

			$event->setAffectedUser($user)
				->setSubject(
					$user === $currentUser ? Event::SUBJECT_OBJECT_MOVE . '_' . $object['type'] . '_self' : Event::SUBJECT_OBJECT_MOVE . '_' . $object['type'],
					$params
				);

			$this->activityManager->publish($event);
		}
	}

	/**
	 * @param array $objectData
	 * @return string[]|false
	 */
	protected function getObjectNameAndType(array $objectData) {
		$vObject = Reader::read($objectData['calendardata']);
		$component = $componentType = null;
		foreach ($vObject->getComponents() as $component) {
			if (in_array($component->name, ['VEVENT', 'VTODO'])) {
				$componentType = $component->name;
				break;
			}
		}

		if (!$componentType) {
			// Calendar objects must have a VEVENT or VTODO component
			return false;
		}

		if ($componentType === 'VEVENT') {
			return ['id' => (string) $component->UID, 'name' => (string) $component->SUMMARY, 'type' => 'event'];
		}
		return ['id' => (string) $component->UID, 'name' => (string) $component->SUMMARY, 'type' => 'todo', 'status' => (string) $component->STATUS];
	}

	/**
	 * Get all users that have access to a given calendar
	 *
	 * @param array $shares
	 * @return string[]
	 */
	protected function getUsersForShares(array $shares) {
		$users = $groups = [];
		foreach ($shares as $share) {
			$principal = explode('/', $share['{http://owncloud.org/ns}principal']);
			if ($principal[1] === 'users') {
				$users[] = $principal[2];
			} elseif ($principal[1] === 'groups') {
				$groups[] = $principal[2];
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

		return array_unique($users);
	}
}
