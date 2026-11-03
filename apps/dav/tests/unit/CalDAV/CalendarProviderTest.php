<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\CalendarProvider;
use OCA\DAV\Db\PropertyMapper;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Test\TestCase;

class CalendarProviderTest extends TestCase {
	private CalDavBackend&MockObject $backend;
	private PropertyMapper&MockObject $propertyMapper;
	private CalendarProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->propertyMapper = $this->createMock(PropertyMapper::class);
		$this->provider = new CalendarProvider(
			$this->backend,
			$this->createMock(IL10N::class),
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->propertyMapper,
		);
	}

	public function testGetCalendarsUsesCalendarProperties(): void {
		$this->backend->method('getCalendarsForUser')
			->with('principals/users/user2')
			->willReturn([[
				'id' => 666,
				'uri' => 'cal',
				'principaluri' => 'principals/users/user2',
				'{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new ScheduleCalendarTransp('opaque'),
				'{http://owncloud.org/ns}calendar-enabled' => '0',
			]]);
		$this->backend->method('getFederatedCalendarsForUser')
			->willReturn([]);

		// stored properties are already applied by the backend, only federated calendars are looked up
		$this->propertyMapper->expects($this->once())
			->method('findPropertiesByPathsAndUsers')
			->with([])
			->willReturn([]);

		$calendars = $this->provider->getCalendars('principals/users/user2');

		$this->assertCount(1, $calendars);
		$calendar = $calendars[0];
		$this->assertInstanceOf(CalendarImpl::class, $calendar);
		$this->assertSame('opaque', $calendar->getSchedulingTransparency()->getValue());
		$this->assertFalse($calendar->isEnabled());
	}
}
