<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2012 Thomas Müller thomas.mueller@tmit.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Test\PublicNamespace;

class ContactsTest extends \Test\TestCase {
	protected function setUp() {
		parent::setUp();
		\OCP\Contacts::clear();
	}

	public function testDisabledIfEmpty() {
		// pretty simple
		$this->assertFalse(\OCP\Contacts::isEnabled());
	}

	public function testEnabledAfterRegister() {
		// create mock for the addressbook
		$stub = $this->getMockForAbstractClass("OCP\IAddressBook", array('getKey'));

		// we expect getKey to be called twice:
		// first time on register
		// second time on un-register
		$stub->expects($this->exactly(2))
			->method('getKey');

		// not enabled before register
		$this->assertFalse(\OCP\Contacts::isEnabled());

		// register the address book
		\OCP\Contacts::registerAddressBook($stub);

		// contacts api shall be enabled
		$this->assertTrue(\OCP\Contacts::isEnabled());

		// unregister the address book
		\OCP\Contacts::unregisterAddressBook($stub);

		// not enabled after register
		$this->assertFalse(\OCP\Contacts::isEnabled());
	}

	public function testAddressBookEnumeration() {
		// create mock for the addressbook
		$stub = $this->getMockForAbstractClass("OCP\IAddressBook", array('getKey', 'getDisplayName'));

		// setup return for method calls
		$stub->expects($this->any())
			->method('getKey')
			->will($this->returnValue('SIMPLE_ADDRESS_BOOK'));
		$stub->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue('A very simple Addressbook'));

		// register the address book
		\OCP\Contacts::registerAddressBook($stub);
		$all_books = \OCP\Contacts::getAddressBooks();

		$this->assertEquals(1, count($all_books));
		$this->assertEquals('A very simple Addressbook', $all_books['SIMPLE_ADDRESS_BOOK']);
	}

	public function testSearchInAddressBook() {
		// create mock for the addressbook
		$stub1 = $this->getMockForAbstractClass("OCP\IAddressBook", array('getKey', 'getDisplayName', 'search'));
		$stub2 = $this->getMockForAbstractClass("OCP\IAddressBook", array('getKey', 'getDisplayName', 'search'));

		$searchResult1 = array(
			array('id' => 0, 'FN' => 'Frank Karlitschek', 'EMAIL' => 'a@b.c', 'GEO' => '37.386013;-122.082932'),
			array('id' => 5, 'FN' => 'Klaas Freitag', 'EMAIL' => array('d@e.f', 'g@h.i')),
		);
		$searchResult2 = array(
			array('id' => 0, 'FN' => 'Thomas Müller', 'EMAIL' => 'a@b.c'),
			array('id' => 5, 'FN' => 'Thomas Tanghus', 'EMAIL' => array('d@e.f', 'g@h.i')),
		);

		// setup return for method calls for $stub1
		$stub1->expects($this->any())->method('getKey')->will($this->returnValue('SIMPLE_ADDRESS_BOOK1'));
		$stub1->expects($this->any())->method('getDisplayName')->will($this->returnValue('Address book ownCloud Inc'));
		$stub1->expects($this->any())->method('search')->will($this->returnValue($searchResult1));

		// setup return for method calls for $stub2
		$stub2->expects($this->any())->method('getKey')->will($this->returnValue('SIMPLE_ADDRESS_BOOK2'));
		$stub2->expects($this->any())->method('getDisplayName')->will($this->returnValue('Address book ownCloud Community'));
		$stub2->expects($this->any())->method('search')->will($this->returnValue($searchResult2));

		// register the address books
		\OCP\Contacts::registerAddressBook($stub1);
		\OCP\Contacts::registerAddressBook($stub2);
		$all_books = \OCP\Contacts::getAddressBooks();

		// assert the count - doesn't hurt
		$this->assertEquals(2, count($all_books));

		// perform the search
		$result = \OCP\Contacts::search('x', array());

		// we expect 4 hits
		$this->assertEquals(4, count($result));

	}
}
