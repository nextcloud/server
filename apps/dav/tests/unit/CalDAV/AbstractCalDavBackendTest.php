<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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

use DateTime;
use DateTimeZone;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IL10N;
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
 * @package OCA\DAV\Tests\unit\CalDAV
 */
abstract class AbstractCalDavBackendTest extends TestCase {

	/** @var CalDavBackend */
	protected $backend;

	/** @var Principal | \PHPUnit_Framework_MockObject_MockObject */
	protected $principal;

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

	protected function createTestCalendar() {
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

	protected function createEvent($calendarId, $start = '20130912T130000Z', $end = '20130912T140000Z') {

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

	protected function assertAcl($principal, $privilege, $acl) {
		foreach($acl as $a) {
			if ($a['principal'] === $principal && $a['privilege'] === $privilege) {
				$this->assertTrue(true);
				return;
			}
		}
		$this->fail("ACL does not contain $principal / $privilege");
	}

	protected function assertNotAcl($principal, $privilege, $acl) {
		foreach($acl as $a) {
			if ($a['principal'] === $principal && $a['privilege'] === $privilege) {
				$this->fail("ACL contains $principal / $privilege");
				return;
			}
		}
		$this->assertTrue(true);
	}

	protected function assertAccess($shouldHaveAcl, $principal, $privilege, $acl) {
		if ($shouldHaveAcl) {
			$this->assertAcl($principal, $privilege, $acl);
		} else {
			$this->assertNotAcl($principal, $privilege, $acl);
		}
	}
}
