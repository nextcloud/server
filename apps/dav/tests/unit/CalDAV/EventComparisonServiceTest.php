<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\EventComparisonService;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

class EventComparisonServiceTest extends TestCase {
	/** @var EventComparisonService */
	private $eventComparisonService;

	protected function setUp(): void {
		$this->eventComparisonService = new EventComparisonService();
	}

	public function testNoModifiedEvent(): void {
		$vCalendarOld = new VCalendar();
		$vCalendarNew = new VCalendar();

		$vEventOld = $vCalendarOld->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventOld->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventOld->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$vEventNew = $vCalendarNew->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventNew->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventNew->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$result = $this->eventComparisonService->findModified($vCalendarNew, $vCalendarOld);
		$this->assertEmpty($result['old']);
		$this->assertEmpty($result['new']);
	}

	public function testNewEvent(): void {
		$vCalendarOld = null;
		$vCalendarNew = new VCalendar();

		$vEventNew = $vCalendarNew->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventNew->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventNew->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$result = $this->eventComparisonService->findModified($vCalendarNew, $vCalendarOld);
		$this->assertNull($result['old']);
		$this->assertEquals([$vEventNew], $result['new']);
	}

	public function testModifiedUnmodifiedEvent(): void {
		$vCalendarOld = new VCalendar();
		$vCalendarNew = new VCalendar();

		$vEventOld1 = $vCalendarOld->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);
		$vEventOld1->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventOld1->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$vEventOld2 = $vCalendarOld->add('VEVENT', [
			'UID' => 'uid-1235',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);
		$vEventOld2->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventOld2->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$vEventNew1 = $vCalendarNew->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);
		$vEventNew1->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventNew1->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$vEventNew2 = $vCalendarNew->add('VEVENT', [
			'UID' => 'uid-1235',
			'LAST-MODIFIED' => 123457,
			'SEQUENCE' => 3,
			'SUMMARY' => 'Fellowship meeting 2',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);
		$vEventNew2->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventNew2->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$result = $this->eventComparisonService->findModified($vCalendarNew, $vCalendarOld);
		$this->assertEquals([$vEventOld2], $result['old']);
		$this->assertEquals([$vEventNew2], $result['new']);
	}

	// First test to certify fix for issue nextcloud/server#41084
	public function testSequenceNumberIncrementDetectedForFirstModificationToEventWithoutZeroInit(): void {
		$vCalendarOld = new VCalendar();
		$vCalendarNew = new VCalendar();

		$vEventOld = $vCalendarOld->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			// 'SEQUENCE' => 0,			// sequence number may not be set to zero during event creation and instead fully omitted
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventOld->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventOld->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$vEventNew = $vCalendarNew->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventNew->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventNew->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$result = $this->eventComparisonService->findModified($vCalendarNew, $vCalendarOld);
		$this->assertEquals([$vEventOld], $result['old']);
		$this->assertEquals([$vEventNew], $result['new']);
	}

	// Second test to certify fix for issue nextcloud/server#41084
	public function testSequenceNumberIncrementDetectedForFirstModificationToEventWithZeroInit(): void {
		$vCalendarOld = new VCalendar();
		$vCalendarNew = new VCalendar();

		$vEventOld = $vCalendarOld->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventOld->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventOld->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$vEventNew = $vCalendarNew->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);
		$vEventNew->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$vEventNew->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);

		$result = $this->eventComparisonService->findModified($vCalendarNew, $vCalendarOld);
		$this->assertEquals([$vEventOld], $result['old']);
		$this->assertEquals([$vEventNew], $result['new']);
	}


}
