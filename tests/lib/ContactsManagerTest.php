<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test;

use OC\ContactsManager;
use OCP\Constants;
use OCP\IAddressBook;

class ContactsManagerTest extends \Test\TestCase {
	/** @var \OC\ContactsManager */
	private $cm;

	protected function setUp(): void {
		parent::setUp();
		$this->cm = new ContactsManager();
	}

	public static function searchProvider(): array {
		$search1 = [
			0 => [
				'N' => [0 => '', 1 => 'Jan', 2 => 'Jansen', 3 => '', 4 => '',],
				'UID' => '04ada7f5-01f9-4309-9c82-6b555b2170ed',
				'FN' => 'Jan Jansen',
				'id' => '1',
				'addressbook-key' => 'simple:1',
			],
			0 => [
				'N' => [0 => '', 1 => 'Tom', 2 => 'Peeters', 3 => '', 4 => '',],
				'UID' => '04ada7f5-01f9-4309-9c82-2345-2345--6b555b2170ed',
				'FN' => 'Tom Peeters',
				'id' => '2',
				'addressbook-key' => 'simple:1',
			],
		];

		$search2 = [
			0 => [
				'N' => [0 => '', 1 => 'fg', 2 => '', 3 => '', 4 => '',],
				'UID' => '04ada234h5jh357f5-01f9-4309-9c82-6b555b2170ed',
				'FN' => 'Jan Rompuy',
				'id' => '1',
				'addressbook-key' => 'simple:2',
			],
			0 => [
				'N' => [0 => '', 1 => 'fg', 2 => '', 3 => '', 4 => '',],
				'UID' => '04ada7f5-01f9-4309-345kj345j9c82-2345-2345--6b555b2170ed',
				'FN' => 'Tim Peeters',
				'id' => '2',
				'addressbook-key' => 'simple:2',
			],
		];

		$expectedResult = array_merge($search1, $search2);
		return [
			[
				$search1,
				$search2,
				$expectedResult
			]
		];
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearch($search1, $search2, $expectedResult): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBook $addressbook */
		$addressbook1 = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook1->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$addressbook1->expects($this->once())
			->method('search')
			->willReturn($search1);

		$addressbook1->expects($this->any())
			->method('getKey')
			->willReturn('simple:1');

		$addressbook2 = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook2->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$addressbook2->expects($this->once())
			->method('search')
			->willReturn($search2);

		$addressbook2->expects($this->any())
			->method('getKey')
			->willReturn('simple:2');


		$this->cm->registerAddressBook($addressbook1);
		$this->cm->registerAddressBook($addressbook2);
		$result = $this->cm->search('');
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearchDisabledAb($search1): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook1 = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook1->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$addressbook1->expects($this->once())
			->method('search')
			->willReturn($search1);

		$addressbook1->expects($this->any())
			->method('getKey')
			->willReturn('simple:1');

		$addressbook2 = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook2->expects($this->once())
			->method('isEnabled')
			->willReturn(false);

		$addressbook2->expects($this->never())
			->method('search');

		$this->cm->registerAddressBook($addressbook1);
		$this->cm->registerAddressBook($addressbook2);
		$result = $this->cm->search('');
		$this->assertEquals($search1, $result);
	}


	public function testDeleteHavePermission(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_ALL);

		$addressbook->expects($this->once())
			->method('delete')
			->willReturn('returnMe');

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->delete(1, $addressbook->getKey());
		$this->assertEquals($result, 'returnMe');
	}

	public function testDeleteNoPermission(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$addressbook->expects($this->never())
			->method('delete');

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->delete(1, $addressbook->getKey());
		$this->assertEquals($result, null);
	}

	public function testDeleteNoAddressbook(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->never())
			->method('delete');

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->delete(1, 'noaddressbook');
		$this->assertEquals($result, null);
	}

	public function testCreateOrUpdateHavePermission(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_ALL);

		$addressbook->expects($this->once())
			->method('createOrUpdate')
			->willReturn('returnMe');

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->createOrUpdate([], $addressbook->getKey());
		$this->assertEquals($result, 'returnMe');
	}

	public function testCreateOrUpdateNoPermission(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$addressbook->expects($this->never())
			->method('createOrUpdate');

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->createOrUpdate([], $addressbook->getKey());
		$this->assertEquals($result, null);
	}

	public function testCreateOrUpdateNOAdressbook(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->never())
			->method('createOrUpdate');

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->createOrUpdate([], 'noaddressbook');
		$this->assertEquals($result, null);
	}

	public function testIsEnabledIfNot(): void {
		$result = $this->cm->isEnabled();
		$this->assertFalse($result);
	}

	public function testIsEnabledIfSo(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('addressbookKey');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->isEnabled();
		$this->assertTrue($result);
	}

	public function testAddressBookEnumeration(): void {
		// create mock for the addressbook
		/** @var \PHPUnit\Framework\MockObject\MockObject|IAddressBookEnabled $addressbook */
		$addressbook = $this->getMockBuilder('\OCP\IAddressBookEnabled')
			->disableOriginalConstructor()
			->getMock();

		// setup return for method calls
		$addressbook->expects($this->any())
			->method('getKey')
			->willReturn('SIMPLE_ADDRESS_BOOK');
		$addressbook->expects($this->any())
			->method('getDisplayName')
			->willReturn('A very simple Addressbook');

		// register the address book
		$this->cm->registerAddressBook($addressbook);
		$all_books = $this->cm->getUserAddressBooks();

		$this->assertEquals(1, count($all_books));
		$this->assertEquals($addressbook, $all_books['SIMPLE_ADDRESS_BOOK']);
	}
}
