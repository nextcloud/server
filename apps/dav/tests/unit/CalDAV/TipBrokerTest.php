<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\TipBroker;
use Sabre\VObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message;
use Test\TestCase;

class TipBrokerTest extends TestCase {

	private TipBroker $broker;
	private VCalendar $vCalendar1a;
	private VCalendar $vCalendar2a;
	private array $templateEventInfo;

	protected function setUp(): void {
		parent::setUp();

		VCalendar::$propertyMap[TipBroker::INVITATION_FORWARDING_PROPERTY] = VObject\Property\Boolean::class;

		$this->broker = new TipBroker();

		$this->templateEventInfo = [
			'organizer' => null,
			'attendees' => [],
			'significantChangeHash' => '',
		];
		// construct calendar with a 1 hour event and same start/end time zones
		$this->vCalendar1a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar1a->add('VEVENT', []);
		$vEvent->add('UID', '96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTAMP', '20240701T000000Z');
		$vEvent->add('CREATED', '20240701T000000Z');
		$vEvent->add('LAST-MODIFIED', '20240701T000000Z');
		$vEvent->add('SEQUENCE', 1);
		$vEvent->add('STATUS', 'CONFIRMED');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Test Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@example.org', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@example.org', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);

		// construct calendar with a 1 hour event and same start/end time zones
		// recurring every week on Monday for 12 weeks
		$this->vCalendar2a = new VCalendar();
		/** @var VEvent $vEvent */
		$vEvent = $this->vCalendar2a->add('VEVENT', []);
		$vEvent->add('UID', '96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTAMP', '20240701T000000Z');
		$vEvent->add('CREATED', '20240701T000000Z');
		$vEvent->add('LAST-MODIFIED', '20240701T000000Z');
		$vEvent->add('SEQUENCE', 1);
		$vEvent->add('STATUS', 'CONFIRMED');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('RRULE', 'FREQ=WEEKLY;COUNT=12;BYDAY=MO');
		$vEvent->add('SUMMARY', 'Test Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@example.org', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@example.org', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
	}

	protected function tearDown(): void {
		unset(VCalendar::$propertyMap[TipBroker::INVITATION_FORWARDING_PROPERTY]);

		parent::tearDown();
	}

	/**
	 * Tests user creating a new singleton or recurring event
	 */
	public function testParseEventForOrganizerCreated(): void {
		// construct calendar and generate event info for newly created event with one attendee
		$mutatedCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->templateEventInfo;
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests user modifying an existing singleton or recurring (base) event
	 */
	public function testParseEventForOrganizerModified(): void {
		// construct calendar and generate event info for modified event with one attendee
		$originalCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedCalendar = clone $this->vCalendar1a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->SUMMARY->setValue('Test Event Modified');
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests user deleting an existing singleton or recurring (base) event
	 */
	public function testParseEventForOrganizerDeleted(): void {
		// construct calendar and generate event info for modified event with one attendee
		$originalCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedEventInfo = $originalEventInfo;
		$mutatedEventInfo['attendees'] = [];
		++$mutatedEventInfo['sequence'];
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$originalCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals($originalCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($originalCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests user cancelling an existing singleton or recurring (base) event
	 */
	public function testParseEventForOrganizerStatusCancelled(): void {
		// construct calendar and generate event info for modified event with one attendee
		$originalCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedCalendar = clone $this->vCalendar1a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->STATUS->setValue('CANCELLED');
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests user adding an attendee to an existing singleton or recurring (base) event
	 */
	public function testParseEventForOrganizerAddAttendee(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedCalendar = clone $this->vCalendar1a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->add('ATTENDEE', 'mailto:attendee2@example.org', [
			'CN' => 'Attendee Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(2, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertEquals('REQUEST', $messages[1]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[1]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[1]->getValue(), $messages[1]->recipient);
	}

	/**
	 * Tests user removing an attendee from an existing singleton or recurring (base) event
	 */
	public function testParseEventForOrganizerRemoveAttendee(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar1a;
		$originalCalendar->VEVENT->add('ATTENDEE', 'mailto:attendee2@example.org', [
			'CN' => 'Attendee Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedCalendar = clone $this->vCalendar1a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->remove('ATTENDEE');
		$mutatedCalendar->VEVENT->add('ATTENDEE', 'mailto:attendee1@example.org', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(2, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertEquals('CANCEL', $messages[1]->method);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[1]->sender);
		$this->assertEquals('mailto:attendee2@example.org', $messages[1]->recipient);
	}


	/**
	 * Tests user converts existing singleton or recurring (base) event from attended to attendeless
	 */
	public function testParseEventForOrganizerRemoveOrganizerAndAttendees(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedCalendar = clone $this->vCalendar1a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->remove('ORGANIZER');
		$mutatedCalendar->VEVENT->remove('ATTENDEE');
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals(2, $messages[0]->sequence);
		$this->assertEquals($originalCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($originalCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests user modifying recurring (base) event by moving instance to a new date
	 */
	public function testParseEventForOrganizerCreatedInstance(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar2a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedInstance = clone $originalCalendar->VEVENT;
		$mutatedInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$mutatedInstance->SEQUENCE->setValue(0);
		$mutatedInstance->DTSTART->setValue('20240717T080000');
		$mutatedInstance->DTEND->setValue('20240717T090000');
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->add($mutatedInstance);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(1, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertCount(2, $messages[0]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[0]->message->VEVENT[1]->{'RECURRENCE-ID'}->getValue());

	}

	/**
	 * Tests user modifying recurring (base) event by cancelling a single instance
	 */
	public function testParseEventForOrganizerCreatedInstanceCancelled(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar2a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedInstance = clone $originalCalendar->VEVENT;
		$mutatedInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$mutatedInstance->SEQUENCE->setValue(0);
		$mutatedInstance->STATUS->setValue('CANCELLED');
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->add($mutatedInstance);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(2, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals(1, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertCount(1, $messages[0]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[0]->message->VEVENT->{'RECURRENCE-ID'}->getValue());

	}

	/**
	 * Tests user modifying recurring (instance) event with non status or attendee changes
	 */
	public function testParseEventForOrganizerModifyInstance(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar2a;
		$originalInstance = clone $originalCalendar->VEVENT;
		$originalInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$originalInstance->SEQUENCE->setValue(1);
		$originalInstance->DTSTART->setValue('20240717T080000');
		$originalInstance->DTEND->setValue('20240717T090000');
		$originalCalendar->add($originalInstance);
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);

		$mutatedInstance = clone $originalInstance;
		$mutatedInstance->SEQUENCE->setValue(2);
		$mutatedInstance->DTSTART->setValue('20240718T080000');
		$mutatedInstance->DTEND->setValue('20240718T090000');
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->add($mutatedInstance);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(1, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertCount(2, $messages[0]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[0]->message->VEVENT[1]->{'RECURRENCE-ID'}->getValue());

	}

	/**
	 * Tests user modifying recurring (instance) event by setting status to cancelled
	 */
	public function testParseEventForOrganizerModifyInstanceStatus(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar2a;
		$originalInstance = clone $originalCalendar->VEVENT;
		$originalInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$originalInstance->SEQUENCE->setValue(1);
		$originalInstance->DTSTART->setValue('20240717T080000');
		$originalInstance->DTEND->setValue('20240717T090000');
		$originalCalendar->add($originalInstance);
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedInstance = clone $originalInstance;
		$mutatedInstance->SEQUENCE->setValue(2);
		$mutatedInstance->STATUS->setValue('CANCELLED');
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->add($mutatedInstance);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(1, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertCount(2, $messages[0]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[0]->message->VEVENT[1]->{'RECURRENCE-ID'}->getValue());

	}

	/**
	 * Tests user modifying recurring (instance) event by adding attendee
	 */
	public function testParseEventForOrganizerModifyInstanceAddAttendee(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar2a;
		$originalInstance = clone $originalCalendar->VEVENT;
		$originalInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$originalInstance->SEQUENCE->setValue(1);
		$originalInstance->DTSTART->setValue('20240717T080000');
		$originalInstance->DTEND->setValue('20240717T090000');
		$originalCalendar->add($originalInstance);
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedInstance = clone $originalInstance;
		$mutatedInstance->SEQUENCE->setValue(2);
		$mutatedInstance->add('ATTENDEE', 'mailto:attendee2@example.org', [
			'CN' => 'Attendee Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->add($mutatedInstance);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(2, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(1, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertCount(2, $messages[0]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[0]->message->VEVENT[1]->{'RECURRENCE-ID'}->getValue());
		$this->assertEquals('REQUEST', $messages[1]->method);
		$this->assertEquals(1, $messages[1]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[1]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT[1]->ATTENDEE[1]->getValue(), $messages[1]->recipient);
		$this->assertCount(1, $messages[1]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[1]->message->VEVENT->{'RECURRENCE-ID'}->getValue());

	}

	/**
	 * Tests user modifying recurring (instance) event by removing attendee
	 */
	public function testParseEventForOrganizerModifyInstanceRemoveAttendee(): void {
		// construct calendar and generate event info for modified event with two attendees
		$originalCalendar = clone $this->vCalendar2a;
		$originalInstance = clone $originalCalendar->VEVENT;
		$originalInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$originalInstance->SEQUENCE->setValue(1);
		$originalInstance->DTSTART->setValue('20240717T080000');
		$originalInstance->DTEND->setValue('20240717T090000');
		$originalInstance->add('ATTENDEE', 'mailto:attendee2@example.org', [
			'CN' => 'Attendee Two',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$originalCalendar->add($originalInstance);
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		$mutatedInstance = clone $originalInstance;
		$mutatedInstance->SEQUENCE->setValue(2);
		$mutatedInstance->remove('ATTENDEE');
		$mutatedInstance->add('ATTENDEE', 'mailto:attendee1@example.org', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->add($mutatedInstance);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		// attendee modifications get generated in order of Added, Removed, Existing
		$this->assertCount(2, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(1, $messages[0]->sequence);
		$this->assertEquals($originalCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($originalCalendar->VEVENT[1]->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		$this->assertCount(2, $messages[0]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[0]->message->VEVENT[1]->{'RECURRENCE-ID'}->getValue());
		$this->assertEquals('CANCEL', $messages[1]->method);
		$this->assertEquals(1, $messages[1]->sequence);
		$this->assertEquals($originalCalendar->VEVENT[1]->ORGANIZER->getValue(), $messages[1]->sender);
		$this->assertEquals($originalCalendar->VEVENT[1]->ATTENDEE[1]->getValue(), $messages[1]->recipient);
		$this->assertCount(1, $messages[1]->message->VEVENT);
		$this->assertEquals('20240715T080000', $messages[1]->message->VEVENT->{'RECURRENCE-ID'}->getValue());

	}

	/**
	 * Tests user deleting master instance of recurring event
	 */
	public function testParseEventForOrganizerDeleteMasterInstance(): void {
		// construct calendar with recurring event
		$originalCalendar = clone $this->vCalendar2a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		// delete the master instance (convert to non-scheduling)
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->remove('ORGANIZER');
		$mutatedCalendar->VEVENT->remove('ATTENDEE');
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals(2, $messages[0]->sequence);
		$this->assertEquals($originalCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($originalCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests user adding EXDATE to master instance
	 */
	public function testParseEventForOrganizerAddExdate(): void {
		// construct calendar with recurring event
		$originalCalendar = clone $this->vCalendar2a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		// add EXDATE to exclude specific occurrences
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->add('EXDATE', ['20240715T080000', '20240722T080000'], ['TZID' => 'America/Toronto']);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(2, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		// verify EXDATE is present in the message
		$this->assertTrue(isset($messages[0]->message->VEVENT->EXDATE));
		$exdates = $messages[0]->message->VEVENT->EXDATE->getParts();
		$this->assertContains('20240715T080000', $exdates);
		$this->assertContains('20240722T080000', $exdates);
	}

	/**
	 * Tests user removing EXDATE from master instance
	 */
	public function testParseEventForOrganizerRemoveExdate(): void {
		// construct calendar with recurring event that has EXDATE
		$originalCalendar = clone $this->vCalendar2a;
		$originalCalendar->VEVENT->add('EXDATE', ['20240715T080000', '20240722T080000'], ['TZID' => 'America/Toronto']);
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		// remove EXDATE to restore excluded occurrences
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(2, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
		// verify EXDATE is not present in the message
		$this->assertFalse(isset($messages[0]->message->VEVENT->EXDATE));
	}

	/**
	 * Tests user converting recurring event to non-scheduling
	 */
	public function testParseEventForOrganizerConvertRecurringToNonScheduling(): void {
		// construct calendar with recurring event
		$originalCalendar = clone $this->vCalendar2a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		// remove ORGANIZER and ATTENDEE properties to convert to non-scheduling
		$mutatedCalendar = clone $this->vCalendar2a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->remove('ORGANIZER');
		$mutatedCalendar->VEVENT->remove('ATTENDEE');
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('CANCEL', $messages[0]->method);
		$this->assertEquals(2, $messages[0]->sequence);
		$this->assertEquals($originalCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($originalCalendar->VEVENT->ATTENDEE[0]->getValue(), $messages[0]->recipient);
	}

	/**
	 * Tests SCHEDULE-FORCE-SEND parameter handling
	 */
	public function testParseEventForOrganizerScheduleForceSend(): void {
		// construct calendar with event
		$originalCalendar = clone $this->vCalendar1a;
		$originalEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$originalCalendar]);
		// add SCHEDULE-FORCE-SEND parameter to ATTENDEE
		$mutatedCalendar = clone $this->vCalendar1a;
		$mutatedCalendar->VEVENT->{'LAST-MODIFIED'}->setValue('20240701T020000Z');
		$mutatedCalendar->VEVENT->SEQUENCE->setValue(2);
		$mutatedCalendar->VEVENT->ATTENDEE->add('SCHEDULE-FORCE-SEND', 'REQUEST');
		$mutatedEventInfo = $this->invokePrivate($this->broker, 'parseEventInfo', [$mutatedCalendar]);
		// test iTip generation
		$messages = $this->invokePrivate($this->broker, 'parseEventForOrganizer', [$mutatedCalendar, $mutatedEventInfo, $originalEventInfo]);
		$this->assertCount(1, $messages);
		$this->assertEquals('REQUEST', $messages[0]->method);
		$this->assertEquals(2, $messages[0]->sequence);
		$this->assertEquals($mutatedCalendar->VEVENT->ORGANIZER->getValue(), $messages[0]->sender);
		$this->assertEquals($mutatedCalendar->VEVENT->ATTENDEE->getValue(), $messages[0]->recipient);
		// verify SCHEDULE-FORCE-SEND is removed from the message (sanitized)
		$this->assertFalse(isset($messages[0]->message->VEVENT->ATTENDEE['SCHEDULE-FORCE-SEND']));
	}

	public function testProcessMessageReplyDisallowsInvitationForwarding(): void {
		$existingCalendar = clone $this->vCalendar1a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'FALSE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(1, $result->VEVENT->ATTENDEE);
		$this->assertEquals('mailto:attendee1@example.org', $result->VEVENT->ATTENDEE[0]->getValue());
	}

	public function testProcessMessageReplyUpdatesExistingAttendeeWhenInvitationForwardingDisabled(): void {
		$existingCalendar = clone $this->vCalendar1a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'FALSE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee1@example.org';
		$reply->senderName = 'Attendee One';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);
		$replyEvent->add('REQUEST-STATUS', '2.0;Success');

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(1, $result->VEVENT->ATTENDEE);
		$this->assertEquals('mailto:attendee1@example.org', $result->VEVENT->ATTENDEE[0]->getValue());
		$this->assertEquals('ACCEPTED', $result->VEVENT->ATTENDEE[0]['PARTSTAT']->getValue());
		$this->assertEquals('2.0', $result->VEVENT->ATTENDEE[0]['SCHEDULE-STATUS']->getValue());
		$this->assertFalse(isset($result->VEVENT->ATTENDEE[0]['RSVP']));
	}

	public function testProcessMessageReplyAllowsInvitationForwarding(): void {
		$existingCalendar = clone $this->vCalendar1a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'TRUE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT->ATTENDEE);
		$this->assertEquals('mailto:attendee2@example.org', $result->VEVENT->ATTENDEE[1]->getValue());
		$this->assertEquals('ACCEPTED', $result->VEVENT->ATTENDEE[1]['PARTSTAT']->getValue());
		$this->assertEquals('Attendee Two', $result->VEVENT->ATTENDEE[1]['CN']->getValue());
	}

	public function testProcessMessageReplyAllowsInvitationForwardingByDefault(): void {
		$existingCalendar = clone $this->vCalendar1a;
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT->ATTENDEE);
		$this->assertEquals('mailto:attendee2@example.org', $result->VEVENT->ATTENDEE[1]->getValue());
		$this->assertEquals('ACCEPTED', $result->VEVENT->ATTENDEE[1]['PARTSTAT']->getValue());
		$this->assertEquals('Attendee Two', $result->VEVENT->ATTENDEE[1]['CN']->getValue());
	}

	public function testProcessMessageReplyIgnoresReplyWithoutAttendee(): void {
		$existingCalendar = clone $this->vCalendar1a;
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee1@example.org';
		$reply->senderName = 'Attendee One';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(1, $result->VEVENT->ATTENDEE);
		$this->assertEquals('NEEDS-ACTION', $result->VEVENT->ATTENDEE[0]['PARTSTAT']->getValue());
		$this->assertTrue(isset($result->VEVENT->ATTENDEE[0]['RSVP']));
	}

	public function testProcessMessageReplyIgnoresReplyWithoutPartstat(): void {
		$existingCalendar = clone $this->vCalendar1a;
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee1@example.org';
		$reply->senderName = 'Attendee One';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('ATTENDEE', $reply->sender, []);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(1, $result->VEVENT->ATTENDEE);
		$this->assertEquals('NEEDS-ACTION', $result->VEVENT->ATTENDEE[0]['PARTSTAT']->getValue());
		$this->assertTrue(isset($result->VEVENT->ATTENDEE[0]['RSVP']));
	}

	public function testProcessMessageReplyDisallowsInvitationForwardingForGeneratedRecurringInstance(): void {
		$existingCalendar = clone $this->vCalendar2a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'FALSE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(1, $result->VEVENT);
		$this->assertCount(1, $result->VEVENT->ATTENDEE);
		$this->assertEquals('mailto:attendee1@example.org', $result->VEVENT->ATTENDEE[0]->getValue());
	}

	public function testProcessMessageReplyUpdatesExistingAttendeeForGeneratedRecurringInstanceWhenInvitationForwardingDisabled(): void {
		$existingCalendar = clone $this->vCalendar2a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'FALSE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee1@example.org';
		$reply->senderName = 'Attendee One';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);
		$replyEvent->add('REQUEST-STATUS', '2.0;Success');

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT);
		$this->assertEquals('20240715T080000', $result->VEVENT[1]->{'RECURRENCE-ID'}->getValue());
		$this->assertCount(1, $result->VEVENT[1]->ATTENDEE);
		$this->assertEquals('mailto:attendee1@example.org', $result->VEVENT[1]->ATTENDEE[0]->getValue());
		$this->assertEquals('ACCEPTED', $result->VEVENT[1]->ATTENDEE[0]['PARTSTAT']->getValue());
		$this->assertEquals('2.0', $result->VEVENT[1]->ATTENDEE[0]['SCHEDULE-STATUS']->getValue());
		$this->assertFalse(isset($result->VEVENT[1]->ATTENDEE[0]['RSVP']));
	}

	public function testProcessMessageReplyAllowsInvitationForwardingForDetachedRecurringExceptionWhenMasterDisallows(): void {
		$existingCalendar = clone $this->vCalendar2a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'FALSE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		/** @var \Sabre\VObject\Component\VEvent $detachedInstance */
		$detachedInstance = $existingCalendar->add('VEVENT', []);
		$detachedInstance->add('UID', $existingCalendar->VEVENT->UID->getValue());
		$detachedInstance->add('DTSTAMP', '20240701T000000Z');
		$detachedInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$detachedInstance->add('DTSTART', '20240715T080000', ['TZID' => 'America/Toronto']);
		$detachedInstance->add('DTEND', '20240715T090000', ['TZID' => 'America/Toronto']);
		$detachedInstance->add('SUMMARY', 'Detached Test Event');
		$detachedInstance->add('ORGANIZER', 'mailto:organizer@example.org', ['CN' => 'Organizer']);
		$detachedInstance->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'TRUE');
		$detachedInstance->add('ATTENDEE', 'mailto:attendee1@example.org', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE',
		]);

		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT);
		$this->assertCount(2, $result->VEVENT[1]->ATTENDEE);
		$this->assertEquals('mailto:attendee2@example.org', $result->VEVENT[1]->ATTENDEE[1]->getValue());
	}

	public function testProcessMessageReplyDisallowsInvitationForwardingForDetachedRecurringExceptionWhenMasterAllows(): void {
		$existingCalendar = clone $this->vCalendar2a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'TRUE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		/** @var \Sabre\VObject\Component\VEvent $detachedInstance */
		$detachedInstance = $existingCalendar->add('VEVENT', []);
		$detachedInstance->add('UID', $existingCalendar->VEVENT->UID->getValue());
		$detachedInstance->add('DTSTAMP', '20240701T000000Z');
		$detachedInstance->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$detachedInstance->add('DTSTART', '20240715T080000', ['TZID' => 'America/Toronto']);
		$detachedInstance->add('DTEND', '20240715T090000', ['TZID' => 'America/Toronto']);
		$detachedInstance->add('SUMMARY', 'Detached Test Event');
		$detachedInstance->add('ORGANIZER', 'mailto:organizer@example.org', ['CN' => 'Organizer']);
		$detachedInstance->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'FALSE');
		$detachedInstance->add('ATTENDEE', 'mailto:attendee1@example.org', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE',
		]);

		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT);
		$this->assertCount(1, $result->VEVENT[1]->ATTENDEE);
		$this->assertEquals('mailto:attendee1@example.org', $result->VEVENT[1]->ATTENDEE[0]->getValue());
	}

	public function testProcessMessageReplyAllowsInvitationForwardingForGeneratedRecurringInstance(): void {
		$existingCalendar = clone $this->vCalendar2a;
		$existingCalendar->VEVENT->add(TipBroker::INVITATION_FORWARDING_PROPERTY, 'TRUE');
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT);
		$this->assertEquals('20240715T080000', $result->VEVENT[1]->{'RECURRENCE-ID'}->getValue());
		$this->assertFalse(isset($result->VEVENT[1]->RRULE));
		$this->assertCount(2, $result->VEVENT[1]->ATTENDEE);
		$this->assertEquals('mailto:attendee2@example.org', $result->VEVENT[1]->ATTENDEE[1]->getValue());
		$this->assertEquals('ACCEPTED', $result->VEVENT[1]->ATTENDEE[1]['PARTSTAT']->getValue());
		$this->assertEquals('Attendee Two', $result->VEVENT[1]->ATTENDEE[1]['CN']->getValue());
	}

	public function testProcessMessageReplyAllowsInvitationForwardingByDefaultForGeneratedRecurringInstance(): void {
		$existingCalendar = clone $this->vCalendar2a;
		$existingCalendar->VEVENT->ATTENDEE[0]->setValue('mailto:attendee1@example.org');
		$reply = new Message();
		$reply->uid = $existingCalendar->VEVENT->UID->getValue();
		$reply->component = 'VEVENT';
		$reply->sender = 'mailto:attendee2@example.org';
		$reply->senderName = 'Attendee Two';
		$reply->sequence = 1;
		$reply->message = new VCalendar();
		/** @var \Sabre\VObject\Component\VEvent $replyEvent */
		$replyEvent = $reply->message->add('VEVENT', []);
		$replyEvent->add('UID', $reply->uid);
		$replyEvent->add('RECURRENCE-ID', '20240715T080000', ['TZID' => 'America/Toronto']);
		$replyEvent->add('ATTENDEE', $reply->sender, [
			'PARTSTAT' => 'ACCEPTED',
		]);

		$result = $this->invokePrivate($this->broker, 'processMessageReply', [$reply, $existingCalendar]);

		$this->assertSame($existingCalendar, $result);
		$this->assertCount(2, $result->VEVENT);
		$this->assertEquals('20240715T080000', $result->VEVENT[1]->{'RECURRENCE-ID'}->getValue());
		$this->assertFalse(isset($result->VEVENT[1]->RRULE));
		$this->assertCount(2, $result->VEVENT[1]->ATTENDEE);
		$this->assertEquals('mailto:attendee2@example.org', $result->VEVENT[1]->ATTENDEE[1]->getValue());
		$this->assertEquals('ACCEPTED', $result->VEVENT[1]->ATTENDEE[1]['PARTSTAT']->getValue());
		$this->assertEquals('Attendee Two', $result->VEVENT[1]->ATTENDEE[1]['CN']->getValue());
	}

}
