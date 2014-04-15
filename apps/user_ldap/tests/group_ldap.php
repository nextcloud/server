<?php
/**
* ownCloud
*
* @author Arthur Schiwon
* @copyright 2014 Arthur Schiwon <blizzz@owncloud.com>
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
*
*/

namespace OCA\user_ldap\tests;

namespace OCA\user_ldap\tests;

use \OCA\user_ldap\GROUP_LDAP as GroupLDAP;
use \OCA\user_ldap\lib\Access;
use \OCA\user_ldap\lib\Connection;
use \OCA\user_ldap\lib\ILDAPWrapper;

class Test_Group_Ldap extends \PHPUnit_Framework_TestCase {
	private function getAccessMock() {
		static $conMethods;
		static $accMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\user_ldap\lib\Connection');
			$accMethods = get_class_methods('\OCA\user_ldap\lib\Access');
		}
		$lw  = $this->getMock('\OCA\user_ldap\lib\ILDAPWrapper');
		$connector = $this->getMock('\OCA\user_ldap\lib\Connection',
									$conMethods,
									array($lw, null, null));
		$access = $this->getMock('\OCA\user_ldap\lib\Access',
								 $accMethods,
								 array($connector, $lw));

		return $access;
	}

	private function enableGroups($access) {
		$access->connection->expects($this->any())
			   ->method('__get')
			   ->will($this->returnCallback(function($name) {
// 					if($name === 'ldapLoginFilter') {
// 						return '%uid';
// 					}
					return 1;
			   }));
	}

	public function testCountEmptySearchString() {
		$access = $this->getAccessMock();

		$this->enableGroups($access);

		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnValue(array('u11', 'u22', 'u33', 'u34')));

		$groupBackend = new GroupLDAP($access);
		$users = $groupBackend->countUsersInGroup('group');

		$this->assertSame(4, $users);
	}

	public function testCountWithSearchString() {
		$access = $this->getAccessMock();

		$this->enableGroups($access);

		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($name) {
				//the search operation will call readAttribute, thus we need
				//to anaylze the "dn". All other times we just need to return
				//something that is neither null or false, but once an array
				//with the users in the group – so we do so all other times for
				//simplicicity.
				if(strpos($name, 'u') === 0) {
					return strpos($name, '3');
				}
				return array('u11', 'u22', 'u33', 'u34');
			}));

		$access->expects($this->any())
			->method('dn2username')
			->will($this->returnValue('foobar'));

		$groupBackend = new GroupLDAP($access);
		$users = $groupBackend->countUsersInGroup('group', '3');

		$this->assertSame(2, $users);
	}

}