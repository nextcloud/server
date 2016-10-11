<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\Converter;
use Test\TestCase;

class ConverterTest extends  TestCase {

	/**
	 * @dataProvider providesNewUsers
	 */
	public function testCreation($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null) {
		$user = $this->getUserMock($displayName, $eMailAddress, $cloudId);

		$converter = new Converter();
		$vCard = $converter->createCardFromUser($user);
		$cardData = $vCard->serialize();

		$this->assertEquals($expectedVCard, $cardData);
	}

	public function providesNewUsers() {
		return [
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nPHOTO;ENCODING=b;TYPE=JPEG:MTIzNDU2Nzg5\r\nEND:VCARD\r\n"],
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nN:Bar;Dr.;Foo;;\r\nPHOTO;ENCODING=b;TYPE=JPEG:MTIzNDU2Nzg5\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nN:Bar;Dr.;Foo;;\r\nEMAIL;TYPE=OTHER:foo@bar.net\r\nPHOTO;ENCODING=b;TYPE=JPEG:MTIzNDU2Nzg5\r\nEND:VCARD\r\n", "Dr. Foo Bar", "foo@bar.net"],
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:Dr. Foo Bar\r\nN:Bar;Dr.;Foo;;\r\nCLOUD:foo@bar.net\r\nPHOTO;ENCODING=b;TYPE=JPEG:MTIzNDU2Nzg5\r\nEND:VCARD\r\n", "Dr. Foo Bar", null, "foo@bar.net"],
		];
	}

	/**
	 * @dataProvider providesNewUsers
	 */
	public function testUpdateOfUnchangedUser($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null) {
		$user = $this->getUserMock($displayName, $eMailAddress, $cloudId);

		$converter = new Converter();
		$vCard = $converter->createCardFromUser($user);
		$updated = $converter->updateCard($vCard, $user);
		$this->assertFalse($updated);
		$cardData = $vCard->serialize();

		$this->assertEquals($expectedVCard, $cardData);
	}

	/**
	 * @dataProvider providesUsersForUpdateOfRemovedElement
	 */
	public function testUpdateOfRemovedElement($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null) {
		$user = $this->getUserMock($displayName, $eMailAddress, $cloudId);

		$converter = new Converter();
		$vCard = $converter->createCardFromUser($user);

		$user1 = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();
		$user1->method('getUID')->willReturn('12345');
		$user1->method('getDisplayName')->willReturn(null);
		$user1->method('getEMailAddress')->willReturn(null);
		$user1->method('getCloudId')->willReturn(null);
		$user1->method('getAvatarImage')->willReturn(null);

		$updated = $converter->updateCard($vCard, $user1);
		$this->assertTrue($updated);
		$cardData = $vCard->serialize();

		$this->assertEquals($expectedVCard, $cardData);
	}

	public function providesUsersForUpdateOfRemovedElement() {
		return [
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nEND:VCARD\r\n", "Dr. Foo Bar"],
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nEND:VCARD\r\n", "Dr. Foo Bar", "foo@bar.net"],
				["BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nEND:VCARD\r\n", "Dr. Foo Bar", null, "foo@bar.net"],
		];
	}

	/**
	 * @dataProvider providesNames
	 * @param $expected
	 * @param $fullName
	 */
	public function testNameSplitter($expected, $fullName) {

		$converter = new Converter();
		$r = $converter->splitFullName($fullName);
		$r = implode(';', $r);
		$this->assertEquals($expected, $r);
	}

	public function providesNames() {
		return [
				['Sauron;;;;', 'Sauron'],
				['Baggins;Bilbo;;;', 'Bilbo Baggins'],
				['Tolkien;John;Ronald Reuel;;', 'John Ronald Reuel Tolkien'],
		];
	}

	/**
	 * @param $displayName
	 * @param $eMailAddress
	 * @param $cloudId
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getUserMock($displayName, $eMailAddress, $cloudId) {
		$image0 = $this->getMockBuilder('OCP\IImage')->disableOriginalConstructor()->getMock();
		$image0->method('mimeType')->willReturn('JPEG');
		$image0->method('data')->willReturn('123456789');
		$user = $this->getMockBuilder('OCP\IUser')->disableOriginalConstructor()->getMock();
		$user->method('getUID')->willReturn('12345');
		$user->method('getDisplayName')->willReturn($displayName);
		$user->method('getEMailAddress')->willReturn($eMailAddress);
		$user->method('getCloudId')->willReturn($cloudId);
		$user->method('getAvatarImage')->willReturn($image0);
		return $user;
	}
}
