<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace Tests\Connector\Sabre;

use DateTime;
use DateTimeZone;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\Connector\Sabre\Principal;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\IACL;
use Test\TestCase;

/**
 * Class CalDavBackendTest
 *
 * @group DB
 *
 * @package Tests\Connector\Sabre
 */
class CalDavBackendTest extends TestCase {

	/** @var CalDavBackend */
	private $backend;

	/** @var Principal | \PHPUnit_Framework_MockObject_MockObject */
	private $principal;

	const UNIT_TEST_USER = 'principals/users/caldav-unit-test';
	const UNIT_TEST_USER1 = 'principals/users/caldav-unit-test1';
	const UNIT_TEST_GROUP = 'principals/groups/caldav-unit-test-group';

	public function setUp() {
		parent::setUp();

		$this->principal = $this->getMockBuilder('OCA\DAV\Connector\Sabre\Principal')
			->disableOriginalConstructor()
			->setMethods(['getPrincipalByPath', 'getGroupMembership'])
			->getMock();
		$this->principal->expects($this->any())->method('getPrincipalByPath')
			->willReturn([
				'uri' => 'principals/best-friend'
			]);
		$this->principal->expects($this->any())->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([self::UNIT_TEST_GROUP]);

		$db = \OC::$server->getDatabaseConnection();
		$this->backend = new CalDavBackend($db, $this->principal);

		$this->tearDown();
	}

	public function tearDown() {
		parent::tearDown();

		if (is_null($this->backend)) {
			return;
		}
		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		foreach ($books as $book) {
			$this->backend->deleteCalendar($book['id']);
		}
		$subscriptions = $this->backend->getSubscriptionsForUser(self::UNIT_TEST_USER);
		foreach ($subscriptions as $subscription) {
			$this->backend->deleteSubscription($subscription['id']);
		}
	}

	public function testCalendarOperations() {

		$calendarId = $this->createTestCalendar();

		// update it's display name
		$patch = new PropPatch([
			'{DAV:}displayname' => 'Unit test',
			'{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar used for unit testing'
		]);
		$this->backend->updateCalendar($calendarId, $patch);
		$patch->commit();
		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$this->assertEquals('Unit test', $books[0]['{DAV:}displayname']);
		$this->assertEquals('Calendar used for unit testing', $books[0]['{urn:ietf:params:xml:ns:caldav}calendar-description']);

		// delete the address book
		$this->backend->deleteCalendar($books[0]['id']);
		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(0, count($books));
	}

	public function providesSharingData() {
		return [
			[true, true, true, false, [
				[
					'href' => 'principal:' . self::UNIT_TEST_USER1,
					'readOnly' => false
				],
				[
					'href' => 'principal:' . self::UNIT_TEST_GROUP,
					'readOnly' => true
				]
			]],
			[true, false, false, false, [
				[
					'href' => 'principal:' . self::UNIT_TEST_USER1,
					'readOnly' => true
				],
			]],

		];
	}

	/**
	 * @dataProvider providesSharingData
	 */
	public function testCalendarSharing($userCanRead, $userCanWrite, $groupCanRead, $groupCanWrite, $add) {

		$calendarId = $this->createTestCalendar();
		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($books));
		$calendar = new Calendar($this->backend, $books[0]);
		$this->backend->updateShares($calendar, $add, []);
		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER1);
		$this->assertEquals(1, count($books));
		$calendar = new Calendar($this->backend, $books[0]);
		$acl = $calendar->getACL();
		$this->assertAcl(self::UNIT_TEST_USER, '{DAV:}read', $acl);
		$this->assertAcl(self::UNIT_TEST_USER, '{DAV:}write', $acl);
		$this->assertAccess($userCanRead, self::UNIT_TEST_USER1, '{DAV:}read', $acl);
		$this->assertAccess($userCanWrite, self::UNIT_TEST_USER1, '{DAV:}write', $acl);
		$this->assertAccess($groupCanRead, self::UNIT_TEST_GROUP, '{DAV:}read', $acl);
		$this->assertAccess($groupCanWrite, self::UNIT_TEST_GROUP, '{DAV:}write', $acl);
		$this->assertEquals(self::UNIT_TEST_USER, $calendar->getOwner());

		// test acls on the child
		$uri = $this->getUniqueID('calobj');
		$calData = <<<'EOD'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:20130910T125139Z
UID:47d15e3ec8
LAST-MODIFIED;VALUE=DATE-TIME:20130910T125139Z
DTSTAMP;VALUE=DATE-TIME:20130910T125139Z
SUMMARY:Test Event
DTSTART;VALUE=DATE-TIME:20130912T130000Z
DTEND;VALUE=DATE-TIME:20130912T140000Z
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
EOD;

		$this->backend->createCalendarObject($calendarId, $uri, $calData);

		/** @var IACL $child */
		$child = $calendar->getChild($uri);
		$acl = $child->getACL();
		$this->assertAcl(self::UNIT_TEST_USER, '{DAV:}read', $acl);
		$this->assertAcl(self::UNIT_TEST_USER, '{DAV:}write', $acl);
		$this->assertAccess($userCanRead, self::UNIT_TEST_USER1, '{DAV:}read', $acl);
		$this->assertAccess($userCanWrite, self::UNIT_TEST_USER1, '{DAV:}write', $acl);
		$this->assertAccess($groupCanRead, self::UNIT_TEST_GROUP, '{DAV:}read', $acl);
		$this->assertAccess($groupCanWrite, self::UNIT_TEST_GROUP, '{DAV:}write', $acl);

		// delete the address book
		$this->backend->deleteCalendar($books[0]['id']);
		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(0, count($books));
	}

	public function testCalendarObjectsOperations() {

		$calendarId = $this->createTestCalendar();

		// create a card
		$uri = $this->getUniqueID('calobj');
		$calData = <<<'EOD'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:20130910T125139Z
UID:47d15e3ec8
LAST-MODIFIED;VALUE=DATE-TIME:20130910T125139Z
DTSTAMP;VALUE=DATE-TIME:20130910T125139Z
SUMMARY:Test Event
DTSTART;VALUE=DATE-TIME:20130912T130000Z
DTEND;VALUE=DATE-TIME:20130912T140000Z
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
EOD;

		$this->backend->createCalendarObject($calendarId, $uri, $calData);

		// get all the cards
		$calendarObjects = $this->backend->getCalendarObjects($calendarId);
		$this->assertEquals(1, count($calendarObjects));
		$this->assertEquals($calendarId, $calendarObjects[0]['calendarid']);

		// get the cards
		$calendarObject = $this->backend->getCalendarObject($calendarId, $uri);
		$this->assertNotNull($calendarObject);
		$this->assertArrayHasKey('id', $calendarObject);
		$this->assertArrayHasKey('uri', $calendarObject);
		$this->assertArrayHasKey('lastmodified', $calendarObject);
		$this->assertArrayHasKey('etag', $calendarObject);
		$this->assertArrayHasKey('size', $calendarObject);
		$this->assertEquals($calData, $calendarObject['calendardata']);

		// update the card
		$calData = <<<'EOD'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:20130910T125139Z
UID:47d15e3ec8
LAST-MODIFIED;VALUE=DATE-TIME:20130910T125139Z
DTSTAMP;VALUE=DATE-TIME:20130910T125139Z
SUMMARY:Test Event
DTSTART;VALUE=DATE-TIME:20130912T130000Z
DTEND;VALUE=DATE-TIME:20130912T140000Z
END:VEVENT
END:VCALENDAR
EOD;
		$this->backend->updateCalendarObject($calendarId, $uri, $calData);
		$calendarObject = $this->backend->getCalendarObject($calendarId, $uri);
		$this->assertEquals($calData, $calendarObject['calendardata']);

		// delete the card
		$this->backend->deleteCalendarObject($calendarId, $uri);
		$calendarObjects = $this->backend->getCalendarObjects($calendarId);
		$this->assertEquals(0, count($calendarObjects));
	}

	public function testMultiCalendarObjects() {

		$calendarId = $this->createTestCalendar();

		// create an event
		$calData = <<<'EOD'
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:20130910T125139Z
UID:47d15e3ec8
LAST-MODIFIED;VALUE=DATE-TIME:20130910T125139Z
DTSTAMP;VALUE=DATE-TIME:20130910T125139Z
SUMMARY:Test Event
DTSTART;VALUE=DATE-TIME:20130912T130000Z
DTEND;VALUE=DATE-TIME:20130912T140000Z
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
EOD;
		$uri0 = $this->getUniqueID('card');
		$this->backend->createCalendarObject($calendarId, $uri0, $calData);
		$uri1 = $this->getUniqueID('card');
		$this->backend->createCalendarObject($calendarId, $uri1, $calData);
		$uri2 = $this->getUniqueID('card');
		$this->backend->createCalendarObject($calendarId, $uri2, $calData);

		// get all the cards
		$calendarObjects = $this->backend->getCalendarObjects($calendarId);
		$this->assertEquals(3, count($calendarObjects));

		// get the cards
		$calendarObjects = $this->backend->getMultipleCalendarObjects($calendarId, [$uri1, $uri2]);
		$this->assertEquals(2, count($calendarObjects));
		foreach($calendarObjects as $card) {
			$this->assertArrayHasKey('id', $card);
			$this->assertArrayHasKey('uri', $card);
			$this->assertArrayHasKey('lastmodified', $card);
			$this->assertArrayHasKey('etag', $card);
			$this->assertArrayHasKey('size', $card);
			$this->assertEquals($calData, $card['calendardata']);
		}

		// delete the card
		$this->backend->deleteCalendarObject($calendarId, $uri0);
		$this->backend->deleteCalendarObject($calendarId, $uri1);
		$this->backend->deleteCalendarObject($calendarId, $uri2);
		$calendarObjects = $this->backend->getCalendarObjects($calendarId);
		$this->assertEquals(0, count($calendarObjects));
	}

	/**
	 * @dataProvider providesCalendarQueryParameters
	 */
	public function testCalendarQuery($expectedEventsInResult, $propFilters, $compFilter) {
		$calendarId = $this->createTestCalendar();
		$events = [];
		$events[0] = $this->createEvent($calendarId, '20130912T130000Z', '20130912T140000Z');
		$events[1] = $this->createEvent($calendarId, '20130912T150000Z', '20130912T170000Z');
		$events[2] = $this->createEvent($calendarId, '20130912T173000Z', '20130912T220000Z');

		$result = $this->backend->calendarQuery($calendarId, [
			'name' => '',
			'prop-filters' => $propFilters,
			'comp-filters' => $compFilter
		]);

		$expectedEventsInResult = array_map(function($index) use($events) {
			return $events[$index];
		}, $expectedEventsInResult);
		$this->assertEquals($expectedEventsInResult, $result, '', 0.0, 10, true);
	}

	public function testGetCalendarObjectByUID() {
		$calendarId = $this->createTestCalendar();
		$this->createEvent($calendarId, '20130912T130000Z', '20130912T140000Z');

		$co = $this->backend->getCalendarObjectByUID(self::UNIT_TEST_USER, '47d15e3ec8');
		$this->assertNotNull($co);
	}

	public function providesCalendarQueryParameters() {
		return [
			'all' => [[0, 1, 2], [], []],
			'only-todos' => [[], ['name' => 'VTODO'], []],
			'only-events' => [[0, 1, 2], [], [['name' => 'VEVENT', 'is-not-defined' => false, 'comp-filters' => [], 'time-range' => ['start' => null, 'end' => null], 'prop-filters' => []]],],
			'start' => [[1, 2], [], [['name' => 'VEVENT', 'is-not-defined' => false, 'comp-filters' => [], 'time-range' => ['start' => new DateTime('2013-09-12 14:00:00', new DateTimeZone('UTC')), 'end' => null], 'prop-filters' => []]],],
			'end' => [[0], [], [['name' => 'VEVENT', 'is-not-defined' => false, 'comp-filters' => [], 'time-range' => ['start' => null, 'end' => new DateTime('2013-09-12 14:00:00', new DateTimeZone('UTC'))], 'prop-filters' => []]],],
		];
	}

	private function createTestCalendar() {
		$this->backend->createCalendar(self::UNIT_TEST_USER, 'Example', [
			'{http://apple.com/ns/ical/}calendar-color' => '#1C4587FF'
		]);
		$calendars = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($calendars));
		$this->assertEquals(self::UNIT_TEST_USER, $calendars[0]['principaluri']);
		/** @var SupportedCalendarComponentSet $components */
		$components = $calendars[0]['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'];
		$this->assertEquals(['VEVENT','VTODO'], $components->getValue());
		$color = $calendars[0]['{http://apple.com/ns/ical/}calendar-color'];
		$this->assertEquals('#1C4587FF', $color);
		$this->assertEquals('Example', $calendars[0]['uri']);
		$this->assertEquals('Example', $calendars[0]['{DAV:}displayname']);
		$calendarId = $calendars[0]['id'];

		return $calendarId;
	}

	private function createEvent($calendarId, $start = '20130912T130000Z', $end = '20130912T140000Z') {

		$calData = <<<EOD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:ownCloud Calendar
BEGIN:VEVENT
CREATED;VALUE=DATE-TIME:20130910T125139Z
UID:47d15e3ec8
LAST-MODIFIED;VALUE=DATE-TIME:20130910T125139Z
DTSTAMP;VALUE=DATE-TIME:20130910T125139Z
SUMMARY:Test Event
DTSTART;VALUE=DATE-TIME:$start
DTEND;VALUE=DATE-TIME:$end
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
EOD;
		$uri0 = $this->getUniqueID('event');
		$this->backend->createCalendarObject($calendarId, $uri0, $calData);

		return $uri0;
	}

	public function testSyncSupport() {
		$calendarId = $this->createTestCalendar();

		// fist call without synctoken
		$changes = $this->backend->getChangesForCalendar($calendarId, '', 1);
		$syncToken = $changes['syncToken'];

		// add a change
		$event = $this->createEvent($calendarId, '20130912T130000Z', '20130912T140000Z');

		// look for changes
		$changes = $this->backend->getChangesForCalendar($calendarId, $syncToken, 1);
		$this->assertEquals($event, $changes['added'][0]);
	}

	public function testSubscriptions() {
		$id = $this->backend->createSubscription(self::UNIT_TEST_USER, 'Subscription', [
			'{http://calendarserver.org/ns/}source' => new Href('test-source')
		]);

		$subscriptions = $this->backend->getSubscriptionsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($subscriptions));
		$this->assertEquals($id, $subscriptions[0]['id']);

		$patch = new PropPatch([
				'{DAV:}displayname' => 'Unit test',
		]);
		$this->backend->updateSubscription($id, $patch);
		$patch->commit();

		$subscriptions = $this->backend->getSubscriptionsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($subscriptions));
		$this->assertEquals($id, $subscriptions[0]['id']);
		$this->assertEquals('Unit test', $subscriptions[0]['{DAV:}displayname']);

		$this->backend->deleteSubscription($id);
		$subscriptions = $this->backend->getSubscriptionsForUser(self::UNIT_TEST_USER);
		$this->assertEquals(0, count($subscriptions));
	}

	public function testScheduling() {
		$this->backend->createSchedulingObject(self::UNIT_TEST_USER, 'Sample Schedule', '');

		$sos = $this->backend->getSchedulingObjects(self::UNIT_TEST_USER);
		$this->assertEquals(1, count($sos));

		$so = $this->backend->getSchedulingObject(self::UNIT_TEST_USER, 'Sample Schedule');
		$this->assertNotNull($so);

		$this->backend->deleteSchedulingObject(self::UNIT_TEST_USER, 'Sample Schedule');

		$sos = $this->backend->getSchedulingObjects(self::UNIT_TEST_USER);
		$this->assertEquals(0, count($sos));
	}

	/**
	 * @dataProvider providesCalDataForGetDenormalizedData
	 */
	public function testGetDenormalizedData($expectedFirstOccurance, $calData) {
		$actual = $this->invokePrivate($this->backend, 'getDenormalizedData', [$calData]);
		$this->assertEquals($expectedFirstOccurance, $actual['firstOccurence']);
	}

	public function providesCalDataForGetDenormalizedData() {
		return [
			[0, "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:413F269B-B51B-46B1-AFB6-40055C53A4DC\r\nDTSTAMP:20160309T095056Z\r\nDTSTART;VALUE=DATE:16040222\r\nDTEND;VALUE=DATE:16040223\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:SUMMARY\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"],
			[null, "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:413F269B-B51B-46B1-AFB6-40055C53A4DC\r\nDTSTAMP:20160309T095056Z\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:SUMMARY\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"]
		];
	}

	private function assertAcl($principal, $privilege, $acl) {
		foreach($acl as $a) {
			if ($a['principal'] === $principal && $a['privilege'] === $privilege) {
				$this->assertTrue(true);
				return;
			}
		}
		$this->fail("ACL does not contain $principal / $privilege");
	}

	private function assertNotAcl($principal, $privilege, $acl) {
		foreach($acl as $a) {
			if ($a['principal'] === $principal && $a['privilege'] === $privilege) {
				$this->fail("ACL contains $principal / $privilege");
				return;
			}
		}
		$this->assertTrue(true);
	}

	private function assertAccess($shouldHaveAcl, $principal, $privilege, $acl) {
		if ($shouldHaveAcl) {
			$this->assertAcl($principal, $privilege, $acl);
		} else {
			$this->assertNotAcl($principal, $privilege, $acl);
		}
	}
}
