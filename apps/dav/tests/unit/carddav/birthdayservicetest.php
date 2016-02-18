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

namespace OCA\DAV\Tests\Unit\CardDAV;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;
use Test\TestCase;

class BirthdayServiceTest extends TestCase {

	/** @var BirthdayService */
	private $service;
	/** @var CalDavBackend | \PHPUnit_Framework_MockObject_MockObject */
	private $calDav;
	/** @var CardDavBackend | \PHPUnit_Framework_MockObject_MockObject */
	private $cardDav;

	public function setUp() {
		parent::setUp();

		$this->calDav = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
		$this->cardDav = $this->getMockBuilder('OCA\DAV\CardDAV\CardDavBackend')->disableOriginalConstructor()->getMock();

		$this->service = new BirthdayService($this->calDav, $this->cardDav);
	}

	/**
	 * @dataProvider providesVCards
	 * @param boolean $nullExpected
	 * @param string | null $data
	 */
	public function testBuildBirthdayFromContact($nullExpected, $data) {
		$cal = $this->service->buildBirthdayFromContact($data);
		if ($nullExpected) {
			$this->assertNull($cal);
		} else {
			$this->assertInstanceOf('Sabre\VObject\Component\VCalendar', $cal);
			$this->assertTrue(isset($cal->VEVENT));
			$this->assertEquals('FREQ=YEARLY', $cal->VEVENT->RRULE->getValue());
			$this->assertEquals('12345\'s Birthday (1900)', $cal->VEVENT->SUMMARY->getValue());
			$this->assertEquals('TRANSPARENT', $cal->VEVENT->TRANSP->getValue());
		}
	}

	public function testOnCardDeleted() {
		$this->cardDav->expects($this->once())->method('getAddressBookById')
			->with(666)
			->willReturn([
			'principaluri' => 'principals/users/user01',
			'uri' => 'default'
		]);
		$this->calDav->expects($this->once())->method('getCalendarByUri')
			->with('principals/users/user01', 'contact_birthdays')
			->willReturn([
			'id' => 1234
		]);
		$this->calDav->expects($this->once())->method('deleteCalendarObject')->with(1234, 'default-gump.vcf.ics');

		$this->service->onCardDeleted(666, 'gump.vcf');
	}

	/**
	 * @dataProvider providesCardChanges
	 */
	public function testOnCardChanged($expectedOp) {
		$this->cardDav->expects($this->once())->method('getAddressBookById')
			->with(666)
			->willReturn([
				'principaluri' => 'principals/users/user01',
				'uri' => 'default'
			]);
		$this->calDav->expects($this->once())->method('getCalendarByUri')
			->with('principals/users/user01', 'contact_birthdays')
			->willReturn([
				'id' => 1234
			]);

		/** @var BirthdayService | \PHPUnit_Framework_MockObject_MockObject $service */
		$service = $this->getMock('\OCA\DAV\CalDAV\BirthdayService',
			['buildBirthdayFromContact', 'birthdayEvenChanged'], [$this->calDav, $this->cardDav]);

		if ($expectedOp === 'delete') {
			$this->calDav->expects($this->once())->method('getCalendarObject')->willReturn('');
			$service->expects($this->once())->method('buildBirthdayFromContact')->willReturn(null);
			$this->calDav->expects($this->once())->method('deleteCalendarObject')->with(1234, 'default-gump.vcf.ics');
		}
		if ($expectedOp === 'create') {
			$service->expects($this->once())->method('buildBirthdayFromContact')->willReturn(new VCalendar());
			$this->calDav->expects($this->once())->method('createCalendarObject')->with(1234, 'default-gump.vcf.ics', "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nEND:VCALENDAR\r\n");
		}
		if ($expectedOp === 'update') {
			$service->expects($this->once())->method('buildBirthdayFromContact')->willReturn(new VCalendar());
			$service->expects($this->once())->method('birthdayEvenChanged')->willReturn(true);
			$this->calDav->expects($this->once())->method('getCalendarObject')->willReturn([
				'calendardata' => '']);
			$this->calDav->expects($this->once())->method('updateCalendarObject')->with(1234, 'default-gump.vcf.ics', "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nEND:VCALENDAR\r\n");
		}

		$service->onCardChanged(666, 'gump.vcf', '');
	}

	/**
	 * @dataProvider providesBirthday
	 * @param $expected
	 * @param $old
	 * @param $new
	 */
	public function testBirthdayEvenChanged($expected, $old, $new) {
		$new = Reader::read($new);
		$this->assertEquals($expected, $this->service->birthdayEvenChanged($old, $new));
	}

	public function providesBirthday() {
		return [
			[true,
				'',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"],
			[false,
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"],
			[true,
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:4567's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"],
			[true,
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000102\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"]
		];
	}

	public function providesCardChanges(){
		return[
			['delete'],
			['create'],
			['update']
		];
	}

	public function providesVCards() {
		return [
			[true, null],
			[true, ''],
			[true, 'yasfewf'],
			[true, "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
			[true, "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nBDAY:\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
			[true, "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nBDAY:someday\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
			[false, "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nBDAY:1900-01-01\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
		];
	}
}
