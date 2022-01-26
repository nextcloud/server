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
namespace OCA\DAV\Tests\Unit\Listener;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Listener\UserChangeListener;
use OCP\DB\Exception;
use OCP\Defaults;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UserChangeListenerTest extends TestCase {

	/** @var CalDavBackend|MockObject */
	private $calDavBackend;

	/** @var CardDavBackend|MockObject */
	private $cardDavBackend;

	/** @var Defaults|MockObject */
	private $defaults;

	/** @var SyncService|MockObject */
	private $syncService;

	/** @var UserChangeListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$userManager = $this->createMock(IUserManager::class);
		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->cardDavBackend = $this->createMock(CardDavBackend::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->syncService = $this->createMock(SyncService::class);
		$logger = $this->createMock(LoggerInterface::class);

		$this->listener = new UserChangeListener(
			$userManager,
			$this->calDavBackend,
			$this->cardDavBackend,
			$this->defaults,
			$this->syncService,
			$logger
		);
	}

	/**
	 * @throws Exception
	 */
	public function testFirstLoginWithExisting() {
		$this->calDavBackend->expects($this->once())->method('getCalendarsForUserCount')->willReturn(1);
		$this->calDavBackend->expects($this->never())->method('createCalendar');


		$this->cardDavBackend->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(1);
		$this->cardDavBackend->expects($this->never())->method('createAddressBook');

		$this->listener->firstLogin($this->createMockUser());
	}

	/**
	 * @throws Exception
	 */
	public function testFirstLoginWithBirthdayCalendar() {
		$this->setupDefaultPrimaryColor();

		$this->calDavBackend->expects($this->once())->method('getCalendarsForUserCount')->willReturn(0);
		$this->calDavBackend->expects($this->once())->method('createCalendar')->with(
			'principals/users/newUser',
			'personal', [
			'{DAV:}displayname' => 'Personal',
			'{http://apple.com/ns/ical/}calendar-color' => '#745bca',
			'components' => 'VEVENT'
		]);

		$this->cardDavBackend->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(0);
		$this->cardDavBackend->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => 'Contacts']);

		$this->listener->firstLogin($this->createMockUser());
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteCalendar() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn('newUser');

		$this->syncService->expects($this->once())->method('deleteUser');

		$this->calDavBackend->expects($this->once())->method('getUsersOwnCalendars')->willReturn([
			['id' => 'personal']
		]);
		$this->calDavBackend->expects($this->once())->method('getSubscriptionsForUser')->willReturn([
			['id' => 'some-subscription']
		]);
		$this->calDavBackend->expects($this->once())->method('deleteCalendar')->with('personal');
		$this->calDavBackend->expects($this->once())->method('deleteSubscription')->with('some-subscription');
		$this->calDavBackend->expects($this->once())->method('deleteAllSharesByUser');

		$this->cardDavBackend->expects($this->once())->method('getUsersOwnAddressBooks')->willReturn([
			['id' => 'personal']
		]);
		$this->cardDavBackend->expects($this->once())->method('deleteAddressBook');

		$preDeleteEvent = new BeforeUserDeletedEvent($user);
		$postDeleteEvent = new UserDeletedEvent($user);
		$this->listener->handle($preDeleteEvent);
		$this->listener->handle($postDeleteEvent);
	}

	private function createMockUser() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('newUser');
		return $user;
	}

	private function setupDefaultPrimaryColor(): void {
		$this->defaults->expects($this->once())->method('getColorPrimary')->willReturn('#745bca');
	}
}
