<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\PublicCalendar;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;

class PublicCalendarTest extends CalendarTest {

	/**
	 * @dataProvider providesConfidentialClassificationData
	 * @param int $expectedChildren
	 * @param bool $isShared
	 */
	public function testPrivateClassification($expectedChildren, $isShared): void {
		$calObject0 = ['uri' => 'event-0', 'classification' => CalDavBackend::CLASSIFICATION_PUBLIC];
		$calObject1 = ['uri' => 'event-1', 'classification' => CalDavBackend::CLASSIFICATION_CONFIDENTIAL];
		$calObject2 = ['uri' => 'event-2', 'classification' => CalDavBackend::CLASSIFICATION_PRIVATE];

		/** @var MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder(CalDavBackend::class)->disableOriginalConstructor()->getMock();
		$backend->expects($this->any())->method('getCalendarObjects')->willReturn([
			$calObject0, $calObject1, $calObject2
		]);
		$backend->expects($this->any())->method('getMultipleCalendarObjects')
			->with(666, ['event-0', 'event-1', 'event-2'])
			->willReturn([
				$calObject0, $calObject1, $calObject2
			]);
		$backend->expects($this->any())->method('getCalendarObject')
			->willReturn($calObject2)->with(666, 'event-2');
		$backend->expects($this->any())->method('applyShareAcl')->willReturnArgument(1);

		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user2',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		/** @var MockObject | IConfig $config */
		$config = $this->createMock(IConfig::class);
		/** @var MockObject | LoggerInterface $logger */
		$logger = $this->createMock(LoggerInterface::class);
		$c = new PublicCalendar($backend, $calendarInfo, $this->l10n, $config, $logger);
		$children = $c->getChildren();
		$this->assertEquals(2, count($children));
		$children = $c->getMultipleChildren(['event-0', 'event-1', 'event-2']);
		$this->assertEquals(2, count($children));

		$this->assertFalse($c->childExists('event-2'));
	}

	/**
	 * @dataProvider providesConfidentialClassificationData
	 * @param int $expectedChildren
	 * @param bool $isShared
	 */
	public function testConfidentialClassification($expectedChildren, $isShared): void {
		$start = '20160609';
		$end = '20160610';

		$calData = <<<EOD
BEGIN:VCALENDAR
PRODID:-//ownCloud calendar v1.2.2
BEGIN:VEVENT
CREATED:20160602T133732
DTSTAMP:20160602T133732
LAST-MODIFIED:20160602T133732
UID:wej2z68l9h
SUMMARY:Test Event
LOCATION:Somewhere ...
ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;CUTYPE=INDIVIDUAL;CN=de
 epdiver:MAILTO:thomas.mueller@tmit.eu
ORGANIZER;CN=deepdiver:MAILTO:thomas.mueller@tmit.eu
DESCRIPTION:maybe ....
DTSTART;TZID=Europe/Berlin;VALUE=DATE:$start
DTEND;TZID=Europe/Berlin;VALUE=DATE:$end
RRULE:FREQ=DAILY
BEGIN:VALARM
ACTION:AUDIO
TRIGGER:-PT15M
END:VALARM
END:VEVENT
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
DTSTART:19810329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
TZNAME:MESZ
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
DTSTART:19961027T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
TZNAME:MEZ
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
END:VCALENDAR
EOD;

		$calObject0 = ['uri' => 'event-0', 'classification' => CalDavBackend::CLASSIFICATION_PUBLIC];
		$calObject1 = ['uri' => 'event-1', 'classification' => CalDavBackend::CLASSIFICATION_CONFIDENTIAL, 'calendardata' => $calData];
		$calObject2 = ['uri' => 'event-2', 'classification' => CalDavBackend::CLASSIFICATION_PRIVATE];

		/** @var MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder(CalDavBackend::class)->disableOriginalConstructor()->getMock();
		$backend->expects($this->any())->method('getCalendarObjects')->willReturn([
			$calObject0, $calObject1, $calObject2
		]);
		$backend->expects($this->any())->method('getMultipleCalendarObjects')
			->with(666, ['event-0', 'event-1', 'event-2'])
			->willReturn([
				$calObject0, $calObject1, $calObject2
			]);
		$backend->expects($this->any())->method('getCalendarObject')
			->willReturn($calObject1)->with(666, 'event-1');
		$backend->expects($this->any())->method('applyShareAcl')->willReturnArgument(1);

		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		/** @var MockObject | IConfig $config */
		$config = $this->createMock(IConfig::class);
		/** @var MockObject | LoggerInterface $logger */
		$logger = $this->createMock(LoggerInterface::class);
		$c = new PublicCalendar($backend, $calendarInfo, $this->l10n, $config, $logger);

		$this->assertEquals(count($c->getChildren()), 2);

		// test private event
		$privateEvent = $c->getChild('event-1');
		$calData = $privateEvent->get();
		$event = Reader::read($calData);

		$this->assertEquals($start, $event->VEVENT->DTSTART->getValue());
		$this->assertEquals($end, $event->VEVENT->DTEND->getValue());

		$this->assertEquals('Busy', $event->VEVENT->SUMMARY->getValue());
		$this->assertArrayNotHasKey('ATTENDEE', $event->VEVENT);
		$this->assertArrayNotHasKey('LOCATION', $event->VEVENT);
		$this->assertArrayNotHasKey('DESCRIPTION', $event->VEVENT);
		$this->assertArrayNotHasKey('ORGANIZER', $event->VEVENT);
	}
}
