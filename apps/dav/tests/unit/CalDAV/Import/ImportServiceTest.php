<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Import;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\Import\ImportCountEvent;
use OCA\DAV\CalDAV\Import\ImportDisposition;
use OCA\DAV\CalDAV\Import\ImportObjectEvent;
use OCA\DAV\CalDAV\Import\ImportService;
use OCP\Calendar\CalendarImportOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Writer;

class ImportServiceTest extends \Test\TestCase {

	private ImportService $service;
	private CalendarImpl|MockObject $calendar;
	private CalDavBackend|MockObject $backend;

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
		$options->setCounts(true);

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
		$result = iterator_to_array($this->service->import($stream, $this->calendar, $options), false);

		// Assert
		$this->assertIsArray($result);
		$this->assertCount(2, $result, 'Import result should contain counts and one item');
		$this->assertInstanceOf(ImportCountEvent::class, $result[0]);
		$this->assertSame(1, $result[0]->vevent);
		$this->assertSame(0, $result[0]->vtodo);
		$this->assertSame(0, $result[0]->vjournal);
		$this->assertInstanceOf(ImportObjectEvent::class, $result[1]);
		$this->assertSame('96a0e6b1-d886-4a55-a60d-152b31401dcc', $result[1]->identifier);
		$this->assertSame(ImportDisposition::Created, $result[1]->disposition);
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
		$result = iterator_to_array($this->service->import($stream, $this->calendar, $options), false);

		// Assert
		$this->assertIsArray($result);
		$this->assertCount(1, $result, 'Import result should contain one item');
		$this->assertInstanceOf(ImportObjectEvent::class, $result[0]);
		$this->assertSame($longUID, $result[0]->identifier);
		$this->assertSame(ImportDisposition::Created, $result[0]->disposition);
	}

	public static function importWithoutUIDProvider(): array {
		return [
			'ical' => ['ical'],
			'xcal' => ['xcal'],
		];
	}

	#[DataProvider('importWithoutUIDProvider')]
	public function testImportWithoutUID(string $format): void {
		// events without a UID surround an event with a numeric UID that PHP casts to an integer array key
		$vCalendar = new VCalendar();
		foreach ([null, '0', null] as $index => $uid) {
			/** @var VEvent $vEvent */
			$vEvent = $vCalendar->add('VEVENT', []);
			if ($uid === null) {
				$vEvent->remove('UID');
			} else {
				$vEvent->UID->setValue($uid);
			}
			$vEvent->add('DTSTART', '20240701T080000');
			$vEvent->add('DTEND', '20240701T090000');
			$vEvent->add('SUMMARY', 'Test Event ' . $index);
		}
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $format === 'xcal' ? Writer::writeXml($vCalendar) : $vCalendar->serialize());
		rewind($stream);
		$options = new CalendarImportOptions();
		$options->setFormat($format);
		$options->setCounts(true);
		$options->setErrors(CalendarImportOptions::ERROR_CONTINUE);

		$this->calendar->method('getKey')->willReturn('calendar-id-123');
		$this->calendar->method('getPrincipalUri')->willReturn('principals/users/test-user');
		$this->backend->method('getCalendarObjectByUID')->willReturn(null);
		$this->backend->expects($this->once())
			->method('createCalendarObject')
			->with(
				'calendar-id-123',
				$this->isType('string'),
				$this->stringContains('SUMMARY:Test Event 1')
			);

		$result = iterator_to_array($this->service->import($stream, $this->calendar, $options), false);

		$this->assertCount(4, $result);
		$this->assertInstanceOf(ImportCountEvent::class, $result[0]);
		$this->assertSame(3, $result[0]->vevent);
		$this->assertSame(
			[ImportDisposition::Error, ImportDisposition::Created, ImportDisposition::Error],
			array_map(fn (ImportObjectEvent $event) => $event->disposition, array_slice($result, 1))
		);
		$this->assertSame([null, '0', null], array_map(fn (ImportObjectEvent $event) => $event->identifier, array_slice($result, 1)));
		$this->assertSame(['One or more objects discovered without a UID'], $result[1]->errors);
	}
}
