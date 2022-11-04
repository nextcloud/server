<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\DAV\CardDAV\Activity;

use OCA\DAV\CardDAV\Activity\Provider\Addressbook;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Sabre\CardDAV\Plugin;
use Sabre\VObject\Reader;

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

	public function __construct(IActivityManager $activityManager,
								IGroupManager $groupManager,
								IUserSession $userSession,
								IAppManager $appManager,
								IUserManager $userManager) {
		$this->activityManager = $activityManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
		$this->userManager = $userManager;
	}

	/**
	 * Creates activities when an addressbook was creates
	 *
	 * @param array $addressbookData
	 */
	public function onAddressbookCreate(array $addressbookData): void {
		$this->triggerAddressbookActivity(Addressbook::SUBJECT_ADD, $addressbookData);
	}

	/**
	 * Creates activities when a calendar was updated
	 *
	 * @param array $addressbookData
	 * @param array $shares
	 * @param array $properties
	 */
	public function onAddressbookUpdate(array $addressbookData, array $shares, array $properties): void {
		$this->triggerAddressbookActivity(Addressbook::SUBJECT_UPDATE, $addressbookData, $shares, $properties);
	}

	/**
	 * Creates activities when a calendar was deleted
	 *
	 * @param array $addressbookData
	 * @param array $shares
	 */
	public function onAddressbookDelete(array $addressbookData, array $shares): void {
		$this->triggerAddressbookActivity(Addressbook::SUBJECT_DELETE, $addressbookData, $shares);
	}

	/**
	 * Creates activities for all related users when a calendar was touched
	 *
	 * @param string $action
	 * @param array $addressbookData
	 * @param array $shares
	 * @param array $changedProperties
	 */
	protected function triggerAddressbookActivity(string $action, array $addressbookData, array $shares = [], array $changedProperties = []): void {
		if (!isset($addressbookData['principaluri'])) {
			return;
		}

		$principalUri = $addressbookData['principaluri'];

		// We are not interested in changes from the system addressbook
		if ($principalUri === 'principals/system/system') {
			return;
		}

		$principal = explode('/', $principalUri);
		$owner = array_pop($principal);

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('addressbook', (int) $addressbookData['id'])
			->setType('contacts')
			->setAuthor($currentUser);

		$changedVisibleInformation = array_intersect([
			'{DAV:}displayname',
			'{' . Plugin::NS_CARDDAV . '}addressbook-description',
		], array_keys($changedProperties));

		if (empty($shares) || ($action === Addressbook::SUBJECT_UPDATE && empty($changedVisibleInformation))) {
			$users = [$owner];
		} else {
			$users = $this->getUsersForShares($shares);
			$users[] = $owner;
		}

		foreach ($users as $user) {
			if ($action === Addressbook::SUBJECT_DELETE && !$this->userManager->userExists($user)) {
				// Avoid creating addressbook_delete activities for deleted users
				continue;
			}

			$event->setAffectedUser($user)
				->setSubject(
					$user === $currentUser ? $action . '_self' : $action,
					[
						'actor' => $currentUser,
						'addressbook' => [
							'id' => (int) $addressbookData['id'],
							'uri' => $addressbookData['uri'],
							'name' => $addressbookData['{DAV:}displayname'],
						],
					]
				);
			$this->activityManager->publish($event);
		}
	}

	/**
	 * Creates activities for all related users when an addressbook was (un-)shared
	 *
	 * @param array $addressbookData
	 * @param array $shares
	 * @param array $add
	 * @param array $remove
	 */
	public function onAddressbookUpdateShares(array $addressbookData, array $shares, array $add, array $remove): void {
		$principal = explode('/', $addressbookData['principaluri']);
		$owner = $principal[2];

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('addressbook', (int) $addressbookData['id'])
			->setType('contacts')
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
					$addressbookData,
					Addressbook::SUBJECT_UNSHARE_USER,
					Addressbook::SUBJECT_DELETE . '_self'
				);

				if ($owner !== $principal[2]) {
					$parameters = [
						'actor' => $event->getAuthor(),
						'addressbook' => [
							'id' => (int) $addressbookData['id'],
							'uri' => $addressbookData['uri'],
							'name' => $addressbookData['{DAV:}displayname'],
						],
						'user' => $principal[2],
					];

					if ($owner === $event->getAuthor()) {
						$subject = Addressbook::SUBJECT_UNSHARE_USER . '_you';
					} elseif ($principal[2] === $event->getAuthor()) {
						$subject = Addressbook::SUBJECT_UNSHARE_USER . '_self';
					} else {
						$event->setAffectedUser($event->getAuthor())
							->setSubject(Addressbook::SUBJECT_UNSHARE_USER . '_you', $parameters);
						$this->activityManager->publish($event);

						$subject = Addressbook::SUBJECT_UNSHARE_USER . '_by';
					}

					$event->setAffectedUser($owner)
						->setSubject($subject, $parameters);
					$this->activityManager->publish($event);
				}
			} elseif ($principal[1] === 'groups') {
				$this->triggerActivityGroup($principal[2], $event, $addressbookData, Addressbook::SUBJECT_UNSHARE_USER);

				$parameters = [
					'actor' => $event->getAuthor(),
					'addressbook' => [
						'id' => (int) $addressbookData['id'],
						'uri' => $addressbookData['uri'],
						'name' => $addressbookData['{DAV:}displayname'],
					],
					'group' => $principal[2],
				];

				if ($owner === $event->getAuthor()) {
					$subject = Addressbook::SUBJECT_UNSHARE_GROUP . '_you';
				} else {
					$event->setAffectedUser($event->getAuthor())
						->setSubject(Addressbook::SUBJECT_UNSHARE_GROUP . '_you', $parameters);
					$this->activityManager->publish($event);

					$subject = Addressbook::SUBJECT_UNSHARE_GROUP . '_by';
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
				$this->triggerActivityUser($principal[2], $event, $addressbookData, Addressbook::SUBJECT_SHARE_USER);

				if ($owner !== $principal[2]) {
					$parameters = [
						'actor' => $event->getAuthor(),
						'addressbook' => [
							'id' => (int) $addressbookData['id'],
							'uri' => $addressbookData['uri'],
							'name' => $addressbookData['{DAV:}displayname'],
						],
						'user' => $principal[2],
					];

					if ($owner === $event->getAuthor()) {
						$subject = Addressbook::SUBJECT_SHARE_USER . '_you';
					} else {
						$event->setAffectedUser($event->getAuthor())
							->setSubject(Addressbook::SUBJECT_SHARE_USER . '_you', $parameters);
						$this->activityManager->publish($event);

						$subject = Addressbook::SUBJECT_SHARE_USER . '_by';
					}

					$event->setAffectedUser($owner)
						->setSubject($subject, $parameters);
					$this->activityManager->publish($event);
				}
			} elseif ($principal[1] === 'groups') {
				$this->triggerActivityGroup($principal[2], $event, $addressbookData, Addressbook::SUBJECT_SHARE_USER);

				$parameters = [
					'actor' => $event->getAuthor(),
					'addressbook' => [
						'id' => (int) $addressbookData['id'],
						'uri' => $addressbookData['uri'],
						'name' => $addressbookData['{DAV:}displayname'],
					],
					'group' => $principal[2],
				];

				if ($owner === $event->getAuthor()) {
					$subject = Addressbook::SUBJECT_SHARE_GROUP . '_you';
				} else {
					$event->setAffectedUser($event->getAuthor())
						->setSubject(Addressbook::SUBJECT_SHARE_GROUP . '_you', $parameters);
					$this->activityManager->publish($event);

					$subject = Addressbook::SUBJECT_SHARE_GROUP . '_by';
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
	protected function isAlreadyShared(string $principal, array $shares): bool {
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
	protected function triggerActivityGroup(string $gid, IEvent $event, array $properties, string $subject): void {
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
	protected function triggerActivityUser(string $user, IEvent $event, array $properties, string $subject, string $subjectSelf = ''): void {
		$event->setAffectedUser($user)
			->setSubject(
				$user === $event->getAuthor() && $subjectSelf ? $subjectSelf : $subject,
				[
					'actor' => $event->getAuthor(),
					'addressbook' => [
						'id' => (int) $properties['id'],
						'uri' => $properties['uri'],
						'name' => $properties['{DAV:}displayname'],
					],
				]
			);

		$this->activityManager->publish($event);
	}

	/**
	 * Creates activities when a card was created/updated/deleted
	 *
	 * @param string $action
	 * @param array $addressbookData
	 * @param array $shares
	 * @param array $cardData
	 */
	public function triggerCardActivity(string $action, array $addressbookData, array $shares, array $cardData): void {
		if (!isset($addressbookData['principaluri'])) {
			return;
		}

		$principalUri = $addressbookData['principaluri'];

		// We are not interested in changes from the system addressbook
		if ($principalUri === 'principals/system/system') {
			return;
		}

		$principal = explode('/', $principalUri);
		$owner = array_pop($principal);

		$currentUser = $this->userSession->getUser();
		if ($currentUser instanceof IUser) {
			$currentUser = $currentUser->getUID();
		} else {
			$currentUser = $owner;
		}

		$card = $this->getCardNameAndId($cardData);

		$event = $this->activityManager->generateEvent();
		$event->setApp('dav')
			->setObject('addressbook', (int) $addressbookData['id'])
			->setType('contacts')
			->setAuthor($currentUser);

		$users = $this->getUsersForShares($shares);
		$users[] = $owner;

		// Users for share can return the owner itself if the calendar is published
		foreach (array_unique($users) as $user) {
			$params = [
				'actor' => $event->getAuthor(),
				'addressbook' => [
					'id' => (int) $addressbookData['id'],
					'uri' => $addressbookData['uri'],
					'name' => $addressbookData['{DAV:}displayname'],
				],
				'card' => [
					'id' => $card['id'],
					'name' => $card['name'],
				],
			];


			$event->setAffectedUser($user)
				->setSubject(
					$user === $currentUser ? $action . '_self' : $action,
					$params
				);

			$this->activityManager->publish($event);
		}
	}

	/**
	 * @param array $cardData
	 * @return string[]
	 */
	protected function getCardNameAndId(array $cardData): array {
		$vObject = Reader::read($cardData['carddata']);
		return ['id' => (string) $vObject->UID, 'name' => (string) ($vObject->FN ?? '')];
	}

	/**
	 * Get all users that have access to a given calendar
	 *
	 * @param array $shares
	 * @return string[]
	 */
	protected function getUsersForShares(array $shares): array {
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
