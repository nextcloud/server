<?php

declare(strict_types=1);

/**
 * @copyright 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author 2023 Daniel Kesselberg <mail@danielkesselberg.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\EventComparisonService;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Test\TestCase;

class EventComparisonServiceTest extends TestCase
{
	/** @var EventComparisonService */
	private $eventComparisonService;

	protected function setUp(): void
	{
		$this->eventComparisonService = new EventComparisonService();
	}

	public function testNoModifiedEvent(): void
	{
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

	public function testNewEvent(): void
	{
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

	public function testModifiedUnmodifiedEvent(): void
	{
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
}
