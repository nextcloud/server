<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Controller;

use OCA\DAV\CalDAV\Export\ExportService;
use OCA\DAV\Controller\CalendarExportController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamTraversableResponse;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarExport;
use OCP\Calendar\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarExportControllerTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private IManager&MockObject $calendarManager;
	private ExportService&MockObject $exportService;
	private IEventDispatcher&MockObject $eventDispatcher;
	private CalendarExportController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->calendarManager = $this->createMock(IManager::class);
		$this->exportService = $this->createMock(ExportService::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('isLoggedIn')->willReturn(true);
		$this->userSession->method('getUser')->willReturn($user);

		$this->controller = new CalendarExportController(
			$this->createMock(IRequest::class),
			$this->userSession,
			$this->createMock(IUserManager::class),
			$this->createMock(IGroupManager::class),
			$this->calendarManager,
			$this->exportService,
			$this->eventDispatcher,
		);
	}

	public function testExport(): void {
		$calendar = $this->createMockForIntersectionOfInterfaces([ICalendar::class, ICalendarExport::class]);
		$this->calendarManager->expects($this->once())
			->method('getCalendarsForPrincipal')
			->with('principals/users/alice', ['personal'])
			->willReturn([$calendar]);
		$this->exportService->method('export')
			->willReturnCallback(static function () {
				yield '';
			});

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'dav.calendar.exported'
				&& $event->getLogMessage() === 'Calendar "%s" of "%s" was exported in format "%s"'
				&& $event->getParameters() === ['calendarId' => 'personal', 'owner' => 'alice', 'format' => 'jcal']));

		$response = $this->controller->export('personal', 'jcal');

		$this->assertInstanceOf(StreamTraversableResponse::class, $response);
	}

	public function testExportInvalidFormat(): void {
		$calendar = $this->createMockForIntersectionOfInterfaces([ICalendar::class, ICalendarExport::class]);
		$this->calendarManager->method('getCalendarsForPrincipal')
			->willReturn([$calendar]);

		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$response = $this->controller->export('personal', 'pdf');

		$this->assertInstanceOf(DataResponse::class, $response);
	}
}
