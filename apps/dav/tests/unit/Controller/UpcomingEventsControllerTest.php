<?php declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\Unit\DAV\Service;

use DateTimeImmutable;
use OCA\DAV\CalDAV\UpcomingEventsService;
use OCA\DAV\Controller\UpcomingEventsController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\IManager;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpcomingEventsControllerTest extends TestCase {

	private IRequest|MockObject $request;
	private UpcomingEventsService|MockObject $service;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(UpcomingEventsService::class);
	}

	public function testGetEventsAnonymously() {
		$controller = new UpcomingEventsController(
			$this->request,
			null,
			$this->service,
		);

		$response = $controller->getEvents('https://cloud.example.com/call/123');

		self::assertNull($response->getData());
		self::assertSame(401, $response->getStatus());
	}

	public function testGetEventsByLocation() {
		$now = new DateTimeImmutable('2024-07-08T18:20:20Z');
		$this->timeFactory->method('now')
			->willReturn($now);
		$query = $this->createMock(ICalendarQuery::class);
		$this->calendarManager->method('newQuery')
			->with('principals/users/u1')
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
					'objects' => [
						0 => [
							'DTSTART' => [
								new DateTimeImmutable('now'),
							],
						],
					],
				],
			]);
		$controller = new UpcomingEventsController(
			$this->request,
			'u1',
			$this->calendarManager,
			$this->timeFactory,
		);

		$response = $controller->getEvents('https://cloud.example.com/call/123');

		self::assertNotNull($response->getData());
		self::assertIsArray($response->getData());
		self::assertCount(1, $response->getData()['events']);
		self::assertSame(200, $response->getStatus());
		$event1 = $response->getData()['events'][0];
		self::assertEquals('ev1', $event1['uri']);
	}
}
