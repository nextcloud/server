<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\DAV\Listener;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Listener\UserEventsListener;
use OCA\DAV\Service\DefaultContactService;
use OCA\DAV\Service\ExampleEventService;
use OCP\Defaults;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UserEventsListenerTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private SyncService&MockObject $syncService;
	private CalDavBackend&MockObject $calDavBackend;
	private CardDavBackend&MockObject $cardDavBackend;
	private Defaults&MockObject $defaults;
	private DefaultContactService&MockObject $defaultContactService;
	private ExampleEventService&MockObject $exampleEventService;
	private LoggerInterface&MockObject $logger;

	private UserEventsListener $userEventsListener;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->syncService = $this->createMock(SyncService::class);
		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->cardDavBackend = $this->createMock(CardDavBackend::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->defaultContactService = $this->createMock(DefaultContactService::class);
		$this->exampleEventService = $this->createMock(ExampleEventService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->userEventsListener = new UserEventsListener(
			$this->userManager,
			$this->syncService,
			$this->calDavBackend,
			$this->cardDavBackend,
			$this->defaults,
			$this->defaultContactService,
			$this->exampleEventService,
			$this->logger,
		);
	}

	public function test(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		$this->defaults->expects($this->once())->method('getColorPrimary')->willReturn('#745bca');

		$this->calDavBackend->expects($this->once())->method('getCalendarsForUserCount')->willReturn(0);
		$this->calDavBackend->expects($this->once())->method('createCalendar')->with(
			'principals/users/newUser',
			'personal', [
				'{DAV:}displayname' => 'Personal',
				'{http://apple.com/ns/ical/}calendar-color' => '#745bca',
				'components' => 'VEVENT'
			])
			->willReturn(1000);
		$this->calDavBackend->expects(self::never())
			->method('getCalendarsForUser');
		$this->exampleEventService->expects(self::once())
			->method('shouldCreateExampleEvent')
			->willReturn(true);
		$this->exampleEventService->expects(self::once())
			->method('createExampleEvent')
			->with(1000);

		$this->cardDavBackend->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(0);
		$this->cardDavBackend->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => 'Contacts']);

		$this->userEventsListener->firstLogin($user);
	}

	public function testWithExisting(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		$this->calDavBackend->expects($this->once())->method('getCalendarsForUserCount')->willReturn(2);
		$this->calDavBackend->expects($this->never())->method('createCalendar');
		$this->calDavBackend->expects(self::never())
			->method('createCalendar');
		$this->exampleEventService->expects(self::once())
			->method('shouldCreateExampleEvent')
			->willReturn(true);
		$this->calDavBackend->expects(self::once())
			->method('getCalendarsForUser')
			->with('principals/users/newUser')
			->willReturn([
				['id' => 1000],
				['id' => 1001],
			]);
		$this->exampleEventService->expects(self::once())
			->method('createExampleEvent')
			->with(1000);

		$this->cardDavBackend->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(1);
		$this->cardDavBackend->expects($this->never())->method('createAddressBook');

		$this->userEventsListener->firstLogin($user);
	}

	public static function provideFirstLoginWithoutExampleEventData(): array {
		return [
			[0],
			[1],
		];
	}

	/** @dataProvider provideFirstLoginWithoutExampleEventData */
	public function testFirstLoginWithoutExampleEvent(int $existingCalendarCount): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('newUser');


		$this->exampleEventService->expects(self::once())
			->method('shouldCreateExampleEvent')
			->willReturn(false);
		$this->calDavBackend->expects(self::once())
			->method('getCalendarsForUserCount')
			->willReturn($existingCalendarCount);
		if ($existingCalendarCount === 0) {
			$this->defaults->expects(self::once())
				->method('getColorPrimary')
				->willReturn('#745bca');
			$this->calDavBackend->expects(self::once())
				->method('createCalendar')
				->with('principals/users/newUser', 'personal', [
					'{DAV:}displayname' => 'Personal',
					'{http://apple.com/ns/ical/}calendar-color' => '#745bca',
					'components' => 'VEVENT'
				])
				->willReturn(1000);
		} else {
			$this->calDavBackend->expects(self::never())
				->method('createCalendar');
		}
		$this->calDavBackend->expects(self::never())
			->method('getCalendarsForUser');
		$this->exampleEventService->expects(self::never())
			->method('createExampleEvent');

		$this->cardDavBackend->expects($this->once())->method('getAddressBooksForUserCount')->willReturn(0);
		$this->cardDavBackend->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => 'Contacts']);

		$this->userEventsListener->firstLogin($user);
	}

	public function testWithBirthdayCalendar(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		$this->defaults->expects($this->once())->method('getColorPrimary')->willReturn('#745bca');

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

		$this->userEventsListener->firstLogin($user);
	}

	public function testDeleteCalendar(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		$this->syncService->expects($this->once())
			->method('deleteUser');

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

		$this->userEventsListener->preDeleteUser($user);
		$this->userEventsListener->postDeleteUser('newUser');
	}
}
