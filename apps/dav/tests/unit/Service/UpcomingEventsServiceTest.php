<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Service;

use DateTimeImmutable;
use OCA\DAV\CalDAV\UpcomingEventsService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\IManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpcomingEventsServiceTest extends TestCase {

	private IManager&MockObject $calendarManager;
	private ITimeFactory&MockObject $timeFactory;
	private IUserManager&MockObject $userManager;
	private IAppManager&MockObject $appManager;
	private IURLGenerator&MockObject $urlGenerator;
	private UpcomingEventsService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->calendarManager = $this->createMock(IManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->service = new UpcomingEventsService(
			$this->calendarManager,
			$this->timeFactory,
			$this->userManager,
			$this->appManager,
			$this->urlGenerator,
		);
	}

	public function testGetEventsByLocation(): void {
		$now = new DateTimeImmutable('2024-07-08T18:20:20Z');
		$this->timeFactory->method('now')
			->willReturn($now);
		$query = $this->createMock(ICalendarQuery::class);
		$this->appManager->method('isEnabledForUser')->willReturn(false);
		$this->calendarManager->method('newQuery')
			->with('principals/users/user1')
			->willReturn($query);
		$query->expects(self::once())
			->method('addSearchProperty')
			->with('LOCATION');
		$query->expects(self::once())
			->method('setSearchPattern')
			->with('https://cloud.example.com/call/123');
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([
				[
					'uri' => 'ev1',
					'calendar-key' => '1',
					'calendar-uri' => 'personal',
					'objects' => [
						0 => [
							'DTSTART' => [
								new DateTimeImmutable('now'),
							],
						],
					],
				],
			]);

		$events = $this->service->getEvents('user1', 'https://cloud.example.com/call/123');

		self::assertCount(1, $events);
		$event1 = $events[0];
		self::assertEquals('ev1', $event1->getUri());
	}
}
