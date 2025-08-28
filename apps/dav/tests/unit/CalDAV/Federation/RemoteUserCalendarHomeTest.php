<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarFactory;
use OCA\DAV\CalDAV\Federation\RemoteUserCalendarHome;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class RemoteUserCalendarHomeTest extends TestCase {
	private RemoteUserCalendarHome $remoteUserCalendarHome;

	private BackendInterface&MockObject $calDavBackend;
	private CalendarFactory&MockObject $calendarFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->calDavBackend = $this->createMock(BackendInterface::class);
		$this->calendarFactory = $this->createMock(CalendarFactory::class);

		$this->remoteUserCalendarHome = new RemoteUserCalendarHome(
			$this->calDavBackend,
			[
				'uri' => 'principals/remote-users/abcdef123',
			],
			$this->calendarFactory,
		);
	}

	public function testGetChild(): void {
		$calendar1 = [
			'id' => 10,
			'uri' => 'cal1',
		];
		$calendar2 = [
			'id' => 11,
			'uri' => 'cal2',
		];

		$this->calDavBackend->expects(self::once())
			->method('getCalendarsForUser')
			->with('principals/remote-users/abcdef123')
			->willReturn([
				$calendar1,
				$calendar2,
			]);

		$actual = $this->remoteUserCalendarHome->getChild('cal2');
		$this->assertInstanceOf(Calendar::class, $actual);
		$this->assertEquals(11, $actual->getResourceId());
		$this->assertEquals('cal2', $actual->getName());
	}

	public function testGetChildNotFound(): void {
		$calendar1 = [
			'id' => 10,
			'uri' => 'cal1',
		];
		$calendar2 = [
			'id' => 11,
			'uri' => 'cal2',
		];

		$this->calDavBackend->expects(self::once())
			->method('getCalendarsForUser')
			->with('principals/remote-users/abcdef123')
			->willReturn([
				$calendar1,
				$calendar2,
			]);

		$this->expectException(NotFound::class);
		$this->remoteUserCalendarHome->getChild('cal3');
	}

	public function testGetChildren(): void {
		$calendar1 = [
			'id' => 10,
			'uri' => 'cal1',
		];
		$calendar2 = [
			'id' => 11,
			'uri' => 'cal2',
		];

		$this->calDavBackend->expects(self::once())
			->method('getCalendarsForUser')
			->with('principals/remote-users/abcdef123')
			->willReturn([
				$calendar1,
				$calendar2,
			]);

		$actual = $this->remoteUserCalendarHome->getChildren();
		$this->assertInstanceOf(Calendar::class, $actual[0]);
		$this->assertEquals(10, $actual[0]->getResourceId());
		$this->assertEquals('cal1', $actual[0]->getName());
		$this->assertInstanceOf(Calendar::class, $actual[1]);
		$this->assertEquals(11, $actual[1]->getResourceId());
		$this->assertEquals('cal2', $actual[1]->getName());
	}
}
