<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Import;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\Calendar\CalendarImportOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class ImportServiceTest extends \Test\TestCase {

	private ImportService $service;
	private CalendarImpl|MockObject $calendar;
	private CalDavBackend|MockObject $backend;
	private array $importResults = [];

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->service = new ImportService($this->backend);
		$this->calendar = $this->createMock(CalendarImpl::class);

	}

	public function testImport(): void {
		// Arrange
		// construct calendar with a 1 hour event and same start/end time zones
		$vCalendar = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $vCalendar->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		// construct stream from mock calendar
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $vCalendar->serialize());
		rewind($stream);
		// construct import options
		$options = new CalendarImportOptions();
		$options->setFormat('ical');

		// Mock calendar methods
		$this->calendar->expects($this->once())
			->method('getKey')
			->willReturn('calendar-id-123');
		$this->calendar->expects($this->once())
			->method('getPrincipalUri')
			->willReturn('principals/users/test-user');

		// Mock backend methods
		$this->backend->expects($this->once())
			->method('getCalendarObjectByUID')
			->with('principals/users/test-user', '96a0e6b1-d886-4a55-a60d-152b31401dcc')
			->willReturn(null); // Object doesn't exist, so it will be created

		$this->backend->expects($this->once())
			->method('createCalendarObject')
			->with(
				'calendar-id-123',
				$this->isType('string'), // Object ID (UUID)
				$this->isType('string')  // Object data
			);

		// Act
		$result = $this->service->import($stream, $this->calendar, $options);

		// Assert
		$this->assertIsArray($result);
		$this->assertCount(1, $result, 'Import result should contain one item');
		$this->assertArrayHasKey('96a0e6b1-d886-4a55-a60d-152b31401dcc', $result);
		$this->assertEquals('created', $result['96a0e6b1-d886-4a55-a60d-152b31401dcc']['outcome']);
	}

	public function testImportWithMultiLineUID(): void {
		// Arrange
		// construct calendar with a 1 hour event and same start/end time zones
		$vCalendar = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $vCalendar->add('VEVENT', []);
		$vEvent->UID->setValue('040000008200E00074C5B7101A82E00800000000000000000000000000000000000000004D0000004D14C68E6D285940B19A7D3D1DC1F8D23230323130363137743133333234387A2D383733323234373636303740666538303A303A303A303A33643A623066663A666533643A65383830656E7335');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		// construct stream from mock calendar
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $vCalendar->serialize());
		rewind($stream);
		// construct import options
		$options = new CalendarImportOptions();
		$options->setFormat('ical');

		$longUID = '040000008200E00074C5B7101A82E00800000000000000000000000000000000000000004D0000004D14C68E6D285940B19A7D3D1DC1F8D23230323130363137743133333234387A2D383733323234373636303740666538303A303A303A303A33643A623066663A666533643A65383830656E7335';

		// Mock calendar methods
		$this->calendar->expects($this->once())
			->method('getKey')
			->willReturn('calendar-id-123');
		$this->calendar->expects($this->once())
			->method('getPrincipalUri')
			->willReturn('principals/users/test-user');

		// Mock backend methods
		$this->backend->expects($this->once())
			->method('getCalendarObjectByUID')
			->with('principals/users/test-user', $longUID)
			->willReturn(null); // Object doesn't exist, so it will be created

		$this->backend->expects($this->once())
			->method('createCalendarObject')
			->with(
				'calendar-id-123',
				$this->isType('string'), // Object ID (UUID)
				$this->isType('string')  // Object data
			);

		// Act
		$result = $this->service->import($stream, $this->calendar, $options);

		// Assert
		$this->assertIsArray($result);
		$this->assertCount(1, $result, 'Import result should contain one item');
		$this->assertArrayHasKey($longUID, $result);
		$this->assertEquals('created', $result[$longUID]['outcome']);
	}
}
