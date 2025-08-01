<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Service;

use OCA\DAV\CalDAV\UpcomingEvent;
use OCA\DAV\CalDAV\UpcomingEventsService;
use OCA\DAV\Controller\UpcomingEventsController;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpcomingEventsControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private UpcomingEventsService&MockObject $service;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(UpcomingEventsService::class);
	}

	public function testGetEventsAnonymously(): void {
		$controller = new UpcomingEventsController(
			$this->request,
			null,
			$this->service,
		);

		$response = $controller->getEvents('https://cloud.example.com/call/123');

		self::assertNull($response->getData());
		self::assertSame(401, $response->getStatus());
	}

	public function testGetEventsByLocation(): void {
		$controller = new UpcomingEventsController(
			$this->request,
			'u1',
			$this->service,
		);
		$this->service->expects(self::once())
			->method('getEvents')
			->with('u1', 'https://cloud.example.com/call/123')
			->willReturn([
				new UpcomingEvent(
					'abc-123',
					null,
					'personal',
					123,
					'Test',
					'https://cloud.example.com/call/123',
					null,
				),
			]);

		$response = $controller->getEvents('https://cloud.example.com/call/123');

		self::assertNotNull($response->getData());
		self::assertIsArray($response->getData());
		self::assertCount(1, $response->getData()['events']);
		self::assertSame(200, $response->getStatus());
		$event1 = $response->getData()['events'][0];
		self::assertEquals('abc-123', $event1['uri']);
	}
}
