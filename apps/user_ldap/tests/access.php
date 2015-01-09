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

class Test_Access extends \Test\TestCase {
	private function getConnecterAndLdapMock() {
		static $conMethods;
		static $accMethods;
		static $umMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\user_ldap\lib\Connection');
			$accMethods = get_class_methods('\OCA\user_ldap\lib\Access');
			$umMethods  = get_class_methods('\OCA\user_ldap\lib\user\Manager');
		}
		$lw  = $this->getMock('\OCA\user_ldap\lib\ILDAPWrapper');
		$connector = $this->getMock('\OCA\user_ldap\lib\Connection',
									$conMethods,
									array($lw, null, null));
		$um = $this->getMock('\OCA\user_ldap\lib\user\Manager',
			$umMethods, array(
				$this->getMock('\OCP\IConfig'),
				$this->getMock('\OCA\user_ldap\lib\FilesystemHelper'),
				$this->getMock('\OCA\user_ldap\lib\LogWrapper'),
				$this->getMock('\OCP\IAvatarManager'),
				$this->getMock('\OCP\Image'),
				$this->getMock('\OCP\IDBConnection')));

		return array($lw, $connector, $um);
	}

	public function testEscapeFilterPartValidChars() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$input = 'okay';
		$this->assertTrue($input === $access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$input = '*';
		$expected = '\\\\*';
		$this->assertTrue($expected === $access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard2() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$input = 'foo*bar';
		$expected = 'foo\\\\*bar';
		$this->assertTrue($expected === $access->escapeFilterPart($input));
	}

	/** @dataProvider convertSID2StrSuccessData */
	public function testConvertSID2StrSuccess(array $sidArray, $sidExpected) {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$sidBinary = implode('', $sidArray);
		$this->assertSame($sidExpected, $access->convertSID2Str($sidBinary));
	}

	public function convertSID2StrSuccessData() {
		return array(
			array(
				array(
					"\x01",
					"\x04",
					"\x00\x00\x00\x00\x00\x05",
					"\x15\x00\x00\x00",
					"\xa6\x81\xe5\x0e",
					"\x4d\x6c\x6c\x2b",
					"\xca\x32\x05\x5f",
				),
				'S-1-5-21-249921958-728525901-1594176202',
			),
			array(
				array(
					"\x01",
					"\x02",
					"\xFF\xFF\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
				),
				'S-1-281474976710655-4294967295-4294967295',
			),
		);
	}

	public function testConvertSID2StrInputError() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$sidIllegal = 'foobar';
		$sidExpected = '';

		$this->assertSame($sidExpected, $access->convertSID2Str($sidIllegal));
	}

	public function testGetDomainDNFromDNSuccess() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$inputDN = 'uid=zaphod,cn=foobar,dc=my,dc=server,dc=com';
		$domainDN = 'dc=my,dc=server,dc=com';

		$lw->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->will($this->returnValue(explode(',', $inputDN)));

		$this->assertSame($domainDN, $access->getDomainDNFromDN($inputDN));
	}

	public function testGetDomainDNFromDNError() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$inputDN = 'foobar';
		$expected = '';

		$lw->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->will($this->returnValue(false));

		$this->assertSame($expected, $access->getDomainDNFromDN($inputDN));
	}

	private function getResemblesDNInputData() {
		return  $cases = array(
			array(
				'input' => 'foo=bar,bar=foo,dc=foobar',
				'interResult' => array(
					'count' => 3,
					0 => 'foo=bar',
					1 => 'bar=foo',
					2 => 'dc=foobar'
				),
				'expectedResult' => true
			),
			array(
				'input' => 'foobarbarfoodcfoobar',
				'interResult' => false,
				'expectedResult' => false
			)
		);
	}

	public function testStringResemblesDN() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$access = new Access($con, $lw, $um);

		$cases = $this->getResemblesDNInputData();

		$lw->expects($this->exactly(2))
			->method('explodeDN')
			->will($this->returnCallback(function ($dn) use ($cases) {
				foreach($cases as $case) {
					if($dn === $case['input']) {
						return $case['interResult'];
					}
				}
			}));

		foreach($cases as $case) {
			$this->assertSame($case['expectedResult'], $access->stringResemblesDN($case['input']));
		}
	}

	public function testStringResemblesDNLDAPmod() {
		list($lw, $con, $um) = $this->getConnecterAndLdapMock();
		$lw = new \OCA\user_ldap\lib\LDAP();
		$access = new Access($con, $lw, $um);

		if(!function_exists('ldap_explode_dn')) {
			$this->markTestSkipped('LDAP Module not available');
		}

		$cases = $this->getResemblesDNInputData();

		foreach($cases as $case) {
			$this->assertSame($case['expectedResult'], $access->stringResemblesDN($case['input']));
		}
	}
}
