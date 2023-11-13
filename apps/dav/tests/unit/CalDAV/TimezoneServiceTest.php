<?php
/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

	public function testGetUserTimezoneFromAvailability(): void {
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
		$this->config->expects(self::once())
			->method('getUserValue')
			->with('test123', 'dav', 'defaultCalendar')
			->willReturn('personal-1');
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
		$this->config->expects(self::once())
			->method('getUserValue')
			->with('test123', 'dav', 'defaultCalendar')
			->willReturn('personal-1');
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
