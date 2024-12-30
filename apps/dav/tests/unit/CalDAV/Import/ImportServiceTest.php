<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Export;

use OCA\DAV\CalDAV\Import\ImportService;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\ICalendarImport;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;

class ImportServiceTest extends \Test\TestCase {

	private ImportService $service;
	private ICalendarImport|MockObject $calendar;
	private array $mockImportCollection = [];

	protected function setUp(): void {
		parent::setUp();

		$this->service = new ImportService();
		$this->calendar = $this->createMock(ICalendarImport::class);

	}

	public function mockCollector(CalendarImportOptions $options, callable $generator): array {
		foreach ($generator($options) as $entry) {
			$this->mockImportCollection[] = $entry;
		}
		return [];
	}
	
	public function testImport(): void {

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
		$this->calendar->expects($this->once())
			->method('import')
			->willReturnCallback($this->mockCollector(...));
		// test import
		$this->service->import($stream, $this->calendar, $options);
		$this->assertCount(1, $this->mockImportCollection, 'Imported items count is invalid');
		$this->assertTrue(isset($this->mockImportCollection[0]->VEVENT), 'Imported item missing VEVENT');
		$this->assertCount(1, $this->mockImportCollection[0]->VEVENT, 'Imported items count is invalid');

	}

}
