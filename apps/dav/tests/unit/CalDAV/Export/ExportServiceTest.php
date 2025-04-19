<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Export;

use Generator;
use OCA\DAV\CalDAV\Export\ExportService;
use OCP\Calendar\CalendarExportOptions;
use OCP\Calendar\ICalendarExport;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;

class ExportServiceTest extends \Test\TestCase {

	private ExportService $service;
	private ICalendarExport|MockObject $calendar;
	private array $mockExportCollection;

	protected function setUp(): void {
		parent::setUp();

		$this->service = new ExportService();
		$this->calendar = $this->createMock(ICalendarExport::class);

	}

	protected function mockGenerator(): Generator {
		foreach ($this->mockExportCollection as $entry) {
			yield $entry;
		}
	}
	
	public function testExport(): void {

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
		// construct calendar return
		$options = new CalendarExportOptions();
		$this->mockExportCollection[] = $vCalendar;
		$this->calendar->expects($this->once())
			->method('export')
			->with($options)
			->willReturn($this->mockGenerator());
		// test export
		$document = '';
		foreach ($this->service->export($this->calendar, $options) as $chunk) {
			$document .= $chunk;
		}
		$this->assertTrue(str_contains($document, 'BEGIN:VCALENDAR'), 'Exported document calendar start missing');
		$this->assertTrue(str_contains($document, 'BEGIN:VEVENT'), 'Exported document event start missing');
		$this->assertTrue(str_contains($document, 'END:VEVENT'), 'Exported document event end missing');
		$this->assertTrue(str_contains($document, 'END:VCALENDAR'), 'Exported document calendar end missing');

	}

}
