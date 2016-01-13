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
namespace OCA\DAV\CardDAV;

use Test\TestCase;

class SyncServiceTest extends TestCase {
	public function testEmptySync() {
		$backend = $this->getBackendMock(0, 0, 0);

		$ss = $this->getSyncServiceMock($backend, []);
		$return = $ss->syncRemoteAddressBook('', 'system', '1234567890', null, '1', 'principals/system/system', []);
		$this->assertEquals('sync-token-1', $return);
	}

	public function testSyncWithNewElement() {
		$backend = $this->getBackendMock(1, 0, 0);
		$backend->method('getCard')->willReturn(false);

		$ss = $this->getSyncServiceMock($backend, ['0' => [200 => '']]);
		$return = $ss->syncRemoteAddressBook('', 'system', '1234567890', null, '1', 'principals/system/system', []);
		$this->assertEquals('sync-token-1', $return);
	}

	public function testSyncWithUpdatedElement() {
		$backend = $this->getBackendMock(0, 1, 0);
		$backend->method('getCard')->willReturn(true);

		$ss = $this->getSyncServiceMock($backend, ['0' => [200 => '']]);
		$return = $ss->syncRemoteAddressBook('', 'system', '1234567890', null, '1', 'principals/system/system', []);
		$this->assertEquals('sync-token-1', $return);
	}

	public function testSyncWithDeletedElement() {
		$backend = $this->getBackendMock(0, 0, 1);

		$ss = $this->getSyncServiceMock($backend, ['0' => [404 => '']]);
		$return = $ss->syncRemoteAddressBook('', 'system', '1234567890', null, '1', 'principals/system/system', []);
		$this->assertEquals('sync-token-1', $return);
	}

	public function testEnsureSystemAddressBookExists() {
		/** @var CardDavBackend | \PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMockBuilder('OCA\DAV\CardDAV\CardDAVBackend')->disableOriginalConstructor()->getMock();
		$backend->expects($this->exactly(1))->method('createAddressBook');
		$backend->expects($this->at(0))->method('getAddressBooksByUri')->willReturn(null);
		$backend->expects($this->at(1))->method('getAddressBooksByUri')->willReturn([]);

		$ss = new SyncService($backend);
		$book = $ss->ensureSystemAddressBookExists('principals/users/adam', 'contacts', []);
	}

	/**
	 * @param int $createCount
	 * @param int $updateCount
	 * @param int $deleteCount
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getBackendMock($createCount, $updateCount, $deleteCount) {
		$backend = $this->getMockBuilder('OCA\DAV\CardDAV\CardDAVBackend')->disableOriginalConstructor()->getMock();
		$backend->expects($this->exactly($createCount))->method('createCard');
		$backend->expects($this->exactly($updateCount))->method('updateCard');
		$backend->expects($this->exactly($deleteCount))->method('deleteCard');
		return $backend;
	}

	/**
	 * @param $backend
	 * @param $response
	 * @return SyncService|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getSyncServiceMock($backend, $response) {
		$userManager = $this->getMockBuilder('OCP\IUserManager')->disableOriginalConstructor()->getMock();
		/** @var SyncService | \PHPUnit_Framework_MockObject_MockObject $ss */
		$ss = $this->getMock('OCA\DAV\CardDAV\SyncService', ['ensureSystemAddressBookExists', 'requestSyncReport', 'download'], [$backend, $userManager]);
		$ss->method('requestSyncReport')->withAnyParameters()->willReturn(['response' => $response, 'token' => 'sync-token-1']);
		$ss->method('ensureSystemAddressBookExists')->willReturn(['id' => 1]);
		$ss->method('download')->willReturn([
			'body' => '',
			'statusCode' => 200,
			'headers' => []
		]);
		return $ss;
	}

}
