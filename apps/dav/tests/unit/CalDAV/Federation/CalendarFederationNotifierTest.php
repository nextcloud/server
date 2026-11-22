<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\CalendarFederationNotifier;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationNotification;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudId;
use OCP\Http\Client\IResponse;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarFederationNotifierTest extends TestCase {
	private readonly CalendarFederationNotifier $calendarFederationNotifier;

	private readonly ICloudFederationFactory&MockObject $federationFactory;
	private readonly ICloudFederationProviderManager&MockObject $federationManager;
	private readonly IURLGenerator&MockObject $url;

	protected function setUp(): void {
		parent::setUp();

		$this->federationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->federationManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->url = $this->createMock(IURLGenerator::class);

		$this->calendarFederationNotifier = new CalendarFederationNotifier(
			$this->federationFactory,
			$this->federationManager,
			$this->url,
		);
	}

	public function testNotifySyncCalendar(): void {
		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('remote1@nextcloud.remote');
		$cloudId->method('getRemote')
			->willReturn('nextcloud.remote');

		$this->url->expects(self::once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('/remote.php');
		$this->url->expects(self::once())
			->method('getAbsoluteURL')
			->with('/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1')
			->willReturn('https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1');

		$notification = $this->createMock(ICloudFederationNotification::class);
		$notification->expects(self::once())
			->method('setMessage')
			->with(
				'SYNC_CALENDAR',
				'calendar',
				'calendar',
				[
					'sharedSecret' => 'token',
					'shareWith' => 'remote1@nextcloud.remote',
					'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				],
			);
		$this->federationFactory->expects(self::once())
			->method('getCloudFederationNotification')
			->willReturn($notification);

		$response = $this->createMock(IResponse::class);
		$this->federationManager->expects(self::once())
			->method('sendCloudNotification')
			->with('nextcloud.remote', $notification)
			->willReturn($response);

		$this->calendarFederationNotifier->notifySyncCalendar($cloudId, 'host1', 'cal1', 'token');
	}

	public function testNotifySyncCalendarShouldRethrowOcmException(): void {
		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('remote1@nextcloud.remote');
		$cloudId->method('getRemote')
			->willReturn('nextcloud.remote');

		$this->url->expects(self::once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('/remote.php');
		$this->url->expects(self::once())
			->method('getAbsoluteURL')
			->with('/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1')
			->willReturn('https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1');

		$notification = $this->createMock(ICloudFederationNotification::class);
		$notification->expects(self::once())
			->method('setMessage')
			->with(
				'SYNC_CALENDAR',
				'calendar',
				'calendar',
				[
					'sharedSecret' => 'token',
					'shareWith' => 'remote1@nextcloud.remote',
					'calendarUrl' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				],
			);
		$this->federationFactory->expects(self::once())
			->method('getCloudFederationNotification')
			->willReturn($notification);

		$this->federationManager->expects(self::once())
			->method('sendCloudNotification')
			->with('nextcloud.remote', $notification)
			->willThrowException(new OCMProviderException('I threw this'));

		$this->expectException(OCMProviderException::class);
		$this->expectExceptionMessage('I threw this');
		$this->calendarFederationNotifier->notifySyncCalendar($cloudId, 'host1', 'cal1', 'token');
	}
}
