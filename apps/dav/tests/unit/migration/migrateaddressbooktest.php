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
namespace OCA\DAV\Tests\Unit\Migration;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\Dav\Migration\AddressBookAdapter;
use OCP\ILogger;
use Test\TestCase;

class MigrateAddressbookTest extends TestCase {

	public function testMigration() {
		/** @var AddressBookAdapter | \PHPUnit_Framework_MockObject_MockObject $adapter */
		$adapter = $this->mockAdapter();

		/** @var CardDavBackend | \PHPUnit_Framework_MockObject_MockObject $cardDav */
		$cardDav = $this->getMockBuilder('\OCA\Dav\CardDAV\CardDAVBackend')->disableOriginalConstructor()->getMock();
		$cardDav->method('createAddressBook')->willReturn(666);
		$cardDav->expects($this->once())->method('createAddressBook')->with('principals/users/test01', 'test_contacts');
		$cardDav->expects($this->once())->method('createCard')->with(666, '63f0dd6c-39d5-44be-9d34-34e7a7441fc2.vcf', 'BEGIN:VCARD');
		/** @var ILogger $logger */
		$logger = $this->getMockBuilder('\OCP\ILogger')->disableOriginalConstructor()->getMock();

		$m = new \OCA\Dav\Migration\MigrateAddressbooks($adapter, $cardDav, $logger, null);
		$m->migrateForUser('test01');
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockAdapter($shares = []) {
		$adapter = $this->getMockBuilder('\OCA\Dav\Migration\AddressBookAdapter')->disableOriginalConstructor()->getMock();
		$adapter->method('foreachBook')->willReturnCallback(function ($user, \Closure $callBack) {
			$callBack([
				'id' => 0,
				'userid' => $user,
				'displayname' => 'Test Contacts',
				'uri' => 'test_contacts',
				'description' => 'Contacts to test with',
				'ctag' => 1234567890,
				'active' => 1
			]);
		});
		$adapter->method('foreachCard')->willReturnCallback(function ($addressBookId, \Closure $callBack) {
			$callBack([
				'userid' => $addressBookId,
				'uri' => '63f0dd6c-39d5-44be-9d34-34e7a7441fc2.vcf',
				'carddata' => 'BEGIN:VCARD'
			]);
		});
		$adapter->method('getShares')->willReturn($shares);
		return $adapter;
	}

}
