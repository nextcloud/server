<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Reader;
use Test\TestCase;

class CalendarTest extends TestCase {
	protected IL10N&MockObject $l10n;
	protected IConfig&MockObject $config;
	protected LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
	}

	public function testDelete(): void {
		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$backend->expects($this->never())
			->method('updateShares');
		$backend->expects($this->once())
			->method('unshare');

		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$c->delete();
	}


	public function testDeleteFromGroup(): void {
		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$backend->expects($this->never())
			->method('updateShares');
		$backend->expects($this->once())
			->method('unshare');

		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$c->delete();
	}

	public function testDeleteOwn(): void {
		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$backend->expects($this->never())->method('updateShares');
		$backend->expects($this->never())->method('getShares');

		$this->config->expects($this->never())->method('setUserValue');

		$backend->expects($this->once())->method('deleteCalendar')
			->with(666);

		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user1',
			'id' => 666,
			'uri' => 'cal',
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$c->delete();
	}

	public function testDeleteBirthdayCalendar(): void {
		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$backend->expects($this->once())->method('deleteCalendar')
			->with(666);

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user1', 'dav', 'generateBirthdayCalendar', 'no');

		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'principals/users/user1',
			'principaluri' => 'principals/users/user1',
			'id' => 666,
			'uri' => 'contact_birthdays',
			'{DAV:}displayname' => 'Test',
		];

		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$c->delete();
	}

	public static function dataPropPatch(): array {
		return [
			['user1', 'user2', [], true],
			['user1', 'user2', [
				'{http://owncloud.org/ns}calendar-enabled' => true,
			], true],
			['user1', 'user2', [
				'{DAV:}displayname' => true,
			], true],
			['user1', 'user2', [
				'{DAV:}displayname' => true,
				'{http://owncloud.org/ns}calendar-enabled' => true,
			], true],
			['user1', 'user1', [], false],
			['user1', 'user1', [
				'{http://owncloud.org/ns}calendar-enabled' => true,
			], false],
			['user1', 'user1', [
				'{DAV:}displayname' => true,
			], false],
			['user1', 'user1', [
				'{DAV:}displayname' => true,
				'{http://owncloud.org/ns}calendar-enabled' => true,
			], false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataPropPatch')]
	public function testPropPatch(string $ownerPrincipal, string $principalUri, array $mutations, bool $shared): void {
		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => $ownerPrincipal,
			'principaluri' => $principalUri,
			'id' => 666,
			'uri' => 'default'
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$propPatch = new PropPatch($mutations);

		if (!$shared) {
			$backend->expects($this->once())
				->method('updateCalendar')
				->with(666, $propPatch);
		}
		$c->propPatch($propPatch);
		$this->addToAssertionCount(1);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesReadOnlyInfo')]
	public function testAcl($expectsWrite, $readOnlyValue, $hasOwnerSet, $uri = 'default'): void {
		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$backend->expects($this->any())->method('applyShareAcl')->willReturnArgument(1);
		$calendarInfo = [
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => $uri
		];
		$calendarInfo['{DAV:}displayname'] = 'Test';
		if (!is_null($readOnlyValue)) {
			$calendarInfo['{http://owncloud.org/ns}read-only'] = $readOnlyValue;
		}
		if ($hasOwnerSet) {
			$calendarInfo['{http://owncloud.org/ns}owner-principal'] = 'user1';
		}
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$acl = $c->getACL();
		$childAcl = $c->getChildACL();

		$expectedAcl = [[
			'privilege' => '{DAV:}read',
			'principal' => $hasOwnerSet ? 'user1' : 'user2',
			'protected' => true
		], [
			'privilege' => '{DAV:}read',
			'principal' => ($hasOwnerSet ? 'user1' : 'user2') . '/calendar-proxy-write',
			'protected' => true,
		], [
			'privilege' => '{DAV:}read',
			'principal' => ($hasOwnerSet ? 'user1' : 'user2') . '/calendar-proxy-read',
			'protected' => true,
		]];
		if ($uri === BirthdayService::BIRTHDAY_CALENDAR_URI) {
			$expectedAcl[] = [
				'privilege' => '{DAV:}write-properties',
				'principal' => $hasOwnerSet ? 'user1' : 'user2',
				'protected' => true
			];
			$expectedAcl[] = [
				'privilege' => '{DAV:}write-properties',
				'principal' => ($hasOwnerSet ? 'user1' : 'user2') . '/calendar-proxy-write',
				'protected' => true
			];
		} else {
			$expectedAcl[] = [
				'privilege' => '{DAV:}write',
				'principal' => $hasOwnerSet ? 'user1' : 'user2',
				'protected' => true
			];
			$expectedAcl[] = [
				'privilege' => '{DAV:}write',
				'principal' => ($hasOwnerSet ? 'user1' : 'user2') . '/calendar-proxy-write',
				'protected' => true
			];
		}

		$expectedAcl[] = [
			'privilege' => '{DAV:}write-properties',
			'principal' => ($hasOwnerSet ? 'user1' : 'user2') . '/calendar-proxy-read',
			'protected' => true
		];

		if ($hasOwnerSet) {
			$expectedAcl[] = [
				'privilege' => '{DAV:}read',
				'principal' => 'user2',
				'protected' => true
			];
			if ($expectsWrite) {
				$expectedAcl[] = [
					'privilege' => '{DAV:}write',
					'principal' => 'user2',
					'protected' => true
				];
			} else {
				$expectedAcl[] = [
					'privilege' => '{DAV:}write-properties',
					'principal' => 'user2',
					'protected' => true
				];
			}
		}
		$this->assertEquals($expectedAcl, $acl);
		$this->assertEquals($expectedAcl, $childAcl);
	}

	public static function providesReadOnlyInfo(): array {
		return [
			'read-only property not set' => [true, null, true],
			'read-only property is false' => [true, false, true],
			'read-only property is true' => [false, true, true],
			'read-only property not set and no owner' => [true, null, false],
			'read-only property is false and no owner' => [true, false, false],
			'read-only property is true and no owner' => [false, true, false],
			'birthday calendar' => [false, false, false, BirthdayService::BIRTHDAY_CALENDAR_URI]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesConfidentialClassificationData')]
	public function testPrivateClassification(int $expectedChildren, bool $isShared): void {
		$calObject0 = ['uri' => 'event-0', 'classification' => CalDavBackend::CLASSIFICATION_PUBLIC];
		$calObject1 = ['uri' => 'event-1', 'classification' => CalDavBackend::CLASSIFICATION_CONFIDENTIAL];
		$calObject2 = ['uri' => 'event-2', 'classification' => CalDavBackend::CLASSIFICATION_PRIVATE];

		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
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
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		if ($isShared) {
			$calendarInfo['{http://owncloud.org/ns}owner-principal'] = 'user1';
		}
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$children = $c->getChildren();
		$this->assertCount($expectedChildren, $children);
		$children = $c->getMultipleChildren(['event-0', 'event-1', 'event-2']);
		$this->assertCount($expectedChildren, $children);

		$this->assertEquals(!$isShared, $c->childExists('event-2'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'providesConfidentialClassificationData')]
	public function testConfidentialClassification(int $expectedChildren, bool $isShared): void {
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

		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
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
			'{http://owncloud.org/ns}owner-principal' => $isShared ? 'user1' : 'user2',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		if ($isShared) {
			$calendarInfo['{http://owncloud.org/ns}read-only'] = true;
		}
		$c = new Calendar($backend, $calendarInfo, $this->l10n, $this->config, $this->logger);

		$this->assertCount($expectedChildren, $c->getChildren());

		// test private event
		$privateEvent = $c->getChild('event-1');
		$calData = $privateEvent->get();
		$event = Reader::read($calData);

		$this->assertEquals($start, $event->VEVENT->DTSTART->getValue());
		$this->assertEquals($end, $event->VEVENT->DTEND->getValue());

		if ($isShared) {
			$this->assertEquals('Busy', $event->VEVENT->SUMMARY->getValue());
			$this->assertArrayNotHasKey('ATTENDEE', $event->VEVENT);
			$this->assertArrayNotHasKey('LOCATION', $event->VEVENT);
			$this->assertArrayNotHasKey('DESCRIPTION', $event->VEVENT);
			$this->assertArrayNotHasKey('ORGANIZER', $event->VEVENT);
		} else {
			$this->assertEquals('Test Event', $event->VEVENT->SUMMARY->getValue());
		}

		// Test l10n
		$l10n = $this->createMock(IL10N::class);
		if ($isShared) {
			$l10n->expects($this->once())
				->method('t')
				->with('Busy')
				->willReturn('Translated busy');
		} else {
			$l10n->expects($this->never())
				->method('t');
		}
		$c = new Calendar($backend, $calendarInfo, $l10n, $this->config, $this->logger);

		$calData = $c->getChild('event-1')->get();
		$event = Reader::read($calData);

		if ($isShared) {
			$this->assertEquals('Translated busy', $event->VEVENT->SUMMARY->getValue());
		} else {
			$this->assertEquals('Test Event', $event->VEVENT->SUMMARY->getValue());
		}
	}

	public static function providesConfidentialClassificationData(): array {
		return [
			[3, false],
			[2, true]
		];
	}

	public function testRemoveVAlarms(): void {
		$publicObjectData = <<<EOD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud calendar v1.5.6
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20171022T125130
DTSTAMP:20171022T125130
LAST-MODIFIED:20171022T125130
UID:PPL24TH8UGOWE94XET87ER
SUMMARY:Foo bar blub
CLASS:PUBLIC
STATUS:CONFIRMED
DTSTART;VALUE=DATE:20171024
DTEND;VALUE=DATE:20171025
BEGIN:VALARM
ACTION:AUDIO
TRIGGER:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR

EOD;

		$confidentialObjectData = <<<EOD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud calendar v1.5.6
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20171022T125130
DTSTAMP:20171022T125130
LAST-MODIFIED:20171022T125130
UID:PPL24TH8UGOWE94XET87ER
SUMMARY:Foo bar blub
CLASS:CONFIDENTIAL
STATUS:CONFIRMED
DTSTART;VALUE=DATE:20171024
DTEND;VALUE=DATE:20171025
BEGIN:VALARM
ACTION:AUDIO
TRIGGER:-PT15M
END:VALARM
END:VEVENT
END:VCALENDAR

EOD;

		$publicObjectDataWithoutVAlarm = <<<EOD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud calendar v1.5.6
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20171022T125130
DTSTAMP:20171022T125130
LAST-MODIFIED:20171022T125130
UID:PPL24TH8UGOWE94XET87ER
SUMMARY:Foo bar blub
CLASS:PUBLIC
STATUS:CONFIRMED
DTSTART;VALUE=DATE:20171024
DTEND;VALUE=DATE:20171025
END:VEVENT
END:VCALENDAR

EOD;

		$confidentialObjectCleaned = <<<EOD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud calendar v1.5.6
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20171022T125130
UID:PPL24TH8UGOWE94XET87ER
SUMMARY:Busy
CLASS:CONFIDENTIAL
DTSTART;VALUE=DATE:20171024
DTEND;VALUE=DATE:20171025
END:VEVENT
END:VCALENDAR

EOD;



		$publicObject = ['uri' => 'event-0',
			'classification' => CalDavBackend::CLASSIFICATION_PUBLIC,
			'calendardata' => $publicObjectData];

		$confidentialObject = ['uri' => 'event-1',
			'classification' => CalDavBackend::CLASSIFICATION_CONFIDENTIAL,
			'calendardata' => $confidentialObjectData];

		/** @var CalDavBackend&MockObject $backend */
		$backend = $this->createMock(CalDavBackend::class);
		$backend->expects($this->any())
			->method('getCalendarObjects')
			->willReturn([$publicObject, $confidentialObject]);

		$backend->expects($this->any())
			->method('getMultipleCalendarObjects')
			->with(666, ['event-0', 'event-1'])
			->willReturn([$publicObject, $confidentialObject]);

		$backend->expects($this->any())
			->method('getCalendarObject')
			->willReturnCallback(function ($cId, $uri) use ($publicObject, $confidentialObject) {
				switch ($uri) {
					case 'event-0':
						return $publicObject;

					case 'event-1':
						return $confidentialObject;

					default:
						throw new \Exception('unexpected uri');
				}
			});

		$backend->expects($this->any())
			->method('applyShareAcl')
			->willReturnArgument(1);

		$calendarInfoOwner = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user1',
			'id' => 666,
			'uri' => 'cal',
		];
		$calendarInfoSharedRW = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$calendarInfoSharedRO = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{http://owncloud.org/ns}read-only' => true,
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$ownerCalendar = new Calendar($backend, $calendarInfoOwner, $this->l10n, $this->config, $this->logger);
		$rwCalendar = new Calendar($backend, $calendarInfoSharedRW, $this->l10n, $this->config, $this->logger);
		$roCalendar = new Calendar($backend, $calendarInfoSharedRO, $this->l10n, $this->config, $this->logger);

		$this->assertCount(2, $ownerCalendar->getChildren());
		$this->assertCount(2, $rwCalendar->getChildren());
		$this->assertCount(2, $roCalendar->getChildren());

		// calendar data shall not be altered for the owner
		$this->assertEquals($publicObjectData, $ownerCalendar->getChild('event-0')->get());
		$this->assertEquals($confidentialObjectData, $ownerCalendar->getChild('event-1')->get());

		// valarms shall not be removed for read-write shares
		$this->assertEquals(
			$this->fixLinebreak($publicObjectData),
			$this->fixLinebreak($rwCalendar->getChild('event-0')->get()));
		$this->assertEquals(
			$this->fixLinebreak($confidentialObjectData),
			$this->fixLinebreak($rwCalendar->getChild('event-1')->get()));

		// valarms shall be removed for read-only shares
		$this->assertEquals(
			$this->fixLinebreak($publicObjectDataWithoutVAlarm),
			$this->fixLinebreak($roCalendar->getChild('event-0')->get()));
		$this->assertEquals(
			$this->fixLinebreak($confidentialObjectCleaned),
			$this->fixLinebreak($roCalendar->getChild('event-1')->get()));
	}

	private function fixLinebreak(string $str): string {
		return preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $str);
	}
}
