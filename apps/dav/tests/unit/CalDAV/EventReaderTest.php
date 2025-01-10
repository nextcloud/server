<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use DateTimeZone;
use OCA\DAV\CalDAV\EventReader;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

class EventReaderTest extends TestCase {

	private VCalendar $vCalendar1a;
	private VCalendar $vCalendar1b;
	private VCalendar $vCalendar1c;
	private VCalendar $vCalendar1d;
	private VCalendar $vCalendar1e;
	private VCalendar $vCalendar2;
	private VCalendar $vCalendar3;
	
	protected function setUp(): void {

		parent::setUp();

		// construct calendar with a 1 hour event and same start/end time zones
		$this->vCalendar1a = new VCalendar();
		$vEvent = $this->vCalendar1a->add('VEVENT', []);
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

		// construct calendar with a 1 hour event and different start/end time zones
		$this->vCalendar1b = new VCalendar();
		$vEvent = $this->vCalendar1b->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Vancouver']);
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a 1 hour event and global time zone
		$this->vCalendar1c = new VCalendar();
		// time zone component
		$vTimeZone = $this->vCalendar1c->add('VTIMEZONE');
		$vTimeZone->add('TZID', 'America/Toronto');
		// event component
		$vEvent = $this->vCalendar1c->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000');
		$vEvent->add('DTEND', '20240701T090000');
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a 1 hour event and no time zone
		$this->vCalendar1d = new VCalendar();
		$vEvent = $this->vCalendar1d->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000');
		$vEvent->add('DTEND', '20240701T090000');
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a 1 hour event and Microsoft time zone
		$this->vCalendar1e = new VCalendar();
		$vEvent = $this->vCalendar1e->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'Eastern Standard Time']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'Eastern Standard Time']);
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a full day event
		$this->vCalendar2 = new VCalendar();
		// time zone component
		$vTimeZone = $this->vCalendar2->add('VTIMEZONE');
		$vTimeZone->add('TZID', 'America/Toronto');
		// event component
		$vEvent = $this->vCalendar2->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701');
		$vEvent->add('DTEND', '20240702');
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a multi day event
		$this->vCalendar3 = new VCalendar();
		// time zone component
		$vTimeZone = $this->vCalendar3->add('VTIMEZONE');
		$vTimeZone->add('TZID', 'America/Toronto');
		// event component
		$vEvent = $this->vCalendar3->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701');
		$vEvent->add('DTEND', '20240706');
		$vEvent->add('SUMMARY', 'Test Recurrence Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		
	}

	public function testConstructFromCalendarString(): void {

		// construct event reader
		$er = new EventReader($this->vCalendar1a->serialize(), '96a0e6b1-d886-4a55-a60d-152b31401dcc');
		// test object creation
		$this->assertInstanceOf(EventReader::class, $er);

	}

	public function testConstructFromCalendarObject(): void {

		// construct event reader
		$er = new EventReader($this->vCalendar1a, '96a0e6b1-d886-4a55-a60d-152b31401dcc');
		// test object creation
		$this->assertInstanceOf(EventReader::class, $er);

	}

	public function testConstructFromEventObject(): void {

		// construct event reader
		$er = new EventReader($this->vCalendar1a->VEVENT[0]);
		// test object creation
		$this->assertInstanceOf(EventReader::class, $er);

	}

	public function testStartDateTime(): void {

		/** test day part event with same start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1a, $this->vCalendar1a->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->startDateTime());
		
		/** test day part event with different start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1b, $this->vCalendar1b->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->startDateTime());
		
		/** test day part event with global time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1c, $this->vCalendar1c->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->startDateTime());
		
		/** test day part event with no time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1d, $this->vCalendar1d->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('UTC')))), $er->startDateTime());

		/** test day part event with microsoft time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1e, $this->vCalendar1e->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->startDateTime());
		
		/** test full day event */
		// construct event reader
		$er = new EventReader($this->vCalendar2, $this->vCalendar2->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T000000', (new DateTimeZone('America/Toronto')))), $er->startDateTime());
		
		/** test multi day event */
		// construct event reader
		$er = new EventReader($this->vCalendar3, $this->vCalendar3->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T000000', (new DateTimeZone('America/Toronto')))), $er->startDateTime());

	}

	public function testStartTimeZone(): void {

		/** test day part event with same start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1a, $this->vCalendar1a->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->startTimeZone());
		
		/** test day part event with different start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1b, $this->vCalendar1b->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->startTimeZone());

		/** test day part event with global time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1c, $this->vCalendar1c->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->startTimeZone());

		/** test day part event with no time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1d, $this->vCalendar1d->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('UTC')), $er->startTimeZone());

		/** test day part event with microsoft time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1e, $this->vCalendar1e->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->startTimeZone());

		/** test full day event */
		// construct event reader
		$er = new EventReader($this->vCalendar2, $this->vCalendar2->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->startTimeZone());
		
		/** test multi day event */
		// construct event reader
		$er = new EventReader($this->vCalendar3, $this->vCalendar3->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->startTimeZone());

	}

	public function testEndDate(): void {

		/** test day part event with same start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1a, $this->vCalendar1a->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T090000', (new DateTimeZone('America/Toronto')))), $er->endDateTime());
		
		/** test day part event with different start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1b, $this->vCalendar1b->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T090000', (new DateTimeZone('America/Vancouver')))), $er->endDateTime());
		
		/** test day part event with global time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1c, $this->vCalendar1c->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T090000', (new DateTimeZone('America/Toronto')))), $er->endDateTime());
		
		/** test day part event with no time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1d, $this->vCalendar1d->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T090000', (new DateTimeZone('UTC')))), $er->endDateTime());

		/** test day part event with microsoft time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1e, $this->vCalendar1e->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240701T090000', (new DateTimeZone('America/Toronto')))), $er->endDateTime());
		
		/** test full day event */
		// construct event reader
		$er = new EventReader($this->vCalendar2, $this->vCalendar2->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240702T000000', (new DateTimeZone('America/Toronto')))), $er->endDateTime());
		
		/** test multi day event */
		// construct event reader
		$er = new EventReader($this->vCalendar3, $this->vCalendar3->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240706T000000', (new DateTimeZone('America/Toronto')))), $er->endDateTime());

	}

	public function testEndTimeZone(): void {

		/** test day part event with same start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1a, $this->vCalendar1a->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->endTimeZone());
		
		/** test day part event with different start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1b, $this->vCalendar1b->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Vancouver')), $er->endTimeZone());

		/** test day part event with global time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1c, $this->vCalendar1c->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->endTimeZone());

		/** test day part event with no time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1d, $this->vCalendar1d->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('UTC')), $er->endTimeZone());

		/** test day part event with microsoft time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1e, $this->vCalendar1e->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->endTimeZone());

		/** test full day event */
		// construct event reader
		$er = new EventReader($this->vCalendar2, $this->vCalendar2->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->endTimeZone());
		
		/** test multi day event */
		// construct event reader
		$er = new EventReader($this->vCalendar3, $this->vCalendar3->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new DateTimeZone('America/Toronto')), $er->endTimeZone());

	}

	public function testEntireDay(): void {

		/** test day part event with same start/end time zone */
		// construct event reader
		$er = new EventReader($this->vCalendar1a, $this->vCalendar1a->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertFalse($er->entireDay());

		/** test full day event */
		// construct event reader
		$er = new EventReader($this->vCalendar2, $this->vCalendar2->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->entireDay());

		/** test multi day event */
		// construct event reader
		$er = new EventReader($this->vCalendar3, $this->vCalendar3->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->entireDay());

	}

	public function testRecurs(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertFalse($er->recurs());

		/** test rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurs());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurs());

	}

	public function testRecurringPattern(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringPattern());

		/** test absolute rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('A', $er->recurringPattern());

		/** test relative rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('R', $er->recurringPattern());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('A', $er->recurringPattern());

	}

	public function testRecurringPrecision(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringPrecision());

		/** test daily rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('daily', $er->recurringPrecision());

		/** test weekly rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('weekly', $er->recurringPrecision());

		/** test monthly rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8,15');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('monthly', $er->recurringPrecision());

		/** test yearly rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYMONTHDAY=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('yearly', $er->recurringPrecision());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals('fixed', $er->recurringPrecision());

	}

	public function testRecurringInterval(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringInterval());

		/** test daily rrule recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(2, $er->recurringInterval());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringInterval());

	}

	public function testRecurringConcludes(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertFalse($er->recurringConcludes());

		/** test rrule recurrance with no end */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertFalse($er->recurringConcludes());

		/** test rrule recurrance with until date end */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;UNTIL=20240712T080000Z;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurringConcludes());

		/** test rrule recurrance with iteration end */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurringConcludes());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurringConcludes());

		/** test rdate (multiple property instances) recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703');
		$vCalendar->VEVENT[0]->add('RDATE', '20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurringConcludes());

		/** test rrule and rdate recurrance with rdate as last date */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		$vCalendar->VEVENT[0]->add('RDATE', '20240706,20240715');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurringConcludes());

		/** test rrule and rdate recurrance with rrule as last date */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=7;BYDAY=MO,WE,FR');
		$vCalendar->VEVENT[0]->add('RDATE', '20240706,20240713');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertTrue($er->recurringConcludes());

	}

	public function testRecurringConcludesAfter(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringConcludesAfter());

		/** test rrule recurrance with count */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(6, $er->recurringConcludesAfter());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(2, $er->recurringConcludesAfter());

		/** test rdate (multiple property instances) recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703');
		$vCalendar->VEVENT[0]->add('RDATE', '20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(2, $er->recurringConcludesAfter());

		/** test rrule and rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		$vCalendar->VEVENT[0]->add('RDATE', '20240706,20240715');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(8, $er->recurringConcludesAfter());

	}

	public function testRecurringConcludesOn(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringConcludesOn());

		/** test rrule recurrance with no end */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertNull($er->recurringConcludesOn());

		/** test rrule recurrance with until date end */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;UNTIL=20240712T080000Z;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor

		// TODO: Fix until time zone
		//$this->assertEquals((new \DateTime('20240712T080000', (new DateTimeZone('America/Toronto')))), $er->recurringConcludesOn());

		/** test rdate recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703,20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240705T000000', (new DateTimeZone('America/Toronto')))), $er->recurringConcludesOn());

		/** test rdate (multiple property instances) recurrance */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703');
		$vCalendar->VEVENT[0]->add('RDATE', '20240705');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240705T000000', (new DateTimeZone('America/Toronto')))), $er->recurringConcludesOn());

		/** test rrule and rdate recurrance with rdate as last date */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=6;BYDAY=MO,WE,FR');
		$vCalendar->VEVENT[0]->add('RDATE', '20240706,20240715');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240715T000000', (new DateTimeZone('America/Toronto')))), $er->recurringConcludesOn());

		/** test rrule and rdate recurrance with rrule as last date */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;COUNT=7;BYDAY=MO,WE,FR');
		$vCalendar->VEVENT[0]->add('RDATE', '20240706,20240713');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals((new \DateTime('20240715T080000', (new DateTimeZone('America/Toronto')))), $er->recurringConcludesOn());

	}

	public function testRecurringDaysOfWeek(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringDaysOfWeek());

		/** test rrule recurrance with weekly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;UNTIL=20240712T080000Z;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(['MO','WE','FR'], $er->recurringDaysOfWeek());

	}

	public function testRecurringDaysOfWeekNamed(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringDaysOfWeekNamed());

		/** test rrule recurrance with weekly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;UNTIL=20240712T080000Z;BYDAY=MO,WE,FR');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(['Monday','Wednesday','Friday'], $er->recurringDaysOfWeekNamed());

	}

	public function testRecurringDaysOfMonth(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringDaysOfMonth());

		/** test rrule recurrance with monthly absolute dates*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=6,13,20,27');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([6,13,20,27], $er->recurringDaysOfMonth());

	}

	public function testRecurringDaysOfYear(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringDaysOfYear());

		/** test rrule recurrance with monthly absolute dates*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYYEARDAY=1,30,180,365');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([1,30,180,365], $er->recurringDaysOfYear());

	}

	public function testRecurringWeeksOfMonth(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringWeeksOfMonth());

		/** test rrule recurrance with monthly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([1], $er->recurringWeeksOfMonth());

	}

	public function testRecurringWeeksOfMonthNamed(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringWeeksOfMonthNamed());

		/** test rrule recurrance with weekly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(['First'], $er->recurringWeeksOfMonthNamed());

	}

	public function testRecurringWeeksOfYear(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringWeeksOfYear());

		/** test rrule recurrance with monthly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;INTERVAL=1;BYWEEKNO=35,42;BYDAY=TU');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([35,42], $er->recurringWeeksOfYear());

	}

	public function testRecurringMonthsOfYear(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringMonthsOfYear());

		/** test rrule recurrance with monthly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;INTERVAL=1;BYMONTH=7;BYMONTHDAY=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([7], $er->recurringMonthsOfYear());

	}

	public function testRecurringMonthsOfYearNamed(): void {

		/** test no recurrance */
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals([], $er->recurringMonthsOfYearNamed());

		/** test rrule recurrance with weekly days*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;INTERVAL=1;BYMONTH=7;BYMONTHDAY=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test set by constructor
		$this->assertEquals(['July'], $er->recurringMonthsOfYearNamed());

	}

	public function testRecurringIterationDaily(): void {

		/** test rrule recurrance with daily frequency*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=3;UNTIL=20240714T040000Z');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240704T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240707T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240710T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240713T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20240709T080000')));
		$this->assertEquals((new \DateTime('20240710T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

	public function testRecurringIterationWeekly(): void {

		/** test rrule recurrance with weekly frequency*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20240713T040000Z');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240703T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240705T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240708T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240710T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240712T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20240709T080000')));
		$this->assertEquals((new \DateTime('20240710T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

	public function testRecurringIterationMonthlyAbsolute(): void {

		/** test rrule recurrance with monthly absolute frequency on the 1st of each month*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;COUNT=3;BYMONTHDAY=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240801T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240901T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20240809T080000')));
		$this->assertEquals((new \DateTime('20240901T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

	public function testRecurringIterationMonthlyRelative(): void {

		/** test rrule recurrance with monthly relative frequency on the first monday of each month*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;COUNT=3;BYDAY=MO;BYSETPOS=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240805T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240902T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20240809T080000')));
		$this->assertEquals((new \DateTime('20240902T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

	public function testRecurringIterationYearlyAbsolute(): void {

		/** test rrule recurrance with yearly absolute frequency on the 1st of july*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;COUNT=3;BYMONTH=7');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20250701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20260701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20250809T080000')));
		$this->assertEquals((new \DateTime('20260701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

	public function testRecurringIterationYearlyRelative(): void {

		/** test rrule recurrance with yearly relative frequency on the first monday of july*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;COUNT=3;BYMONTH=7;BYDAY=MO;BYSETPOS=1');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20250707T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20260706T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20250809T080000')));
		$this->assertEquals((new \DateTime('20260706T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

	public function testRecurringIterationFixed(): void {

		/** test rrule recurrance with yearly relative frequency on the first monday of july*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703T080000,20240905T080000,20241231T080000');
		// construct event reader
		$er = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test initial recurrance
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240703T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20240905T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvance();
		$this->assertEquals((new \DateTime('20241231T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance (This is past the last recurrance and should return null)
		$er->recurrenceAdvance();
		$this->assertNull($er->recurrenceDate());
		// test rewind to initial recurrance
		$er->recurrenceRewind();
		$this->assertEquals((new \DateTime('20240701T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());
		// test next recurrance
		$er->recurrenceAdvanceTo((new \DateTime('20240809T080000')));
		$this->assertEquals((new \DateTime('20240905T080000', (new DateTimeZone('America/Toronto')))), $er->recurrenceDate());

	}

}
