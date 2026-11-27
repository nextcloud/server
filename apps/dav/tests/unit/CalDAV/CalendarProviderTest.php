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
use OCA\DAV\Db\Property;
use OCA\DAV\Db\PropertyMapper;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Test\TestCase;

class CalendarProviderTest extends TestCase {
	private const PATH = 'calendars/bob/personal_shared_by_alice';

	private CalDavBackend&MockObject $backend;
	private PropertyMapper&MockObject $propertyMapper;
	private CalendarProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->backend->method('getCalendarsForUser')->willReturn([[
			'id' => 42,
			'uri' => 'personal_shared_by_alice',
			'principaluri' => 'principals/users/bob',
			'{DAV:}displayname' => 'personal (alice)',
			'{http://owncloud.org/ns}owner-principal' => 'principals/users/alice',
			'{http://owncloud.org/ns}read-only' => true,
			'{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new ScheduleCalendarTransp('opaque'),
		]]);
		$this->backend->method('getFederatedCalendarsForUser')->willReturn([]);
		$this->propertyMapper = $this->createMock(PropertyMapper::class);

		$this->provider = new CalendarProvider(
			$this->backend,
			$this->createMock(IL10N::class),
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->propertyMapper,
		);
	}

	private function property(string $name, string $value): Property {
		$property = new Property();
		$property->setUserid('bob');
		$property->setPropertypath(self::PATH);
		$property->setPropertyname($name);
		$property->setPropertyvalue($value);
		return $property;
	}

	private function getSharedCalendar(): CalendarImpl {
		$calendars = $this->provider->getCalendars('principals/users/bob');
		$this->assertCount(1, $calendars);
		$this->assertInstanceOf(CalendarImpl::class, $calendars[0]);
		return $calendars[0];
	}

	public function testStoredPropertiesDoNotOverrideShareAccess(): void {
		$this->propertyMapper->method('findPropertiesByPathsAndUsers')->willReturn([
			$this->property('{http://owncloud.org/ns}read-only', '0'),
			$this->property('{http://owncloud.org/ns}owner-principal', 'principals/users/bob'),
			$this->property('{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp', 'O:44:"Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp":0:{}'),
		]);

		$calendar = $this->getSharedCalendar();

		$this->assertFalse($calendar->isWritable());
		$this->assertTrue($calendar->isShared());
		$this->assertSame('opaque', $calendar->getSchedulingTransparency()?->getValue());
	}

	public function testUserPropertiesAreApplied(): void {
		$this->propertyMapper->method('findPropertiesByPathsAndUsers')->willReturn([
			$this->property('{DAV:}displayname', 'Alice (mine)'),
			$this->property('{http://apple.com/ns/ical/}calendar-color', '#ff0000'),
			$this->property('{http://owncloud.org/ns}calendar-enabled', '0'),
		]);

		$calendar = $this->getSharedCalendar();

		$this->assertSame('Alice (mine)', $calendar->getDisplayName());
		$this->assertSame('#ff0000', $calendar->getDisplayColor());
		$this->assertFalse($calendar->isEnabled());
		$this->assertFalse($calendar->isWritable());
	}
}
