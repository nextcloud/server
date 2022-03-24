<?php

declare(strict_types=1);

/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
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
namespace OCA\DAV\Listener;

use Exception;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCP\Defaults;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserCreatedEvent|BeforeUserDeletedEvent|UserDeletedEvent|UserChangedEvent>
 */
class UserChangeListener implements IEventListener {
	private IUserManager $userManager;
	private CalDavBackend $calDavBackend;
	private CardDavBackend $cardDavBackend;
	private Defaults $defaults;
	private SyncService $syncService;
	private LoggerInterface $logger;
	private array $usersToDelete = [];
	private array $calendarsToDelete = [];
	private array $subscriptionsToDelete = [];
	private array $addressBooksToDelete = [];

	private const USERS_PRINCIPAL_PREFIX = 'principals/users/';

	public function __construct(IUserManager $userManager, CalDavBackend $calDavBackend, CardDavBackend $cardDavBackend, Defaults $defaults, SyncService $syncService, LoggerInterface $logger) {
		$this->userManager = $userManager;
		$this->calDavBackend = $calDavBackend;
		$this->cardDavBackend = $cardDavBackend;
		$this->defaults = $defaults;
		$this->syncService = $syncService;
		$this->logger = $logger;
	}

	public function setupLegacyHooks(): void {
		$this->userManager->listen('\OC\User', 'assignedUserId', function ($uid) {
			$user = $this->userManager->get($uid);
			if ($user) {
				$this->postCreateUser($user);
			}
		});
		$this->userManager->listen('\OC\User', 'preUnassignedUserId', function ($uid): void {
			if ($user = $this->userManager->get($uid)) {
				$this->usersToDelete[$uid] = $user;
			}
		});
		$this->userManager->listen('\OC\User', 'postUnassignedUserId', function ($uid) {
			$this->postDeleteUser($uid);
		});
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function handle(Event $event): void {
		if ($event instanceof UserCreatedEvent) {
			$this->postCreateUser($event->getUser());
		}
		if ($event instanceof BeforeUserDeletedEvent) {
			$this->preDeleteUser($event->getUser());
		}
		if ($event instanceof UserDeletedEvent) {
			$this->postDeleteUser($event->getUser()->getUID());
		}
		if ($event instanceof UserChangedEvent) {
			$this->syncService->updateUser($event->getUser());
		}
	}

	private function postCreateUser(IUser $user): void {
		$this->syncService->updateUser($user);
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	private function preDeleteUser(IUser $user): void {
		$userPrincipalUri = self::USERS_PRINCIPAL_PREFIX . $user->getUID();
		$this->usersToDelete[$user->getUID()] = $user;
		$this->calendarsToDelete = $this->calDavBackend->getUsersOwnCalendars($userPrincipalUri);
		$this->subscriptionsToDelete = $this->calDavBackend->getSubscriptionsForUser($userPrincipalUri);
		$this->addressBooksToDelete = $this->cardDavBackend->getUsersOwnAddressBooks($userPrincipalUri);
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	private function postDeleteUser(string $uid): void {
		if (isset($this->usersToDelete[$uid])) {
			$this->syncService->deleteUser($this->usersToDelete[$uid]);
		}

		foreach ($this->calendarsToDelete as $calendar) {
			$this->calDavBackend->deleteCalendar(
				$calendar['id'],
				true // Make sure the data doesn't go into the trashbin, a new user with the same UID would later see it otherwise
			);
		}

		foreach ($this->subscriptionsToDelete as $subscription) {
			$this->calDavBackend->deleteSubscription(
				$subscription['id'],
			);
		}
		$this->calDavBackend->deleteAllSharesByUser(self::USERS_PRINCIPAL_PREFIX . $uid);

		foreach ($this->addressBooksToDelete as $addressBook) {
			$this->cardDavBackend->deleteAddressBook($addressBook['id']);
		}
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function firstLogin(IUser $user = null): void {
		if (!is_null($user)) {
			$principal = self::USERS_PRINCIPAL_PREFIX . $user->getUID();
			if ($this->calDavBackend->getCalendarsForUserCount($principal) === 0) {
				try {
					$this->calDavBackend->createCalendar($principal, CalDavBackend::PERSONAL_CALENDAR_URI, [
						'{DAV:}displayname' => CalDavBackend::PERSONAL_CALENDAR_NAME,
						'{http://apple.com/ns/ical/}calendar-color' => $this->defaults->getColorPrimary(),
						'components' => 'VEVENT'
					]);
				} catch (Exception $ex) {
					$this->logger->error('Error creating initial calendar for user', ['exception' => $ex]);
				}
			}
			if ($this->cardDavBackend->getAddressBooksForUserCount($principal) === 0) {
				try {
					$this->cardDavBackend->createAddressBook($principal, CardDavBackend::PERSONAL_ADDRESSBOOK_URI, [
						'{DAV:}displayname' => CardDavBackend::PERSONAL_ADDRESSBOOK_NAME,
					]);
				} catch (Exception $ex) {
					$this->logger->error('Error creating initial addressbook for user', ['exception' => $ex]);
				}
			}
		}
	}
}
