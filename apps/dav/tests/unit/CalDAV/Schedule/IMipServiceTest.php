<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OC\L10N\L10N;
use OC\L10N\LazyL10N;
use OC\URLGenerator;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Property\ICalendar\DateTime;
use Test\TestCase;

class IMipServiceTest extends TestCase {
	/** @var URLGenerator|MockObject */
	private $urlGenerator;

	/** @var IConfig|MockObject */
	private $config;

	/** @var IDBConnection|MockObject */
	private $db;

	/** @var ISecureRandom|MockObject */
	private $random;

	/** @var L10NFactory|MockObject */
	private $l10nFactory;

	/** @var L10N|MockObject */
	private $l10n;

	/** @var IMipService */
	private $service;

	protected function setUp(): void {
		$this->urlGenerator = $this->createMock(URLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->l10nFactory = $this->createMock(L10NFactory::class);
		$this->l10n = $this->createMock(LazyL10N::class);
		$this->l10nFactory->expects(self::once())
			->method('findGenericLanguage')
			->willReturn('en');
		$this->l10nFactory->expects(self::once())
			->method('get')
			->with('dav', 'en')
			->willReturn($this->l10n);
		$this->service = new IMipService(
			$this->urlGenerator,
			$this->config,
			$this->db,
			$this->random,
			$this->l10nFactory
		);
	}

	public function testGetFrom(): void {
		$senderName = "Detective McQueen";
		$default = "Twin Lakes Police Department - Darkside Division";
		$expected = "Detective McQueen via Twin Lakes Police Department - Darkside Division";

		$this->l10n->expects(self::once())
			->method('t')
			->willReturn($expected);

		$actual = $this->service->getFrom($senderName, $default);
		$this->assertEquals($expected, $actual);
	}

	public function testBuildBodyDataCreated(): void {
		$vCalendar = new VCalendar();
		$oldVevent = null;
		$newVevent = new VEvent($vCalendar, 'two', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 3,
			'LAST-MODIFIED' => 789456,
			'SUMMARY' => 'Second Breakfast',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RECURRENCE-ID' => new \DateTime('2016-01-01 00:00:00')
		]);

		$expected = [
			'meeting_when' => $this->service->generateWhenString($newVevent),
			'meeting_description' => '',
			'meeting_title' => 'Second Breakfast',
			'meeting_location' => '',
			'meeting_url' => '',
			'meeting_url_html' => '',
		];

		$actual = $this->service->buildBodyData($newVevent, $oldVevent);

		$this->assertEquals($expected, $actual);
	}

	public function testBuildBodyDataUpdate(): void {
		$vCalendar = new VCalendar();
		$oldVevent = new VEvent($vCalendar, 'two', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'LAST-MODIFIED' => 456789,
			'SUMMARY' => 'Elevenses',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RECURRENCE-ID' => new \DateTime('2016-01-01 00:00:00')
		]);
		$oldVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$newVevent = new VEvent($vCalendar, 'two', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 3,
			'LAST-MODIFIED' => 789456,
			'SUMMARY' => 'Second Breakfast',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RECURRENCE-ID' => new \DateTime('2016-01-01 00:00:00')
		]);

		$expected = [
			'meeting_when' => $this->service->generateWhenString($newVevent),
			'meeting_description' => '',
			'meeting_title' => 'Second Breakfast',
			'meeting_location' => '',
			'meeting_url' => '',
			'meeting_url_html' => '',
			'meeting_when_html' => $this->service->generateWhenString($newVevent),
			'meeting_title_html' => sprintf("<span style='text-decoration: line-through'>%s</span><br />%s", 'Elevenses', 'Second Breakfast'),
			'meeting_description_html' => '',
			'meeting_location_html' => ''
		];

		$actual = $this->service->buildBodyData($newVevent, $oldVevent);

		$this->assertEquals($expected, $actual);
	}

	public function testGenerateWhenStringHourlyEvent(): void {
		$vCalendar = new VCalendar();
		$vevent = new VEvent($vCalendar, 'two', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'LAST-MODIFIED' => 456789,
			'SUMMARY' => 'Elevenses',
			'TZID' => 'Europe/Vienna',
			'DTSTART' => (new \DateTime('2016-01-01 08:00:00'))->setTimezone(new \DateTimeZone('Europe/Vienna')),
			'DTEND' => (new \DateTime('2016-01-01 09:00:00'))->setTimezone(new \DateTimeZone('Europe/Vienna')),
		]);

		$this->l10n->expects(self::exactly(3))
			->method('l')
			->withConsecutive(
				['weekdayName', (new \DateTime('2016-01-01 08:00:00'))->setTimezone(new \DateTimeZone('Europe/Vienna')), ['width' => 'abbreviated']],
				['datetime', (new \DateTime('2016-01-01 08:00:00'))->setTimezone(new \DateTimeZone('Europe/Vienna')), ['width' => 'medium|short']],
				['time', (new \DateTime('2016-01-01 09:00:00'))->setTimezone(new \DateTimeZone('Europe/Vienna')), ['width' => 'short']]
			)->willReturnOnConsecutiveCalls(
				'Fr.',
				'01.01. 08:00',
				'09:00'
			);

		$expected = 'Fr., 01.01. 08:00 - 09:00 (Europe/Vienna)';
		$actual = $this->service->generateWhenString($vevent);
		$this->assertEquals($expected, $actual);
	}

	public function testGetLastOccurrenceRRULE(): void {
		$vCalendar = new VCalendar();
		$vCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'RRULE' => 'FREQ=DAILY;INTERVAL=1;UNTIL=20160201T000000Z',
		]);

		$occurrence = $this->service->getLastOccurrence($vCalendar);
		$this->assertEquals(1454284800, $occurrence);
	}

	public function testGetLastOccurrenceEndDate(): void {
		$vCalendar = new VCalendar();
		$vCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'DTEND' => new \DateTime('2017-01-01 00:00:00'),
		]);

		$occurrence = $this->service->getLastOccurrence($vCalendar);
		$this->assertEquals(1483228800, $occurrence);
	}

	public function testGetLastOccurrenceDuration(): void {
		$vCalendar = new VCalendar();
		$vCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
			'DURATION' => 'P12W',
		]);

		$occurrence = $this->service->getLastOccurrence($vCalendar);
		$this->assertEquals(1458864000, $occurrence);
	}

	public function testGetLastOccurrenceAllDay(): void {
		$vCalendar = new VCalendar();
		$vEvent = $vCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);

		// rewrite from DateTime to Date
		$vEvent->DTSTART['VALUE'] = 'DATE';

		$occurrence = $this->service->getLastOccurrence($vCalendar);
		$this->assertEquals(1451692800, $occurrence);
	}

	public function testGetLastOccurrenceFallback(): void {
		$vCalendar = new VCalendar();
		$vCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);

		$occurrence = $this->service->getLastOccurrence($vCalendar);
		$this->assertEquals(1451606400, $occurrence);
	}

	public function testGetCurrentAttendeeRequest(): void {
		// Construct ITip Message
		$message = new Message();
		$message->method = 'REQUEST';
		$message->sequence = 1;
		$message->sender = 'mailto:organizer@example.com';
		$message->senderName = 'The Organizer';
		$message->recipient = 'mailto:attendee@example.com';
		$message->recipientName = 'The Attendee';
		$message->significantChange = true;
		$message->message = new VCalendar();
		$message->message->add('VEVENT', ['UID' => '82496785-1915-4604-a5ce-4e2091639c9a', 'SEQUENCE' => 1]);
		$message->message->VEVENT->add('SUMMARY', 'Fellowship meeting');
		$message->message->VEVENT->add('DTSTART', (new \DateTime('NOW'))->modify('+1 hour'));
		$message->message->VEVENT->add('DTEND', (new \DateTime('NOW'))->modify('+2 hour'));
		$message->message->VEVENT->add('ORGANIZER', 'mailto:organizer@example.com', ['CN' => 'The Organizer']);
		$message->message->VEVENT->add('ATTENDEE', 'mailto:attendee@example.com', ['CN' => 'The Attendee']);
		// Test getCurrentAttendee
		$result = $this->service->getCurrentAttendee($message);
		// Evaluate Result
		$this->assertEquals($message->message->VEVENT->ATTENDEE, $result);
	}

	public function testGetCurrentAttendeeReply(): void {
		// Construct ITip Message
		$message = new Message();
		$message->method = 'REPLY';
		$message->sequence = 2;
		$message->sender = 'mailto:attendee@example.com';
		$message->senderName = 'The Attendee';
		$message->recipient = 'mailto:organizer@example.com';
		$message->recipientName = 'The Organizer';
		$message->significantChange = true;
		$message->message = new VCalendar();
		$message->message->add('METHOD', 'REPLY');
		$message->message->add('VEVENT', ['UID' => '82496785-1915-4604-a5ce-4e2091639c9a', 'SEQUENCE' => 2]);
		$message->message->VEVENT->add('SUMMARY', 'Fellowship meeting');
		$message->message->VEVENT->add('DTSTART', (new \DateTime('NOW'))->modify('+1 hour'));
		$message->message->VEVENT->add('DTEND', (new \DateTime('NOW'))->modify('+2 hour'));
		$message->message->VEVENT->add('ORGANIZER', 'mailto:organizer@example.com', ['CN' => 'The Organizer']);
		$message->message->VEVENT->add('ATTENDEE', 'mailto:attendee@example.com', ['CN' => 'The Attendee']);
		// Test getCurrentAttendee
		$result = $this->service->getCurrentAttendee($message);
		// Evaluate Result
		$this->assertEquals($message->message->VEVENT->ATTENDEE, $result);
	}

	public function testGetCurrentAttendeeMismatch(): void {
		// Construct ITip Message
		$message = new Message();
		$message->method = 'REQUEST';
		$message->sequence = 1;
		$message->sender = 'mailto:organizer@example.com';
		$message->senderName = 'The Organizer';
		$message->recipient = 'mailto:mismatch@example.com';
		$message->recipientName = 'The Mismatch';
		$message->significantChange = true;
		$message->message = new VCalendar();
		$message->message->add('VEVENT', ['UID' => '82496785-1915-4604-a5ce-4e2091639c9a', 'SEQUENCE' => 1]);
		$message->message->VEVENT->add('SUMMARY', 'Fellowship meeting');
		$message->message->VEVENT->add('DTSTART', (new \DateTime('NOW'))->modify('+1 hour'));
		$message->message->VEVENT->add('DTEND', (new \DateTime('NOW'))->modify('+2 hour'));
		$message->message->VEVENT->add('ORGANIZER', 'mailto:organizer@example.com', ['CN' => 'The Organizer']);
		$message->message->VEVENT->add('ATTENDEE', 'mailto:attendee@example.com', ['CN' => 'The Attendee']);
		// Test getCurrentAttendee
		$result = $this->service->getCurrentAttendee($message);
		// Evaluate Result
		$this->assertEquals(null, $result);
	}
}
