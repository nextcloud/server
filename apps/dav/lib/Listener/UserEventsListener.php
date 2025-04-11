<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Service\DefaultContactService;
use OCP\Defaults;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\BeforeUserIdUnassignedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserFirstTimeLoggedInEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<UserFirstTimeLoggedInEvent|UserIdAssignedEvent|BeforeUserIdUnassignedEvent|UserIdUnassignedEvent|BeforeUserDeletedEvent|UserDeletedEvent|UserCreatedEvent|UserChangedEvent> */
class UserEventsListener implements IEventListener {

	/** @var IUser[] */
	private array $usersToDelete = [];

	private array $calendarsToDelete = [];
	private array $subscriptionsToDelete = [];
	private array $addressBooksToDelete = [];

	public function __construct(
		private IUserManager $userManager,
		private SyncService $syncService,
		private CalDavBackend $calDav,
		private CardDavBackend $cardDav,
		private Defaults $themingDefaults,
		private DefaultContactService $defaultContactService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof UserCreatedEvent) {
			$this->postCreateUser($event->getUser());
		} elseif ($event instanceof UserIdAssignedEvent) {
			$user = $this->userManager->get($event->getUserId());
			if ($user !== null) {
				$this->postCreateUser($user);
			}
		} elseif ($event instanceof BeforeUserDeletedEvent) {
			$this->preDeleteUser($event->getUser());
		} elseif ($event instanceof BeforeUserIdUnassignedEvent) {
			$this->preUnassignedUserId($event->getUserId());
		} elseif ($event instanceof UserDeletedEvent) {
			$this->postDeleteUser($event->getUid());
		} elseif ($event instanceof UserIdUnassignedEvent) {
			$this->postDeleteUser($event->getUserId());
		} elseif ($event instanceof UserChangedEvent) {
			$this->changeUser($event->getUser(), $event->getFeature());
		} elseif ($event instanceof UserFirstTimeLoggedInEvent) {
			$this->firstLogin($event->getUser());
		}
	}

	public function postCreateUser(IUser $user): void {
		$this->syncService->updateUser($user);
	}

	public function preDeleteUser(IUser $user): void {
		$uid = $user->getUID();
		$userPrincipalUri = 'principals/users/' . $uid;
		$this->usersToDelete[$uid] = $user;
		$this->calendarsToDelete[$uid] = $this->calDav->getUsersOwnCalendars($userPrincipalUri);
		$this->subscriptionsToDelete[$uid] = $this->calDav->getSubscriptionsForUser($userPrincipalUri);
		$this->addressBooksToDelete[$uid] = $this->cardDav->getUsersOwnAddressBooks($userPrincipalUri);
	}

	public function preUnassignedUserId(string $uid): void {
		$user = $this->userManager->get($uid);
		if ($user !== null) {
			$this->usersToDelete[$uid] = $user;
		}
	}

	public function postDeleteUser(string $uid): void {
		if (isset($this->usersToDelete[$uid])) {
			$this->syncService->deleteUser($this->usersToDelete[$uid]);
		}

		foreach ($this->calendarsToDelete[$uid] as $calendar) {
			$this->calDav->deleteCalendar(
				$calendar['id'],
				true // Make sure the data doesn't go into the trashbin, a new user with the same UID would later see it otherwise
			);
		}

		foreach ($this->subscriptionsToDelete[$uid] as $subscription) {
			$this->calDav->deleteSubscription(
				$subscription['id'],
			);
		}
		$this->calDav->deleteAllSharesByUser('principals/users/' . $uid);

		foreach ($this->addressBooksToDelete[$uid] as $addressBook) {
			$this->cardDav->deleteAddressBook($addressBook['id']);
		}

		unset($this->calendarsToDelete[$uid]);
		unset($this->subscriptionsToDelete[$uid]);
		unset($this->addressBooksToDelete[$uid]);
	}

	public function changeUser(IUser $user, string $feature): void {
		// This case is already covered by the account manager firing up a signal
		// later on
		if ($feature !== 'eMailAddress' && $feature !== 'displayName') {
			$this->syncService->updateUser($user);
		}
	}

	public function firstLogin(IUser $user): void {
		$principal = 'principals/users/' . $user->getUID();
		if ($this->calDav->getCalendarsForUserCount($principal) === 0) {
			try {
				$this->calDav->createCalendar($principal, CalDavBackend::PERSONAL_CALENDAR_URI, [
					'{DAV:}displayname' => CalDavBackend::PERSONAL_CALENDAR_NAME,
					'{http://apple.com/ns/ical/}calendar-color' => $this->themingDefaults->getColorPrimary(),
					'components' => 'VEVENT'
				]);
			} catch (\Exception $e) {
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
			}
		}
		$addressBookId = null;
		if ($this->cardDav->getAddressBooksForUserCount($principal) === 0) {
			try {
				$addressBookId = $this->cardDav->createAddressBook($principal, CardDavBackend::PERSONAL_ADDRESSBOOK_URI, [
					'{DAV:}displayname' => CardDavBackend::PERSONAL_ADDRESSBOOK_NAME,
				]);
			} catch (\Exception $e) {
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
			}
		}
		if ($addressBookId) {
			$this->defaultContactService->createDefaultContact($addressBookId);
		}
	}
}
