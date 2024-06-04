<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use DateTimeZone;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\Property;
use OCA\DAV\Db\PropertyMapper;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VTimeZone;
use Test\TestCase;

class TimezoneServiceTest extends TestCase {

	private IConfig|MockObject $config;
	private PropertyMapper|MockObject $propertyMapper;
	private IManager|MockObject $calendarManager;
	private TimezoneService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->propertyMapper = $this->createMock(PropertyMapper::class);
		$this->calendarManager = $this->createMock(IManager::class);

		$this->service = new TimezoneService(
			$this->config,
			$this->propertyMapper,
			$this->calendarManager,
		);
	}

	public function testGetUserTimezoneFromSettings(): void {
		$this->config->expects(self::once())
			->method('getUserValue')
			->with('test123', 'core', 'timezone', '')
			->willReturn('Europe/Warsaw');

		$timezone = $this->service->getUserTimezone('test123');

		self::assertSame('Europe/Warsaw', $timezone);
	}

	public function testGetUserTimezoneFromAvailability(): void {
		$this->config->expects(self::once())
			->method('getUserValue')
			->with('test123', 'core', 'timezone', '')
			->willReturn('');
		$property = new Property();
		$property->setPropertyvalue('BEGIN:VCALENDAR
PRODID:Nextcloud DAV app
BEGIN:VTIMEZONE
TZID:Europe/Vienna
END:VTIMEZONE
END:VCALENDAR');
		$this->propertyMapper->expects(self::once())
			->method('findPropertyByPathAndName')
			->willReturn([
				$property,
			]);

		$timezone = $this->service->getUserTimezone('test123');

		self::assertNotNull($timezone);
		self::assertEquals('Europe/Vienna', $timezone);
	}

	public function testGetUserTimezoneFromPersonalCalendar(): void {
		$this->config->expects(self::exactly(2))
			->method('getUserValue')
			->willReturnMap([
				['test123', 'core', 'timezone', '', ''],
				['test123', 'dav', 'defaultCalendar', '', 'personal-1'],
			]);
		$other = $this->createMock(ICalendar::class);
		$other->method('getUri')->willReturn('other');
		$personal = $this->createMock(CalendarImpl::class);
		$personal->method('getUri')->willReturn('personal-1');
		$tz = new DateTimeZone('Europe/Berlin');
		$vtz = $this->createMock(VTimeZone::class);
		$vtz->method('getTimeZone')->willReturn($tz);
		$personal->method('getSchedulingTimezone')->willReturn($vtz);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with('principals/users/test123')
			->willReturn([
				$other,
				$personal,
			]);

		$timezone = $this->service->getUserTimezone('test123');

		self::assertNotNull($timezone);
		self::assertEquals('Europe/Berlin', $timezone);
	}

	public function testGetUserTimezoneFromAny(): void {
		$this->config->expects(self::exactly(2))
			->method('getUserValue')
			->willReturnMap([
				['test123', 'core', 'timezone', '', ''],
				['test123', 'dav', 'defaultCalendar', '', 'personal-1'],
			]);
		$other = $this->createMock(ICalendar::class);
		$other->method('getUri')->willReturn('other');
		$personal = $this->createMock(CalendarImpl::class);
		$personal->method('getUri')->willReturn('personal-2');
		$tz = new DateTimeZone('Europe/Prague');
		$vtz = $this->createMock(VTimeZone::class);
		$vtz->method('getTimeZone')->willReturn($tz);
		$personal->method('getSchedulingTimezone')->willReturn($vtz);
		$this->calendarManager->expects(self::once())
			->method('getCalendarsForPrincipal')
			->with('principals/users/test123')
			->willReturn([
				$other,
				$personal,
			]);

		$timezone = $this->service->getUserTimezone('test123');

		self::assertNotNull($timezone);
		self::assertEquals('Europe/Prague', $timezone);
	}

	public function testGetUserTimezoneNoneFound(): void {
		$timezone = $this->service->getUserTimezone('test123');

		self::assertNull($timezone);
	}

}
