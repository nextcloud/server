<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OC\URLGenerator;
use OCA\DAV\CalDAV\EventReader;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property\ICalendar\DateTime;
use Test\TestCase;

class IMipServiceTest extends TestCase {
	private URLGenerator&MockObject $urlGenerator;
	private IUserConfig&MockObject $userConfig;
	private IAppConfig&MockObject $appConfig;
	private IDBConnection&MockObject $db;
	private ISecureRandom&MockObject $random;
	private IFactory&MockObject $l10nFactory;
	private IL10N&MockObject $l10n;
	private ITimeFactory&MockObject $timeFactory;
	private IMipService $service;
	private IUserManager&MockObject $userManager;


	private VCalendar $vCalendar1a;
	private VCalendar $vCalendar1b;
	private VCalendar $vCalendar2;
	private VCalendar $vCalendar3;
	/** @var DateTime DateTime object that will be returned by DateTime() or DateTime('now') */
	public static $datetimeNow;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(URLGenerator::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->l10nFactory->expects(self::once())
			->method('findGenericLanguage')
			->willReturn('en');
		$this->l10nFactory->expects(self::once())
			->method('get')
			->with('dav', 'en')
			->willReturn($this->l10n);
		$this->service = new IMipService(
			$this->urlGenerator,
			$this->db,
			$this->random,
			$this->l10nFactory,
			$this->timeFactory,
			$this->userManager,
			$this->userConfig,
			$this->appConfig,
		);

		// construct calendar with a 1 hour event and same start/end time zones
		$this->vCalendar1a = new VCalendar();
		$vEvent = $this->vCalendar1a->add('VEVENT', []);
		$vEvent->UID->setValue('96a0e6b1-d886-4a55-a60d-152b31401dcc');
		$vEvent->add('DTSTART', '20240701T080000', ['TZID' => 'America/Toronto']);
		$vEvent->add('DTEND', '20240701T090000', ['TZID' => 'America/Toronto']);
		$vEvent->add('SUMMARY', 'Testing Event');
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
		$vEvent->add('SUMMARY', 'Testing Event');
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
		$vEvent->add('SUMMARY', 'Testing Event');
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
		$vEvent->add('SUMMARY', 'Testing Event');
		$vEvent->add('ORGANIZER', 'mailto:organizer@testing.com', ['CN' => 'Organizer']);
		$vEvent->add('ATTENDEE', 'mailto:attendee1@testing.com', [
			'CN' => 'Attendee One',
			'CUTYPE' => 'INDIVIDUAL',
			'PARTSTAT' => 'NEEDS-ACTION',
			'ROLE' => 'REQ-PARTICIPANT',
			'RSVP' => 'TRUE'
		]);
	}

	public function testGetFrom(): void {
		$senderName = 'Detective McQueen';
		$default = 'Twin Lakes Police Department - Darkside Division';
		$expected = 'Detective McQueen via Twin Lakes Police Department - Darkside Division';

		$this->l10n->expects(self::once())
			->method('t')
			->willReturn($expected);

		$actual = $this->service->getFrom($senderName, $default);
		$this->assertEquals($expected, $actual);
	}

	public function testIsSystemUserWhenUserExists(): void {
		$email = 'user@example.com';
		$user = $this->createMock(\OCP\IUser::class);

		$this->userManager->expects(self::once())
			->method('getByEmail')
			->with($email)
			->willReturn([$user]);

		$result = $this->service->isSystemUser($email);
		$this->assertTrue($result);
	}

	public function testIsSystemUserWhenUserDoesNotExist(): void {
		$email = 'external@example.com';

		$this->userManager->expects(self::once())
			->method('getByEmail')
			->with($email)
			->willReturn([]);

		$result = $this->service->isSystemUser($email);
		$this->assertFalse($result);
	}

	public function testBuildBodyDataCreated(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'full'] => 'July 1, 2024'
				};
			}
		);
		$this->l10n->method('n')->willReturnMap([
			[
				'In %n day on %1$s between %2$s - %3$s',
				'In %n days on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 day on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			]
		]);
		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnCallback(
			function ($v1, $v2) {
				return match (true) {
					$v1 == 'now' && $v2 == null => (new \DateTime('20240630T000000'))
				};
			}
		);
		/** test singleton partial day event*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// define expected output
		$expected = [
			'meeting_when' => $this->service->generateWhenString($eventReader),
			'meeting_description' => '',
			'meeting_title' => 'Testing Event',
			'meeting_location' => '',
			'meeting_url' => '',
			'meeting_url_html' => '',
		];
		// generate actual output
		$actual = $this->service->buildBodyData($vCalendar->VEVENT[0], null);
		// test output
		$this->assertEquals($expected, $actual);
	}

	public function testBuildBodyDataUpdate(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'full'] => 'July 1, 2024'
				};
			}
		);
		$this->l10n->method('n')->willReturnMap([
			[
				'In %n day on %1$s between %2$s - %3$s',
				'In %n days on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 day on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			]
		]);
		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnCallback(
			function ($v1, $v2) {
				return match (true) {
					$v1 == 'now' && $v2 == null => (new \DateTime('20240630T000000'))
				};
			}
		);
		/** test singleton partial day event*/
		$vCalendarNew = clone $this->vCalendar1a;
		$vCalendarOld = clone $this->vCalendar1a;
		// construct event reader
		$eventReaderNew = new EventReader($vCalendarNew, $vCalendarNew->VEVENT[0]->UID->getValue());
		// alter old event label/title
		$vCalendarOld->VEVENT[0]->SUMMARY->setValue('Testing Singleton Event');
		// define expected output
		$expected = [
			'meeting_when' => $this->service->generateWhenString($eventReaderNew),
			'meeting_description' => '',
			'meeting_title' => 'Testing Event',
			'meeting_location' => '',
			'meeting_url' => '',
			'meeting_url_html' => '',
			'meeting_when_html' => $this->service->generateWhenString($eventReaderNew),
			'meeting_title_html' => sprintf("<span style='text-decoration: line-through'>%s</span><br />%s", 'Testing Singleton Event', 'Testing Event'),
			'meeting_description_html' => '',
			'meeting_location_html' => ''
		];
		// generate actual output
		$actual = $this->service->buildBodyData($vCalendarNew->VEVENT[0], $vCalendarOld->VEVENT[0]);
		// test output
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

	public function testGenerateWhenStringSingular(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'full'] => 'July 1, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240701T000000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'full'] => 'July 1, 2024'
				};
			}
		);
		$this->l10n->method('t')->willReturnMap([
			[
				'In the past on %1$s for the entire day',
				['July 1, 2024'],
				'In the past on July 1, 2024 for the entire day'
			],
			[
				'In the past on %1$s between %2$s - %3$s',
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In the past on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
		]);
		$this->l10n->method('n')->willReturnMap([
			// singular entire day
			[
				'In %n minute on %1$s for the entire day',
				'In %n minutes on %1$s for the entire day',
				1,
				['July 1, 2024'],
				'In 1 minute on July 1, 2024 for the entire day'
			],
			[
				'In %n hour on %1$s for the entire day',
				'In %n hours on %1$s for the entire day',
				1,
				['July 1, 2024'],
				'In 1 hour on July 1, 2024 for the entire day'
			],
			[
				'In %n day on %1$s for the entire day',
				'In %n days on %1$s for the entire day',
				1,
				['July 1, 2024'],
				'In 1 day on July 1, 2024 for the entire day'
			],
			[
				'In %n week on %1$s for the entire day',
				'In %n weeks on %1$s for the entire day',
				1,
				['July 1, 2024'],
				'In 1 week on July 1, 2024 for the entire day'
			],
			[
				'In %n month on %1$s for the entire day',
				'In %n months on %1$s for the entire day',
				1,
				['July 1, 2024'],
				'In 1 month on July 1, 2024 for the entire day'
			],
			[
				'In %n year on %1$s for the entire day',
				'In %n years on %1$s for the entire day',
				1,
				['July 1, 2024'],
				'In 1 year on July 1, 2024 for the entire day'
			],
			// plural entire day
			[
				'In %n minute on %1$s for the entire day',
				'In %n minutes on %1$s for the entire day',
				2,
				['July 1, 2024'],
				'In 2 minutes on July 1, 2024 for the entire day'
			],
			[
				'In %n hour on %1$s for the entire day',
				'In %n hours on %1$s for the entire day',
				2,
				['July 1, 2024'],
				'In 2 hours on July 1, 2024 for the entire day'
			],
			[
				'In %n day on %1$s for the entire day',
				'In %n days on %1$s for the entire day',
				2,
				['July 1, 2024'],
				'In 2 days on July 1, 2024 for the entire day'
			],
			[
				'In %n week on %1$s for the entire day',
				'In %n weeks on %1$s for the entire day',
				2,
				['July 1, 2024'],
				'In 2 weeks on July 1, 2024 for the entire day'
			],
			[
				'In %n month on %1$s for the entire day',
				'In %n months on %1$s for the entire day',
				2,
				['July 1, 2024'],
				'In 2 months on July 1, 2024 for the entire day'
			],
			[
				'In %n year on %1$s for the entire day',
				'In %n years on %1$s for the entire day',
				2,
				['July 1, 2024'],
				'In 2 years on July 1, 2024 for the entire day'
			],
			// singular partial day
			[
				'In %n minute on %1$s between %2$s - %3$s',
				'In %n minutes on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 minute on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n hour on %1$s between %2$s - %3$s',
				'In %n hours on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 hour on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n day on %1$s between %2$s - %3$s',
				'In %n days on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 day on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n week on %1$s between %2$s - %3$s',
				'In %n weeks on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 week on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n month on %1$s between %2$s - %3$s',
				'In %n months on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 month on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n year on %1$s between %2$s - %3$s',
				'In %n years on %1$s between %2$s - %3$s',
				1,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 1 year on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			// plural partial day
			[
				'In %n minute on %1$s between %2$s - %3$s',
				'In %n minutes on %1$s between %2$s - %3$s',
				2,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 2 minutes on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n hour on %1$s between %2$s - %3$s',
				'In %n hours on %1$s between %2$s - %3$s',
				2,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 2 hours on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n day on %1$s between %2$s - %3$s',
				'In %n days on %1$s between %2$s - %3$s',
				2,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 2 days on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n week on %1$s between %2$s - %3$s',
				'In %n weeks on %1$s between %2$s - %3$s',
				2,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 2 weeks on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n month on %1$s between %2$s - %3$s',
				'In %n months on %1$s between %2$s - %3$s',
				2,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 2 months on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
			[
				'In %n year on %1$s between %2$s - %3$s',
				'In %n years on %1$s between %2$s - %3$s',
				2,
				['July 1, 2024', '8:00 AM', '9:00 AM (America/Toronto)'],
				'In 2 years on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)'
			],
		]);

		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnOnConsecutiveCalls(
			// past interval test dates
			(new \DateTime('20240702T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240703T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240702T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240703T170000', (new \DateTimeZone('America/Toronto')))),
			// minute interval test dates
			(new \DateTime('20240701T075900', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240630T235900', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240701T075800', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240630T235800', (new \DateTimeZone('America/Toronto')))),
			// hour interval test dates
			(new \DateTime('20240701T070000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240630T230000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240701T060000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240630T220000', (new \DateTimeZone('America/Toronto')))),
			// day interval test dates
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			// week interval test dates
			(new \DateTime('20240621T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240621T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240614T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240614T170000', (new \DateTimeZone('America/Toronto')))),
			// month interval test dates
			(new \DateTime('20240530T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240530T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240430T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240430T170000', (new \DateTimeZone('America/Toronto')))),
			// year interval test dates
			(new \DateTime('20230630T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20230630T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20220630T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20220630T170000', (new \DateTimeZone('America/Toronto'))))
		);

		/** test partial day event in 1 day in the past*/
		$vCalendar = clone $this->vCalendar1a;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In the past on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 day in the past*/
		$vCalendar = clone $this->vCalendar2;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In the past on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event in 2 days in the past*/
		$vCalendar = clone $this->vCalendar1a;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In the past on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 days in the past*/
		$vCalendar = clone $this->vCalendar2;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In the past on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event in 1 minute*/
		$vCalendar = clone $this->vCalendar1a;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 1 minute on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 minute*/
		$vCalendar = clone $this->vCalendar2;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 1 minute on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event in 2 minutes*/
		$vCalendar = clone $this->vCalendar1a;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 2 minutes on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 minutes*/
		$vCalendar = clone $this->vCalendar2;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 2 minutes on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event in 1 hour*/
		$vCalendar = clone $this->vCalendar1a;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 1 hour on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 hour*/
		$vCalendar = clone $this->vCalendar2;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 1 hour on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event in 2 hours*/
		$vCalendar = clone $this->vCalendar1a;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 2 hours on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 hours*/
		$vCalendar = clone $this->vCalendar2;
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		$this->assertEquals(
			'In 2 hours on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 1 day*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 day*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 2 days*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 days*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 1 week*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 week on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 week*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 week on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 2 weeks*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 weeks on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 weeks*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 weeks on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 1 month*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 month on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 month*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 month on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 2 months*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 months on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 months*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 months on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 1 year*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 year on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 1 year*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 year on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test patrial day event in 2 years*/
		$vCalendar = clone $this->vCalendar1a;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 years on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event in 2 years*/
		$vCalendar = clone $this->vCalendar2;
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 years on July 1, 2024 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

	}

	public function testGenerateWhenStringRecurringDaily(): void {

		// construct l10n return maps
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20240713T080000', (new \DateTimeZone('UTC')))) && $v3 == ['width' => 'long'] => 'July 13, 2024'
				};
			}
		);
		$this->l10n->method('t')->willReturnMap([
			['Every Day for the entire day', [], 'Every Day for the entire day'],
			['Every Day for the entire day until %1$s', ['July 13, 2024'], 'Every Day for the entire day until July 13, 2024'],
			['Every Day between %1$s - %2$s', ['8:00 AM', '9:00 AM (America/Toronto)'], 'Every Day between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every Day between %1$s - %2$s until %3$s', ['8:00 AM', '9:00 AM (America/Toronto)', 'July 13, 2024'], 'Every Day between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024'],
			['Every %1$d Days for the entire day', [3], 'Every 3 Days for the entire day'],
			['Every %1$d Days for the entire day until %2$s', [3, 'July 13, 2024'], 'Every 3 Days for the entire day until July 13, 2024'],
			['Every %1$d Days between %2$s - %3$s', [3, '8:00 AM', '9:00 AM (America/Toronto)'], 'Every 3 Days between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every %1$d Days between %2$s - %3$s until %4$s', [3, '8:00 AM', '9:00 AM (America/Toronto)', 'July 13, 2024'], 'Every 3 Days between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024'],
			['Could not generate event recurrence statement', [], 'Could not generate event recurrence statement'],
		]);

		/** test partial day event with every day interval and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=1;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Day between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event with every day interval and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=1;UNTIL=20240713T080000Z');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Day between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event every 3rd day interval and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=3;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 3 Days between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event with every 3rd day interval and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=3;UNTIL=20240713T080000Z');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 3 Days between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every day interval and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=1;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Day for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every day interval and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=1;UNTIL=20240713T080000Z');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Day for the entire day until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every 3rd day interval and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=3;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 3 Days for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every 3rd day interval and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=3;UNTIL=20240713T080000Z');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 3 Days for the entire day until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

	}

	public function testGenerateWhenStringRecurringWeekly(): void {

		// construct l10n return maps
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20240722T080000', (new \DateTimeZone('UTC')))) && $v3 == ['width' => 'long'] => 'July 13, 2024'
				};
			}
		);
		$this->l10n->method('t')->willReturnMap([
			['Every Week on %1$s for the entire day', ['Monday, Wednesday, Friday'], 'Every Week on Monday, Wednesday, Friday for the entire day'],
			['Every Week on %1$s for the entire day until %2$s', ['Monday, Wednesday, Friday', 'July 13, 2024'], 'Every Week on Monday, Wednesday, Friday for the entire day until July 13, 2024'],
			['Every Week on %1$s between %2$s - %3$s', ['Monday, Wednesday, Friday', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every Week on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every Week on %1$s between %2$s - %3$s until %4$s', ['Monday, Wednesday, Friday', '8:00 AM', '9:00 AM (America/Toronto)', 'July 13, 2024'], 'Every Week on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024'],
			['Every %1$d Weeks on %2$s for the entire day', [2, 'Monday, Wednesday, Friday'], 'Every 2 Weeks on Monday, Wednesday, Friday for the entire day'],
			['Every %1$d Weeks on %2$s for the entire day until %3$s', [2, 'Monday, Wednesday, Friday', 'July 13, 2024'], 'Every 2 Weeks on Monday, Wednesday, Friday for the entire day until July 13, 2024'],
			['Every %1$d Weeks on %2$s between %3$s - %4$s', [2, 'Monday, Wednesday, Friday', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every 2 Weeks on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every %1$d Weeks on %2$s between %3$s - %4$s until %5$s', [2, 'Monday, Wednesday, Friday', '8:00 AM', '9:00 AM (America/Toronto)', 'July 13, 2024'], 'Every 2 Weeks on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024'],
			['Could not generate event recurrence statement', [], 'Could not generate event recurrence statement'],
			['Monday', [], 'Monday'],
			['Wednesday', [], 'Wednesday'],
			['Friday', [], 'Friday'],
		]);

		/** test partial day event with every week interval on Mon, Wed, Fri and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Week on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event with every week interval on Mon, Wed, Fri and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20240722T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Week on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event with every 2nd week interval on Mon, Wed, Fri and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Weeks on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test partial day event with every 2nd week interval on Mon, Wed, Fri and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;INTERVAL=2;UNTIL=20240722T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Weeks on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every week interval on Mon, Wed, Fri and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Week on Monday, Wednesday, Friday for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every week interval on Mon, Wed, Fri and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20240722T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Week on Monday, Wednesday, Friday for the entire day until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every 2nd week interval on Mon, Wed, Fri and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Weeks on Monday, Wednesday, Friday for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every 2nd week interval on Mon, Wed, Fri and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=WEEKLY;BYDAY=MO,WE,FR;INTERVAL=2;UNTIL=20240722T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Weeks on Monday, Wednesday, Friday for the entire day until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

	}

	public function testGenerateWhenStringRecurringMonthly(): void {

		// construct l10n return maps
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20241231T080000', (new \DateTimeZone('UTC')))) && $v3 == ['width' => 'long'] => 'December 31, 2024'
				};
			}
		);
		$this->l10n->method('t')->willReturnMap([
			['Every Month on the %1$s for the entire day', ['1, 8'], 'Every Month on the 1, 8 for the entire day'],
			['Every Month on the %1$s for the entire day until %2$s', ['1, 8', 'December 31, 2024'], 'Every Month on the 1, 8 for the entire day until December 31, 2024'],
			['Every Month on the %1$s between %2$s - %3$s', ['1, 8', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every Month on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every Month on the %1$s between %2$s - %3$s until %4$s', ['1, 8', '8:00 AM', '9:00 AM (America/Toronto)', 'December 31, 2024'], 'Every Month on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024'],
			['Every %1$d Months on the %2$s for the entire day', [2, '1, 8'], 'Every 2 Months on the 1, 8 for the entire day'],
			['Every %1$d Months on the %2$s for the entire day until %3$s', [2, '1, 8', 'December 31, 2024'], 'Every 2 Months on the 1, 8 for the entire day until December 31, 2024'],
			['Every %1$d Months on the %2$s between %3$s - %4$s', [2, '1, 8', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every 2 Months on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every %1$d Months on the %2$s between %3$s - %4$s until %5$s', [2, '1, 8', '8:00 AM', '9:00 AM (America/Toronto)', 'December 31, 2024'], 'Every 2 Months on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024'],
			['Every Month on the %1$s for the entire day', ['First Sunday, Saturday'], 'Every Month on the First Sunday, Saturday for the entire day'],
			['Every Month on the %1$s for the entire day until %2$s', ['First Sunday, Saturday', 'December 31, 2024'], 'Every Month on the First Sunday, Saturday for the entire day until December 31, 2024'],
			['Every Month on the %1$s between %2$s - %3$s', ['First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every Month on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every Month on the %1$s between %2$s - %3$s until %4$s', ['First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)', 'December 31, 2024'], 'Every Month on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024'],
			['Every %1$d Months on the %2$s for the entire day', [2, 'First Sunday, Saturday'], 'Every 2 Months on the First Sunday, Saturday for the entire day'],
			['Every %1$d Months on the %2$s for the entire day until %3$s', [2, 'First Sunday, Saturday', 'December 31, 2024'], 'Every 2 Months on the First Sunday, Saturday for the entire day until December 31, 2024'],
			['Every %1$d Months on the %2$s between %3$s - %4$s', [2, 'First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every 2 Months on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every %1$d Months on the %2$s between %3$s - %4$s until %5$s', [2, 'First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)', 'December 31, 2024'], 'Every 2 Months on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024'],
			['Could not generate event recurrence statement', [], 'Could not generate event recurrence statement'],
			['Saturday', [], 'Saturday'],
			['Sunday', [], 'Sunday'],
			['First', [], 'First'],
		]);

		/** test absolute partial day event with every month interval on 1st, 8th and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute partial day event with every Month interval on 1st, 8th and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute partial day event with every 2nd Month interval on 1st, 8th and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute partial day event with every 2nd Month interval on 1st, 8th and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;INTERVAL=2;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every Month interval on 1st, 8th and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the 1, 8 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every Month interval on 1st, 8th and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the 1, 8 for the entire day until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every 2nd Month interval on 1st, 8th and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the 1, 8 for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every 2nd Month interval on 1st, 8th and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=1,8;INTERVAL=2;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the 1, 8 for the entire day until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every month interval on the 1st Saturday, Sunday and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every Month interval on the 1st Saturday, Sunday and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every 2nd Month interval on the 1st Saturday, Sunday and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every 2nd Month interval on the 1st Saturday, Sunday and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every Month interval on the 1st Saturday, Sunday and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the First Sunday, Saturday for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every Month interval on the 1st Saturday, Sunday and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Month on the First Sunday, Saturday for the entire day until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every 2nd Month interval on the 1st Saturday, Sunday and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the First Sunday, Saturday for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every 2nd Month interval on the 1st Saturday, Sunday and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=MONTHLY;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;UNTIL=20241231T080000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Months on the First Sunday, Saturday for the entire day until December 31, 2024',
			$this->service->generateWhenString($eventReader)
		);

	}

	public function testGenerateWhenStringRecurringYearly(): void {

		// construct l10n return maps
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20260731T040000', (new \DateTimeZone('UTC')))) && $v3 == ['width' => 'long'] => 'July 31, 2026'
				};
			}
		);
		$this->l10n->method('t')->willReturnMap([
			['Every Year in %1$s on the %2$s for the entire day', ['July', '1st'], 'Every Year in July on the 1st for the entire day'],
			['Every Year in %1$s on the %2$s for the entire day until %3$s', ['July', '1st', 'July 31, 2026'], 'Every Year in July on the 1st for the entire day until July 31, 2026'],
			['Every Year in %1$s on the %2$s between %3$s - %4$s', ['July', '1st', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every Year in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every Year in %1$s on the %2$s between %3$s - %4$s until %5$s', ['July', '1st', '8:00 AM', '9:00 AM (America/Toronto)', 'July 31, 2026'], 'Every Year in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026'],
			['Every %1$d Years in %2$s on the %3$s for the entire day', [2, 'July', '1st'], 'Every 2 Years in July on the 1st for the entire day'],
			['Every %1$d Years in %2$s on the %3$s for the entire day until %4$s', [2, 'July', '1st', 'July 31, 2026'], 'Every 2 Years in July on the 1st for the entire day until July 31, 2026'],
			['Every %1$d Years in %2$s on the %3$s between %4$s - %5$s', [2, 'July', '1st', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every 2 Years in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every %1$d Years in %2$s on the %3$s between %4$s - %5$s until %6$s', [2, 'July', '1st', '8:00 AM', '9:00 AM (America/Toronto)', 'July 31, 2026'], 'Every 2 Years in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026'],
			['Every Year in %1$s on the %2$s for the entire day', ['July', 'First Sunday, Saturday'], 'Every Year in July on the First Sunday, Saturday for the entire day'],
			['Every Year in %1$s on the %2$s for the entire day until %3$s', ['July', 'First Sunday, Saturday', 'July 31, 2026'], 'Every Year in July on the First Sunday, Saturday for the entire day until July 31, 2026'],
			['Every Year in %1$s on the %2$s between %3$s - %4$s', ['July', 'First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every Year in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every Year in %1$s on the %2$s between %3$s - %4$s until %5$s', ['July', 'First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)', 'July 31, 2026'], 'Every Year in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026'],
			['Every %1$d Years in %2$s on the %3$s for the entire day', [2, 'July', 'First Sunday, Saturday'], 'Every 2 Years in July on the First Sunday, Saturday for the entire day'],
			['Every %1$d Years in %2$s on the %3$s for the entire day until %4$s', [2, 'July', 'First Sunday, Saturday', 'July 31, 2026'], 'Every 2 Years in July on the First Sunday, Saturday for the entire day until July 31, 2026'],
			['Every %1$d Years in %2$s on the %3$s between %4$s - %5$s', [2, 'July', 'First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)'], 'Every 2 Years in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)'],
			['Every %1$d Years in %2$s on the %3$s between %4$s - %5$s until %6$s', [2, 'July', 'First Sunday, Saturday', '8:00 AM', '9:00 AM (America/Toronto)', 'July 31, 2026'], 'Every 2 Years in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026'],
			['Could not generate event recurrence statement', [], 'Could not generate event recurrence statement'],
			['July', [], 'July'],
			['Saturday', [], 'Saturday'],
			['Sunday', [], 'Sunday'],
			['First', [], 'First'],
		]);

		/** test absolute partial day event with every year interval on July 1 and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute partial day event with every year interval on July 1 and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;UNTIL=20260731T040000Z');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute partial day event with every 2nd year interval on July 1 and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute partial day event with every 2nd year interval on July 1 and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;INTERVAL=2;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every year interval on July 1 and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the 1st for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every year interval on July 1 and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the 1st for the entire day until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every 2nd year interval on July 1 and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the 1st for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test absolute entire day event with every 2nd year interval on July 1 and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;INTERVAL=2;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the 1st for the entire day until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every year interval on the 1st Saturday, Sunday in July and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every year interval on the 1st Saturday, Sunday in July and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every 2nd year interval on the 1st Saturday, Sunday in July and no conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative partial day event with every 2nd year interval on the 1st Saturday, Sunday in July and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every year interval on the 1st Saturday, Sunday in July and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the First Sunday, Saturday for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every year interval on the 1st Saturday, Sunday in July and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every Year in July on the First Sunday, Saturday for the entire day until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every 2nd year interval on the 1st Saturday, Sunday in July and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the First Sunday, Saturday for the entire day',
			$this->service->generateWhenString($eventReader)
		);

		/** test relative entire day event with every 2nd year interval on the 1st Saturday, Sunday in July and conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=YEARLY;BYMONTH=7;BYDAY=SU,SA;BYSETPOS=1;INTERVAL=2;UNTIL=20260731T040000Z;');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'Every 2 Years in July on the First Sunday, Saturday for the entire day until July 31, 2026',
			$this->service->generateWhenString($eventReader)
		);

	}

	public function testGenerateWhenStringRecurringFixed(): void {

		// construct l10n return maps
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'time' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '8:00 AM',
					$v1 === 'time' && $v2 == (new \DateTime('20240701T090000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'short'] => '9:00 AM',
					$v1 === 'date' && $v2 == (new \DateTime('20240713T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 13, 2024'
				};
			}
		);
		$this->l10n->method('t')->willReturnMap([
			['On specific dates for the entire day until %1$s', ['July 13, 2024'], 'On specific dates for the entire day until July 13, 2024'],
			['On specific dates between %1$s - %2$s until %3$s', ['8:00 AM', '9:00 AM (America/Toronto)', 'July 13, 2024'], 'On specific dates between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024'],
		]);

		/** test partial day event with every day interval and conclusion*/
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703T080000,20240709T080000,20240713T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'On specific dates between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

		/** test entire day event with every day interval and no conclusion*/
		$vCalendar = clone $this->vCalendar2;
		$vCalendar->VEVENT[0]->add('RDATE', '20240703T080000,20240709T080000,20240713T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'On specific dates for the entire day until July 13, 2024',
			$this->service->generateWhenString($eventReader)
		);

	}

	public function testGenerateOccurringStringWithRrule(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 1, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240703T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 3, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240705T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 5, 2024'
				};
			}
		);
		$this->l10n->method('n')->willReturnMap([
			// singular
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				1,
				['July 1, 2024'],
				'In 1 day on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				1,
				['July 1, 2024', 'July 3, 2024'],
				'In 1 day on July 1, 2024 then on July 3, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				1,
				['July 1, 2024', 'July 3, 2024', 'July 5, 2024'],
				'In 1 day on July 1, 2024 then on July 3, 2024 and July 5, 2024'
			],
			// plural
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				2,
				['July 1, 2024'],
				'In 2 days on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				2,
				['July 1, 2024', 'July 3, 2024'],
				'In 2 days on July 1, 2024 then on July 3, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				2,
				['July 1, 2024', 'July 3, 2024', 'July 5, 2024'],
				'In 2 days on July 1, 2024 then on July 3, 2024 and July 5, 2024'
			],
		]);

		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnOnConsecutiveCalls(
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
		);

		/** test patrial day recurring event in 1 day with single occurrence remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=1');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024',
			$this->service->generateOccurringString($eventReader)
		);

		/** test patrial day recurring event in 1 day with two occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=2');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 3, 2024',
			$this->service->generateOccurringString($eventReader)
		);

		/** test patrial day recurring event in 1 day with three occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=3');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 3, 2024 and July 5, 2024',
			$this->service->generateOccurringString($eventReader)
		);

		/** test patrial day recurring event in 2 days with single occurrence remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=1');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024',
			$this->service->generateOccurringString($eventReader)
		);

		/** test patrial day recurring event in 2 days with two occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=2');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 3, 2024',
			$this->service->generateOccurringString($eventReader)
		);

		/** test patrial day recurring event in 2 days with three occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=3');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 3, 2024 and July 5, 2024',
			$this->service->generateOccurringString($eventReader)
		);
	}

	public function testGenerateOccurringStringWithRdate(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 1, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240703T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 3, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240705T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 5, 2024'
				};
			}
		);
		$this->l10n->method('n')->willReturnMap([
			// singular
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				1,
				['July 1, 2024'],
				'In 1 day on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				1,
				['July 1, 2024', 'July 3, 2024'],
				'In 1 day on July 1, 2024 then on July 3, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				1,
				['July 1, 2024', 'July 3, 2024', 'July 5, 2024'],
				'In 1 day on July 1, 2024 then on July 3, 2024 and July 5, 2024'
			],
			// plural
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				2,
				['July 1, 2024'],
				'In 2 days on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				2,
				['July 1, 2024', 'July 3, 2024'],
				'In 2 days on July 1, 2024 then on July 3, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				2,
				['July 1, 2024', 'July 3, 2024', 'July 5, 2024'],
				'In 2 days on July 1, 2024 then on July 3, 2024 and July 5, 2024'
			],
		]);

		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnOnConsecutiveCalls(
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
		);

		/** test patrial day recurring event in 1 day with single occurrence remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240701T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with single occurrence remaining'
		);

		/** test patrial day recurring event in 1 day with two occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240701T080000,20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 3, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with two occurrences remaining'
		);

		/** test patrial day recurring event in 1 day with three occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240701T080000,20240703T080000,20240705T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 3, 2024 and July 5, 2024',
			$this->service->generateOccurringString($eventReader),
			''
		);

		/** test patrial day recurring event in 2 days with single occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240701T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			''
		);

		/** test patrial day recurring event in 2 days with two occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240701T080000');
		$vCalendar->VEVENT[0]->add('RDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 3, 2024',
			$this->service->generateOccurringString($eventReader),
			''
		);

		/** test patrial day recurring event in 2 days with three occurrences remaining */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RDATE', '20240701T080000');
		$vCalendar->VEVENT[0]->add('RDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('RDATE', '20240705T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 3, 2024 and July 5, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with three occurrences remaining'
		);
	}

	public function testGenerateOccurringStringWithOneExdate(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 1, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240705T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 5, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240707T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 7, 2024'
				};
			}
		);
		$this->l10n->method('n')->willReturnMap([
			// singular
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				1,
				['July 1, 2024'],
				'In 1 day on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				1,
				['July 1, 2024', 'July 5, 2024'],
				'In 1 day on July 1, 2024 then on July 5, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				1,
				['July 1, 2024', 'July 5, 2024', 'July 7, 2024'],
				'In 1 day on July 1, 2024 then on July 5, 2024 and July 7, 2024'
			],
			// plural
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				2,
				['July 1, 2024'],
				'In 2 days on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				2,
				['July 1, 2024', 'July 5, 2024'],
				'In 2 days on July 1, 2024 then on July 5, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				2,
				['July 1, 2024', 'July 5, 2024', 'July 7, 2024'],
				'In 2 days on July 1, 2024 then on July 5, 2024 and July 7, 2024'
			],
		]);

		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnOnConsecutiveCalls(
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
		);

		/** test patrial day recurring event in 1 day with single occurrence remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=1');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with single occurrence remaining and one exception'
		);

		/** test patrial day recurring event in 1 day with two occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=2');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with two occurrences remaining and one exception'
		);

		/** test patrial day recurring event in 1 day with three occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=3');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 5, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with three occurrences remaining and one exception'
		);

		/** test patrial day recurring event in 1 day with four occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=4');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 5, 2024 and July 7, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with four occurrences remaining and one exception'
		);

		/** test patrial day recurring event in 2 days with single occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=1');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with single occurrences remaining and one exception'
		);

		/** test patrial day recurring event in 2 days with two occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=2');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with two occurrences remaining and one exception'
		);

		/** test patrial day recurring event in 2 days with three occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=3');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 5, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with three occurrences remaining and one exception'
		);

		/** test patrial day recurring event in 2 days with four occurrences remaining and one exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=4');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 5, 2024 and July 7, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with four occurrences remaining and one exception'
		);
	}

	public function testGenerateOccurringStringWithTwoExdate(): void {

		// construct l10n return(s)
		$this->l10n->method('l')->willReturnCallback(
			function ($v1, $v2, $v3) {
				return match (true) {
					$v1 === 'date' && $v2 == (new \DateTime('20240701T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 1, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240705T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 5, 2024',
					$v1 === 'date' && $v2 == (new \DateTime('20240709T080000', (new \DateTimeZone('America/Toronto')))) && $v3 == ['width' => 'long'] => 'July 9, 2024'
				};
			}
		);
		$this->l10n->method('n')->willReturnMap([
			// singular
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				1,
				['July 1, 2024'],
				'In 1 day on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				1,
				['July 1, 2024', 'July 5, 2024'],
				'In 1 day on July 1, 2024 then on July 5, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				1,
				['July 1, 2024', 'July 5, 2024', 'July 9, 2024'],
				'In 1 day on July 1, 2024 then on July 5, 2024 and July 9, 2024'
			],
			// plural
			[
				'In %n day on %1$s',
				'In %n days on %1$s',
				2,
				['July 1, 2024'],
				'In 2 days on July 1, 2024'
			],
			[
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				2,
				['July 1, 2024', 'July 5, 2024'],
				'In 2 days on July 1, 2024 then on July 5, 2024'
			],
			[
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				2,
				['July 1, 2024', 'July 5, 2024', 'July 9, 2024'],
				'In 2 days on July 1, 2024 then on July 5, 2024 and July 9, 2024'
			],
		]);

		// construct time factory return(s)
		$this->timeFactory->method('getDateTime')->willReturnOnConsecutiveCalls(
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240629T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
			(new \DateTime('20240628T170000', (new \DateTimeZone('America/Toronto')))),
		);

		/** test patrial day recurring event in 1 day with single occurrence remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=1');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with single occurrence remaining and two exception'
		);

		/** test patrial day recurring event in 1 day with two occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=2');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with two occurrences remaining and two exception'
		);

		/** test patrial day recurring event in 1 day with three occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=3');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 5, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with three occurrences remaining and two exception'
		);

		/** test patrial day recurring event in 1 day with four occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=5');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 1 day on July 1, 2024 then on July 5, 2024 and July 9, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 1 day with four occurrences remaining and two exception'
		);

		/** test patrial day recurring event in 2 days with single occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=1');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with single occurrences remaining and two exception'
		);

		/** test patrial day recurring event in 2 days with two occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=2');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with two occurrences remaining and two exception'
		);

		/** test patrial day recurring event in 2 days with three occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=3');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 5, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with three occurrences remaining and two exception'
		);

		/** test patrial day recurring event in 2 days with five occurrences remaining and two exception */
		$vCalendar = clone $this->vCalendar1a;
		$vCalendar->VEVENT[0]->add('RRULE', 'FREQ=DAILY;INTERVAL=2;COUNT=5');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240703T080000');
		$vCalendar->VEVENT[0]->add('EXDATE', '20240707T080000');
		// construct event reader
		$eventReader = new EventReader($vCalendar, $vCalendar->VEVENT[0]->UID->getValue());
		// test output
		$this->assertEquals(
			'In 2 days on July 1, 2024 then on July 5, 2024 and July 9, 2024',
			$this->service->generateOccurringString($eventReader),
			'test patrial day recurring event in 2 days with five occurrences remaining and two exception'
		);
	}

}
