<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\DAV\Tests\Unit;

use OCA\DAV\CardDAV\Converter;
use Test\TestCase;

class ConverterTests extends  TestCase {

	/**
	 * @dataProvider providesNewUsers
	 */
	public function testCreation($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null) {
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();
		$user->method('getUID')->willReturn('12345');
		$user->method('getDisplayName')->willReturn($displayName);
		$user->method('getEMailAddress')->willReturn($eMailAddress);
		$user->method('getCloudId')->willReturn($cloudId);

		$converter = new Converter();
		$vCard = $converter->createCardFromUser($user);
		$cardData = $vCard->serialize();

		$this->assertEquals($expectedVCard, $cardData);
	}

	public function providesNewUsers() {
		return [
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:12345\r\nEND:VCARD\r\n"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nCLOUD:foo@bar.net\r\nEND:VCARD\r\n", "Dr. Foo Bar", null, "foo@bar.net"],
		];
	}

	/**
	 * @dataProvider providesUsersForUpdate
	 */
	public function testUpdateOfUnchangedUser($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null) {
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();
		$user->method('getUID')->willReturn('12345');
		$user->method('getDisplayName')->willReturn($displayName);
		$user->method('getEMailAddress')->willReturn($eMailAddress);
		$user->method('getCloudId')->willReturn($cloudId);

		$converter = new Converter();
		$vCard = $converter->createCardFromUser($user);
		$updated = $converter->updateCard($vCard, $user);
		$this->assertFalse($updated);
		$cardData = $vCard->serialize();

		$this->assertEquals($expectedVCard, $cardData);
	}

	public function providesUsersForUpdate() {
		return [
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:12345\r\nEND:VCARD\r\n"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nEMAIL:foo@bar.net\r\nEND:VCARD\r\n", "Dr. Foo Bar", "foo@bar.net"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nCLOUD:foo@bar.net\r\nEND:VCARD\r\n", "Dr. Foo Bar", null, "foo@bar.net"],
		];
	}

	/**
	 * @dataProvider providesUsersForUpdateOfRemovedElement
	 */
	public function testUpdateOfRemovedElement($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null) {
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();
		$user->method('getUID')->willReturn('12345');
		$user->method('getDisplayName')->willReturn($displayName);
		$user->method('getEMailAddress')->willReturn($eMailAddress);
		$user->method('getCloudId')->willReturn($cloudId);

		$converter = new Converter();
		$vCard = $converter->createCardFromUser($user);

		$user1 = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();
		$user1->method('getUID')->willReturn('12345');
		$user1->method('getDisplayName')->willReturn(null);
		$user1->method('getEMailAddress')->willReturn(null);
		$user1->method('getCloudId')->willReturn(null);

		$updated = $converter->updateCard($vCard, $user1);
		$this->assertTrue($updated);
		$cardData = $vCard->serialize();

		$this->assertEquals($expectedVCard, $cardData);
	}

	public function providesUsersForUpdateOfRemovedElement() {
		return [
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:12345\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:12345\r\nEND:VCARD\r\n", "Dr. Foo Bar", "foo@bar.net"],
			["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.7//EN\r\nUID:12345\r\nFN:12345\r\nEND:VCARD\r\n", "Dr. Foo Bar", null, "foo@bar.net"],
		];
	}

}
