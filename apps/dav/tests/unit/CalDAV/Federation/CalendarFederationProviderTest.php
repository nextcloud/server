<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OC\BackgroundJob\JobList;
use OCA\DAV\BackgroundJob\FederatedCalendarSyncJob;
use OCA\DAV\CalDAV\Federation\CalendarFederationConfig;
use OCA\DAV\CalDAV\Federation\CalendarFederationProvider;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCP\BackgroundJob\IJobList;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Share\Exceptions\ShareNotFound;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CalendarFederationProviderTest extends TestCase {
	private CalendarFederationProvider $calendarFederationProvider;

	private LoggerInterface&MockObject $logger;
	private FederatedCalendarMapper&MockObject $federatedCalendarMapper;
	private CalendarFederationConfig&MockObject $calendarFederationConfig;
	private IJobList&MockObject $jobList;
	private ICloudIdManager&MockObject $cloudIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->federatedCalendarMapper = $this->createMock(FederatedCalendarMapper::class);
		$this->calendarFederationConfig = $this->createMock(CalendarFederationConfig::class);
		$this->jobList = $this->createMock(JobList::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);

		$this->calendarFederationProvider = new CalendarFederationProvider(
			$this->logger,
			$this->federatedCalendarMapper,
			$this->calendarFederationConfig,
			$this->jobList,
			$this->cloudIdManager,
		);
	}

	public function testGetShareType(): void {
		$this->assertEquals('calendar', $this->calendarFederationProvider->getShareType());
	}

	public function testGetSupportedShareTypes(): void {
		$this->assertEqualsCanonicalizing(
			['user'],
			$this->calendarFederationProvider->getSupportedShareTypes(),
		);
	}

	public function testShareReceived(): void {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getShareType')
			->willReturn('user');
		$share->method('getProtocol')
			->willReturn([
				'version' => 'v1',
				'url' => 'https://nextcloud.remote/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]);
		$share->method('getShareWith')
			->willReturn('sharee1');
		$share->method('getShareSecret')
			->willReturn('token');
		$share->method('getSharedBy')
			->willReturn('user1@nextcloud.remote');
		$share->method('getSharedByDisplayName')
			->willReturn('User 1');

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(true);

		$this->federatedCalendarMapper->expects(self::once())
			->method('deleteByUri')
			->with(
				'principals/users/sharee1',
				'ae4b8ab904076fff2b955ea21b1a0d92',
			);

		$this->federatedCalendarMapper->expects(self::once())
			->method('insert')
			->willReturnCallback(function (FederatedCalendarEntity $calendar) {
				$this->assertEquals('principals/users/sharee1', $calendar->getPrincipaluri());
				$this->assertEquals('ae4b8ab904076fff2b955ea21b1a0d92', $calendar->getUri());
				$this->assertEquals('https://nextcloud.remote/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user1', $calendar->getRemoteUrl());
				$this->assertEquals('Calendar 1', $calendar->getDisplayName());
				$this->assertEquals('#ff0000', $calendar->getColor());
				$this->assertEquals('token', $calendar->getToken());
				$this->assertEquals('user1@nextcloud.remote', $calendar->getSharedBy());
				$this->assertEquals('User 1', $calendar->getSharedByDisplayName());
				$this->assertEquals(1, $calendar->getPermissions());
				$this->assertEquals('VEVENT,VTODO', $calendar->getComponents());

				$calendar->setId(10);
				return $calendar;
			});

		$this->jobList->expects(self::once())
			->method('add')
			->with(FederatedCalendarSyncJob::class, ['id' => 10]);

		$this->assertEquals(10, $this->calendarFederationProvider->shareReceived($share));
	}

	public function testShareReceivedWithInvalidProtocolVersion(): void {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getShareType')
			->willReturn('user');
		$share->method('getProtocol')
			->willReturn([
				'version' => 'unknown',
				'url' => 'https://nextcloud.remote/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]);

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(true);

		$this->federatedCalendarMapper->expects(self::never())
			->method('insert');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('Unknown protocol version');
		$this->expectExceptionCode(400);
		$this->assertEquals(10, $this->calendarFederationProvider->shareReceived($share));
	}

	public function testShareReceivedWithoutProtocolVersion(): void {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getShareType')
			->willReturn('user');
		$share->method('getProtocol')
			->willReturn([
				'url' => 'https://nextcloud.remote/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]);

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(true);

		$this->federatedCalendarMapper->expects(self::never())
			->method('insert');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('No protocol version');
		$this->expectExceptionCode(400);
		$this->assertEquals(10, $this->calendarFederationProvider->shareReceived($share));
	}

	public function testShareReceivedWithDisabledConfig(): void {
		$share = $this->createMock(ICloudFederationShare::class);

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::never())
			->method('insert');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('Server does not support calendar federation');
		$this->expectExceptionCode(503);
		$this->calendarFederationProvider->shareReceived($share);
	}

	public function testShareReceivedWithUnsupportedShareType(): void {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getShareType')
			->willReturn('foobar');

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(true);

		$this->federatedCalendarMapper->expects(self::never())
			->method('insert');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('Support for sharing with non-users not implemented yet');
		$this->expectExceptionCode(501);
		$this->calendarFederationProvider->shareReceived($share);
	}

	public static function provideIncompleteProtocolData(): array {
		return [
			[[
				'version' => 'v1',
				'url' => '',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]],
			[[
				'version' => 'v1',
				'url' => 'https://nextcloud.remote/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user1',
				'displayName' => '',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]],
		];
	}

	#[DataProvider(methodName: 'provideIncompleteProtocolData')]
	public function testShareReceivedWithIncompleteProtocolData(array $protocol): void {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getShareType')
			->willReturn('user');
		$share->method('getProtocol')
			->willReturn($protocol);
		$share->method('getShareWith')
			->willReturn('sharee1');
		$share->method('getShareSecret')
			->willReturn('token');
		$share->method('getSharedBy')
			->willReturn('user1@nextcloud.remote');
		$share->method('getSharedByDisplayName')
			->willReturn('User 1');

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(true);

		$this->federatedCalendarMapper->expects(self::never())
			->method('insert');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('Incomplete protocol data');
		$this->expectExceptionCode(400);
		$this->calendarFederationProvider->shareReceived($share);
	}

	public function testShareReceivedWithUnsupportedAccess(): void {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getShareType')
			->willReturn('user');
		$share->method('getProtocol')
			->willReturn([
				'version' => 'v1',
				'url' => 'https://nextcloud.remote/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 2, // Backend::ACCESS_READ_WRITE
				'components' => 'VEVENT,VTODO',
			]);
		$share->method('getShareWith')
			->willReturn('sharee1');
		$share->method('getShareSecret')
			->willReturn('token');
		$share->method('getSharedBy')
			->willReturn('user1@nextcloud.remote');
		$share->method('getSharedByDisplayName')
			->willReturn('User 1');

		$this->calendarFederationConfig->expects(self::once())
			->method('isFederationEnabled')
			->willReturn(true);

		$this->federatedCalendarMapper->expects(self::never())
			->method('insert');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessageMatches('/Unsupported access value: [0-9]+/');
		$this->expectExceptionCode(400);
		$this->calendarFederationProvider->shareReceived($share);
	}

	public function testNotificationReceivedWithUnknownNotification(): void {
		$actual = $this->calendarFederationProvider->notificationReceived('UNKNOWN', 'calendar', [
			'sharedSecret' => 'token',
			'foobar' => 'baz',
		]);
		$this->assertEquals([], $actual);
	}

	public function testNotificationReceivedWithInvalidProviderId(): void {
		$this->expectException(BadRequestException::class);
		$this->calendarFederationProvider->notificationReceived('SYNC_CALENDAR', 'foobar', [
			'sharedSecret' => 'token',
			'shareWith' => 'remote1@nextcloud.remote',
			'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
		]);
	}

	public function testNotificationReceivedWithSyncCalendarNotification(): void {
		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('remote1@nextcloud.remote');
		$cloudId->method('getUser')
			->willReturn('remote1');
		$cloudId->method('getRemote')
			->willReturn('nextcloud.remote');

		$this->cloudIdManager->expects(self::once())
			->method('resolveCloudId')
			->with('remote1@nextcloud.remote')
			->willReturn($cloudId);

		$calendar1 = new FederatedCalendarEntity();
		$calendar1->setId(10);
		$calendar2 = new FederatedCalendarEntity();
		$calendar2->setId(11);
		$calendars = [
			$calendar1,
			$calendar2,
		];
		$this->federatedCalendarMapper->expects(self::once())
			->method('findByRemoteUrl')
			->with(
				'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				'principals/users/remote1',
				'token',
			)
			->willReturn($calendars);

		$this->jobList->expects(self::exactly(2))
			->method('add')
			->willReturnMap([
				[FederatedCalendarSyncJob::class, ['id' => 10]],
				[FederatedCalendarSyncJob::class, ['id' => 11]],
			]);

		$actual = $this->calendarFederationProvider->notificationReceived(
			'SYNC_CALENDAR',
			'calendar',
			[
				'sharedSecret' => 'token',
				'shareWith' => 'remote1@nextcloud.remote',
				'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
			],
		);
		$this->assertEquals([], $actual);
	}

	public static function provideIncompleteSyncCalendarNotificationData(): array {
		return [
			// Missing shareWith
			[[
				'sharedSecret' => 'token',
				'shareWith' => '',
				'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
			]],
			[[
				'sharedSecret' => 'token',
				'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
			]],

			// Missing calendarUrl
			[[
				'sharedSecret' => 'token',
				'shareWith' => 'remote1@nextcloud.remote',
				'calendarUrl' => '',
			]],
			[[
				'sharedSecret' => 'token',
				'shareWith' => 'remote1@nextcloud.remote',
			]],
		];
	}

	#[DataProvider(methodName: 'provideIncompleteSyncCalendarNotificationData')]
	public function testNotificationReceivedWithSyncCalendarNotificationAndIncompleteData(
		array $notification,
	): void {
		$this->cloudIdManager->expects(self::never())
			->method('resolveCloudId');
		$this->federatedCalendarMapper->expects(self::never())
			->method('findByRemoteUrl');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(BadRequestException::class);
		$this->calendarFederationProvider->notificationReceived(
			'SYNC_CALENDAR',
			'calendar',
			$notification,
		);
	}

	public function testNotificationReceivedWithSyncCalendarNotificationAndInvalidCloudId(): void {
		$this->cloudIdManager->expects(self::once())
			->method('resolveCloudId')
			->with('invalid-cloud-id')
			->willThrowException(new \InvalidArgumentException());

		$this->federatedCalendarMapper->expects(self::never())
			->method('findByRemoteUrl');
		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ShareNotFound::class);
		$this->expectExceptionMessage('Invalid sharee cloud id');
		$this->calendarFederationProvider->notificationReceived(
			'SYNC_CALENDAR',
			'calendar',
			[
				'sharedSecret' => 'token',
				'shareWith' => 'invalid-cloud-id',
				'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
			],
		);
	}

	public function testNotificationReceivedWithSyncCalendarNotificationAndNoCalendars(): void {
		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('remote1@nextcloud.remote');
		$cloudId->method('getUser')
			->willReturn('remote1');
		$cloudId->method('getRemote')
			->willReturn('nextcloud.remote');

		$this->cloudIdManager->expects(self::once())
			->method('resolveCloudId')
			->with('remote1@nextcloud.remote')
			->willReturn($cloudId);

		$this->federatedCalendarMapper->expects(self::once())
			->method('findByRemoteUrl')
			->with(
				'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				'principals/users/remote1',
				'token',
			)
			->willReturn([]);

		$this->jobList->expects(self::never())
			->method('add');

		$this->expectException(ShareNotFound::class);
		$this->expectExceptionMessage('Calendar is not shared with the sharee');
		$this->calendarFederationProvider->notificationReceived(
			'SYNC_CALENDAR',
			'calendar',
			[
				'sharedSecret' => 'token',
				'shareWith' => 'remote1@nextcloud.remote',
				'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
			],
		);
	}
}
