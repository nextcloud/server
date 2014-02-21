<?php
/**
* ownCloud
*
* @author Arthur Schiwon
* @copyright 2013 Arthur Schiwon blizzz@owncloud.com
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

use \OCA\user_ldap\lib\Access;
use \OCA\user_ldap\lib\Connection;
use \OCA\user_ldap\lib\ILDAPWrapper;

class Test_Access extends \PHPUnit_Framework_TestCase {
	private function getConnecterAndLdapMock() {
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

		return array($lw, $connector);
	}

	public function testEscapeFilterPartValidChars() {
		list($lw, $con) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw);

		$input = 'okay';
		$this->assertTrue($input === $access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard() {
		list($lw, $con) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw);

		$input = '*';
		$expected = '\\\\*';
		$this->assertTrue($expected === $access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard2() {
		list($lw, $con) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw);

		$input = 'foo*bar';
		$expected = 'foo\\\\*bar';
		$this->assertTrue($expected === $access->escapeFilterPart($input));
	}
}