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

OC::autoload('OCP\Contacts');

class Test_Contacts extends PHPUnit_Framework_TestCase
{

	public function setUp() {

		OCP\Contacts::clear();
	}

	public function tearDown() {
	}

	public function testDisabledIfEmpty() {
		// pretty simple
		$this->assertFalse(OCP\Contacts::isEnabled());
	}

	public function testEnabledAfterRegister() {
		// create mock for the addressbook
		$stub = $this->getMock("SimpleAddressBook", array('getKey'));

		// we expect getKey to be called once
		$stub->expects($this->once())
			->method('getKey');

		// not enabled before register
		$this->assertFalse(OCP\Contacts::isEnabled());

		// register the address book
		OCP\Contacts::registerAddressBook($stub);

		// contacts api shall be enabled
		$this->assertTrue(OCP\Contacts::isEnabled());
	}

	//
	// TODO: test unregister
	//

	public function testAddressBookEnumeration() {
		// create mock for the addressbook
		$stub = $this->getMock("SimpleAddressBook", array('getKey', 'getDisplayName'));

		// setup return for method calls
		$stub->expects($this->any())
			->method('getKey')
			->will($this->returnValue('SIMPLE_ADDRESS_BOOK'));
		$stub->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue('A very simple Addressbook'));

		// register the address book
		OCP\Contacts::registerAddressBook($stub);
		$all_books = OCP\Contacts::getAddressBooks();

		$this->assertEquals(1, count($all_books));
	}
}
