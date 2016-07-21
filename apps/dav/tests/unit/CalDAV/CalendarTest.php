<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\IL10N;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Reader;
use Test\TestCase;

class CalendarTest extends TestCase {

	/** @var IL10N */
	private $l10n;

	public function setUp() {
		parent::setUp();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function ($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
	}

	public function testDelete() {
		/** @var \PHPUnit_Framework_MockObject_MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
		$backend->expects($this->once())->method('updateShares');
		$backend->expects($this->any())->method('getShares')->willReturn([
			['href' => 'principal:user2']
		]);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n);
		$c->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteFromGroup() {
		/** @var \PHPUnit_Framework_MockObject_MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
		$backend->expects($this->never())->method('updateShares');
		$backend->expects($this->any())->method('getShares')->willReturn([
			['href' => 'principal:group2']
		]);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n);
		$c->delete();
	}

	public function dataPropPatch() {
		return [
			[[], true],
			[[
				'{http://owncloud.org/ns}calendar-enabled' => true,
			], false],
			[[
				'{DAV:}displayname' => true,
			], true],
			[[
				'{DAV:}displayname' => true,
				'{http://owncloud.org/ns}calendar-enabled' => true,
			], true],
		];
	}

	/**
	 * @dataProvider dataPropPatch
	 */
	public function testPropPatch($mutations, $throws) {
		/** @var \PHPUnit_Framework_MockObject_MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default'
		];
		$c = new Calendar($backend, $calendarInfo, $this->l10n);

		if ($throws) {
			$this->setExpectedException('\Sabre\DAV\Exception\Forbidden');
		}
		$c->propPatch(new PropPatch($mutations));
		if (!$throws) {
			$this->assertTrue(true);
		}
	}

	/**
	 * @dataProvider providesReadOnlyInfo
	 */
	public function testAcl($expectsWrite, $readOnlyValue, $hasOwnerSet, $uri = 'default') {
		/** @var \PHPUnit_Framework_MockObject_MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
		$backend->expects($this->any())->method('applyShareAcl')->willReturnArgument(1);
		$calendarInfo = [
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => $uri
		];
		if (!is_null($readOnlyValue)) {
			$calendarInfo['{http://owncloud.org/ns}read-only'] = $readOnlyValue;
		}
		if ($hasOwnerSet) {
			$calendarInfo['{http://owncloud.org/ns}owner-principal'] = 'user1';
		}
		$c = new Calendar($backend, $calendarInfo, $this->l10n);
		$acl = $c->getACL();
		$childAcl = $c->getChildACL();

		$expectedAcl = [[
			'privilege' => '{DAV:}read',
			'principal' => $hasOwnerSet ? 'user1' : 'user2',
			'protected' => true
		], [
			'privilege' => '{DAV:}write',
			'principal' => $hasOwnerSet ? 'user1' : 'user2',
			'protected' => true
		]];
		if ($uri === BirthdayService::BIRTHDAY_CALENDAR_URI) {
			$expectedAcl = [[
				'privilege' => '{DAV:}read',
				'principal' => $hasOwnerSet ? 'user1' : 'user2',
				'protected' => true
			]];
		}
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
			}
		}
		$this->assertEquals($expectedAcl, $acl);
		$this->assertEquals($expectedAcl, $childAcl);
	}

	public function providesReadOnlyInfo() {
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

	/**
	 * @dataProvider providesConfidentialClassificationData
	 * @param $expectedChildren
	 * @param $isShared
	 */
	public function testPrivateClassification($expectedChildren, $isShared) {

		$calObject0 = ['uri' => 'event-0', 'classification' => CalDavBackend::CLASSIFICATION_PUBLIC];
		$calObject1 = ['uri' => 'event-1', 'classification' => CalDavBackend::CLASSIFICATION_CONFIDENTIAL];
		$calObject2 = ['uri' => 'event-2', 'classification' => CalDavBackend::CLASSIFICATION_PRIVATE];

		/** @var \PHPUnit_Framework_MockObject_MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
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

		$calendarInfo = [
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		if ($isShared) {
			$calendarInfo['{http://owncloud.org/ns}owner-principal'] = 'user1';

		}
		$c = new Calendar($backend, $calendarInfo, $this->l10n);
		$children = $c->getChildren();
		$this->assertEquals($expectedChildren, count($children));
		$children = $c->getMultipleChildren(['event-0', 'event-1', 'event-2']);
		$this->assertEquals($expectedChildren, count($children));

		$this->assertEquals(!$isShared, $c->childExists('event-2'));
	}

	/**
	 * @dataProvider providesConfidentialClassificationData
	 * @param $expectedChildren
	 * @param $isShared
	 */
	public function testConfidentialClassification($expectedChildren, $isShared) {
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

		/** @var \PHPUnit_Framework_MockObject_MockObject | CalDavBackend $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
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

		$calendarInfo = [
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		if ($isShared) {
			$calendarInfo['{http://owncloud.org/ns}owner-principal'] = 'user1';

		}
		$c = new Calendar($backend, $calendarInfo, $this->l10n);

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
	}

	public function providesConfidentialClassificationData() {
		return [
			[3, false],
			[2, true]
		];
	}
}
