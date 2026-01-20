<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\Federation\FederatedCalendarSyncService;
use OCA\DAV\CalDAV\SyncService as CalDavSyncService;
use OCA\DAV\CalDAV\SyncServiceResult;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FederatedCalendarSyncServiceTest extends TestCase {
	private FederatedCalendarSyncService $federatedCalendarSyncService;

	private FederatedCalendarMapper&MockObject $federatedCalendarMapper;
	private LoggerInterface&MockObject $logger;
	private CalDavSyncService&MockObject $calDavSyncService;
	private ICloudIdManager&MockObject $cloudIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->federatedCalendarMapper = $this->createMock(FederatedCalendarMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->calDavSyncService = $this->createMock(CalDavSyncService::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);

		$this->federatedCalendarSyncService = new FederatedCalendarSyncService(
			$this->federatedCalendarMapper,
			$this->logger,
			$this->calDavSyncService,
			$this->cloudIdManager,
		);
	}

	public function testSyncOne(): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		$this->calDavSyncService->expects(self::once())
			->method('syncRemoteCalendar')
			->with(
				'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2',
				'dXNlcjFAbmV4dGNsb3VkLnRlc3Rpbmc=',
				'token',
				'http://sabre.io/ns/sync/100',
				$calendar,
			)
			->willReturn(new SyncServiceResult('http://sabre.io/ns/sync/101', 10));

		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTokenAndTime')
			->with(1, 101);
		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTime');

		$this->assertEquals(10, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public function testSyncOneUnchanged(): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		$this->calDavSyncService->expects(self::once())
			->method('syncRemoteCalendar')
			->with(
				'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2',
				'dXNlcjFAbmV4dGNsb3VkLnRlc3Rpbmc=',
				'token',
				'http://sabre.io/ns/sync/100',
				$calendar,
			)
			->willReturn(new SyncServiceResult('http://sabre.io/ns/sync/100', 0));

		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTokenAndTime');
		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTime')
			->with(1);

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public static function provideUnexpectedSyncTokenData(): array {
		return [
			['http://sabre.io/ns/sync/'],
			['http://sabre.io/ns/sync/foobar'],
			['http://sabre.io/ns/sync/23abc'],
			['http://nextcloud.com/ns/sync/33'],
		];
	}

	#[DataProvider(methodName: 'provideUnexpectedSyncTokenData')]
	public function testSyncOneWithUnexpectedSyncTokenFormat(string $syncToken): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		$this->calDavSyncService->expects(self::once())
			->method('syncRemoteCalendar')
			->with(
				'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2',
				'dXNlcjFAbmV4dGNsb3VkLnRlc3Rpbmc=',
				'token',
				'http://sabre.io/ns/sync/100',
				$calendar,
			)
			->willReturn(new SyncServiceResult($syncToken, 10));

		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTokenAndTime');
		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTime');

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}
}
