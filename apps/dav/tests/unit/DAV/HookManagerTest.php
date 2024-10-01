<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\HookManager;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class HookManagerTest extends TestCase {
	/** @var IL10N */
	private $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
	}

	public function test(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		/** @var IUserManager | MockObject $userManager */
		$userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var SyncService | MockObject $syncService */
		$syncService = $this->getMockBuilder(SyncService::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var Defaults | MockObject $syncService */
		$defaults = $this->getMockBuilder(Defaults::class)
			->disableOriginalConstructor()
			->getMock();

		$defaults->expects($this->once())->method('getColorPrimary')->willReturn('#745bca');

		/** @var CalDavBackend | MockObject $cal */
		$cal = $this->getMockBuilder(CalDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getCalendarsForUserCount')->willReturn(0);
		$cal->expects($this->once())->method('createCalendar')->with(
			'principals/users/newUser',
			'personal', [
				'{DAV:}displayname' => 'Personal',
				'{http://apple.com/ns/ical/}calendar-color' => '#745bca',
				'components' => 'VEVENT'
			]);

		/** @var CardDavBackend | MockObject $card */
		$card = $this->getMockBuilder(CardDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(0);
		$card->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => 'Contacts']);

		$hm = new HookManager($userManager, $syncService, $cal, $card, $defaults);
		$hm->firstLogin($user);
	}

	public function testWithExisting(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		/** @var IUserManager | MockObject $userManager */
		$userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var SyncService | MockObject $syncService */
		$syncService = $this->getMockBuilder(SyncService::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var Defaults | MockObject $syncService */
		$defaults = $this->getMockBuilder(Defaults::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var CalDavBackend | MockObject $cal */
		$cal = $this->getMockBuilder(CalDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getCalendarsForUserCount')->willReturn(1);
		$cal->expects($this->never())->method('createCalendar');

		/** @var CardDavBackend | MockObject $card */
		$card = $this->getMockBuilder(CardDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(1);
		$card->expects($this->never())->method('createAddressBook');

		$hm = new HookManager($userManager, $syncService, $cal, $card, $defaults);
		$hm->firstLogin($user);
	}

	public function testWithBirthdayCalendar(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		/** @var IUserManager | MockObject $userManager */
		$userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var SyncService | MockObject $syncService */
		$syncService = $this->getMockBuilder(SyncService::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var Defaults | MockObject $syncService */
		$defaults = $this->getMockBuilder(Defaults::class)
			->disableOriginalConstructor()
			->getMock();
		$defaults->expects($this->once())->method('getColorPrimary')->willReturn('#745bca');

		/** @var CalDavBackend | MockObject $cal */
		$cal = $this->getMockBuilder(CalDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getCalendarsForUserCount')->willReturn(0);
		$cal->expects($this->once())->method('createCalendar')->with(
			'principals/users/newUser',
			'personal', [
				'{DAV:}displayname' => 'Personal',
				'{http://apple.com/ns/ical/}calendar-color' => '#745bca',
				'components' => 'VEVENT'
			]);

		/** @var CardDavBackend | MockObject $card */
		$card = $this->getMockBuilder(CardDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(0);
		$card->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => 'Contacts']);

		$hm = new HookManager($userManager, $syncService, $cal, $card, $defaults);
		$hm->firstLogin($user);
	}

	public function testDeleteCalendar(): void {
		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var IUserManager | MockObject $userManager */
		$userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();
		$userManager->expects($this->once())->method('get')->willReturn($user);

		/** @var SyncService | MockObject $syncService */
		$syncService = $this->getMockBuilder(SyncService::class)
			->disableOriginalConstructor()
			->getMock();
		$syncService->expects($this->once())
			->method('deleteUser');

		/** @var Defaults | MockObject $syncService */
		$defaults = $this->getMockBuilder(Defaults::class)
			->disableOriginalConstructor()
			->getMock();

		/** @var CalDavBackend | MockObject $cal */
		$cal = $this->getMockBuilder(CalDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getUsersOwnCalendars')->willReturn([
			['id' => 'personal']
		]);
		$cal->expects($this->once())->method('getSubscriptionsForUser')->willReturn([
			['id' => 'some-subscription']
		]);
		$cal->expects($this->once())->method('deleteCalendar')->with('personal');
		$cal->expects($this->once())->method('deleteSubscription')->with('some-subscription');
		$cal->expects($this->once())->method('deleteAllSharesByUser');

		/** @var CardDavBackend | MockObject $card */
		$card = $this->getMockBuilder(CardDavBackend::class)
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getUsersOwnAddressBooks')->willReturn([
			['id' => 'personal']
		]);
		$card->expects($this->once())->method('deleteAddressBook');

		$hm = new HookManager($userManager, $syncService, $cal, $card, $defaults);
		$hm->preDeleteUser(['uid' => 'newUser']);
		$hm->postDeleteUser(['uid' => 'newUser']);
	}
}
