<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\LDAP;
use Test\TestCase;

class LDAPTest extends TestCase  {
	/** @var LDAP|\PHPUnit_Framework_MockObject_MockObject */
	private $ldap;

	public function setUp() {
		parent::setUp();
		$this->ldap = $this->getMockBuilder(LDAP::class)
			->setMethods(['invokeLDAPMethod'])
			->getMock();
	}

	public function testModReplace() {
		$link = $this->createMock(LDAP::class);
		$userDN = 'CN=user';
		$password = 'MyPassword';
		$this->ldap
			->expects($this->once())
			->method('invokeLDAPMethod')
			->with('mod_replace', $link, $userDN, array('userPassword' => $password))
			->willReturn(true);

		$this->assertTrue($this->ldap->modReplace($link, $userDN, $password));
	}
}
