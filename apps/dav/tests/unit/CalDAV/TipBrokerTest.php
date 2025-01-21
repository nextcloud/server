<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\TipBroker;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

class TipBrokerTest extends TestCase {

	private TipBroker $broker;
	private VCalendar $vCalendar1a;

	protected function setUp(): void {
		parent::setUp();

		$this->broker = new TipBroker();
		// construct calendar with a 1 hour event and same start/end time zones
		$this->vCalendar1a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar1a->add('VEVENT', []);
		$vEvent->add('UID', '96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTAMP', '20240701T000000Z');
		$vEvent->add('CREATED', '20240701T000000Z');
		$vEvent->add('LAST-MODIFIED', '20240701T000000Z');
		$vEvent->add('SEQUENCE', '1');
		$vEvent->add('STATUS', 'CONFIRMED');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
	}

	public function testParseEventForOrganizerOnCreate(): void {
		
		// construct calendar and generate event info for newly created event with one attendee
		$calendar = clone $this->vCalendar1a;
		$previousEventInfo = [
			'organizer' => null,
			'significantChangeHash' => '',
			'attendees' => [],
		];
		$currentEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$calendar, $currentEventInfo, $previousEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);

	}

	public function testParseEventForOrganizerOnModify(): void {
		
		// construct calendar and generate event info for modified event with one attendee
		$calendar = clone $this->vCalendar1a;
		$previousEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		$calendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$calendar->VEVENT->SEQUENCE->setValue(2);
		$calendar->VEVENT->SUMMARY->setValue('Test Event Modified');
		$currentEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$calendar, $currentEventInfo, $previousEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);

	}

	public function testParseEventForOrganizerOnDelete(): void {
		
		// construct calendar and generate event info for modified event with one attendee
		$calendar = clone $this->vCalendar1a;
		$previousEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		$currentEventInfo = $previousEventInfo;
		$currentEventInfo['attendees'] = [];
		++$currentEventInfo['sequence'];
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$calendar, $currentEventInfo, $previousEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);

	}

	public function testParseEventForOrganizerOnStatusCancelled(): void {
		
		// construct calendar and generate event info for modified event with one attendee
		$calendar = clone $this->vCalendar1a;
		$previousEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		$calendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$calendar->VEVENT->SEQUENCE->setValue(2);
		$calendar->VEVENT->STATUS->setValue('CANCELLED');
		$currentEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$calendar, $currentEventInfo, $previousEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);

	}

	public function testParseEventForOrganizerOnAddAttendee(): void {
		
		// construct calendar and generate event info for modified event with two attendees
		$calendar = clone $this->vCalendar1a;
		$previousEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		$calendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$calendar->VEVENT->SEQUENCE->setValue(2);
		$calendar->VEVENT->add('ATTENDEE', 'mailto:attendee2@testing.com', [
			'CN' => 'Attendee Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$currentEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$calendar, $currentEventInfo, $previousEventInfo]);
		$this->assertCount(2, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertEquals('REQUEST', $messages[1]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[1]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[1]->getValue(), $messages[1]->recipient);

	}

	public function testParseEventForOrganizerOnRemoveAttendee(): void {
		
		// construct calendar and generate event info for modified event with two attendees
		$calendar = clone $this->vCalendar1a;
		$calendar->VEVENT->add('ATTENDEE', 'mailto:attendee2@testing.com', [
			'CN' => 'Attendee Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$previousEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		$calendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$calendar->VEVENT->SEQUENCE->setValue(2);
		$calendar->VEVENT->remove('ATTENDEE');
		$calendar->VEVENT->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$currentEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$calendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$calendar, $currentEventInfo, $previousEventInfo]);
		$this->assertCount(2, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($calendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertEquals('CANCEL', $messages[1]->method);
		$this->assertEquals($calendar->VEVENT->ORGANIZER->getValue(), $messages[1]->sender);
		$this->assertEquals('mailto:attendee2@testing.com', $messages[1]->recipient);

	}

}
