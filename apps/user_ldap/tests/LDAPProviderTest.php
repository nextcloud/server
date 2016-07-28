<?php
/**
 *
 * @copyright Copyright (c) 2016, Roger Szabo (roger.szabo@web.de)
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

use OCP\IServerContainer;
use OCA\User_LDAP\IUserLDAP;

/**
 * Class LDAPProviderTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class LDAPProviderTest extends \Test\TestCase {

	protected function setUp() {
		parent::setUp();
	}
	
	private function getServerMock(IUserLDAP $backend) {
		$server = $this->getMockBuilder('OC\Server')
			 ->setMethods(['getUserManager', 'getBackends'])
			 ->setConstructorArgs(['', new \OC\Config(\OC::$configDir)])
			 ->getMock();
		$server->expects($this->at(1))
            ->method('getBackends')
            ->willReturn([$backend]);
		$server->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();
			
		return $server;
	}
	
	private function getLDAPProvider(IServerContainer $serverContainer) {
		$factory = new \OCA\User_LDAP\LDAPProviderFactory($serverContainer);
		return $factory->getLDAPProvider();
	}
	
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User id not found in LDAP
	 */
	public function testGetUserDNUserIDNotFound() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())->method('userExists')->willReturn(false);
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->getUserDN('nonexisting_user');
	}
	
	public function testGetUserDN() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists', 'getLDAPAccess', 'username2dn'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->at(0))
            ->method('userExists')
            ->willReturn(true);
		$backend->expects($this->at(2))
            ->method('username2dn')
            ->willReturn('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org');
		$backend->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertEquals('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org', 
			$ldapProvider->getUserDN('existing_user'));
	}

	public function testGetUserName() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['dn2UserName'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())
            ->method('dn2UserName')
            ->willReturn('existing_user');
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertEquals('existing_user', 
			$ldapProvider->getUserName('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org'));
	}
	
	public function testDNasBaseParameter() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods([])
			 ->disableOriginalConstructor()
			 ->getMock();
		
		$server = $this->getServerMock($backend);
		
		$helper = new \OCA\User_LDAP\Helper();
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertEquals(
			$helper->DNasBaseParameter('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org'), 
			$ldapProvider->DNasBaseParameter('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org'));
	}

	public function testSanitizeDN() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods([])
			 ->disableOriginalConstructor()
			 ->getMock();
		
		$server = $this->getServerMock($backend);
		
		$helper = new \OCA\User_LDAP\Helper();
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertEquals(
			$helper->sanitizeDN('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org'), 
			$ldapProvider->sanitizeDN('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org'));
	}
	
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User id not found in LDAP
	 */
	public function testGetLDAPConnectionUserIDNotFound() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())->method('userExists')->willReturn(false);
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->getLDAPConnection('nonexisting_user');
	}
	
	public function testGetLDAPConnection() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists', 'getNewLDAPConnection'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())
            ->method('userExists')
            ->willReturn(true);
		$backend->expects($this->any())
            ->method('getNewLDAPConnection')
            ->willReturn(true);
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertTrue($ldapProvider->getLDAPConnection('existing_user'));
	}
	
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User id not found in LDAP
	 */
	public function testGetLDAPBaseUsersUserIDNotFound() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())->method('userExists')->willReturn(false);
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->getLDAPBaseUsers('nonexisting_user');
	}
	
	public function testGetLDAPBaseUsers() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists', 'getLDAPAccess', 'getConnection', 'getConfiguration'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->at(0))
            ->method('userExists')
            ->willReturn(true);
		$backend->expects($this->at(3))
            ->method('getConfiguration')
            ->willReturn(array('ldap_base_users'=>'ou=users,dc=example,dc=org'));
		$backend->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertEquals('ou=users,dc=example,dc=org', $ldapProvider->getLDAPBaseUsers('existing_user'));
	}
	
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User id not found in LDAP
	 */
	public function testGetLDAPBaseGroupsUserIDNotFound() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())->method('userExists')->willReturn(false);
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->getLDAPBaseGroups('nonexisting_user');
	}
	
	public function testGetLDAPBaseGroups() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists', 'getLDAPAccess', 'getConnection', 'getConfiguration'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->at(0))
            ->method('userExists')
            ->willReturn(true);
		$backend->expects($this->at(3))
            ->method('getConfiguration')
            ->willReturn(array('ldap_base_groups'=>'ou=groups,dc=example,dc=org'));
		$backend->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertEquals('ou=groups,dc=example,dc=org', $ldapProvider->getLDAPBaseGroups('existing_user'));
	}
	
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User id not found in LDAP
	 */
	public function testClearCacheUserIDNotFound() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())->method('userExists')->willReturn(false);
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->clearCache('nonexisting_user');
	}
	
	public function testClearCache() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['userExists', 'getLDAPAccess', 'getConnection', 'clearCache'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->at(0))
            ->method('userExists')
            ->willReturn(true);
		$backend->expects($this->at(3))
            ->method('clearCache')
            ->willReturn(true);
		$backend->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->clearCache('existing_user');
		$this->assertTrue(TRUE);
	}
	
	public function testDnExists() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods(['dn2UserName'])
			 ->disableOriginalConstructor()
			 ->getMock();
		$backend->expects($this->any())
            ->method('dn2UserName')
            ->willReturn('existing_user');
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$this->assertTrue($ldapProvider->dnExists('cn=existing_user,ou=Are Sufficient To,ou=Test,dc=example,dc=org'));
	}
	
	public function testFlagRecord() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods([])
			 ->disableOriginalConstructor()
			 ->getMock();
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->flagRecord('existing_user');
		$this->assertTrue(TRUE);
	}
	
	public function testUnflagRecord() {
		$backend = $this->getMockBuilder('OCA\User_LDAP\User_LDAP')
			 ->setMethods([])
			 ->disableOriginalConstructor()
			 ->getMock();
		
		$server = $this->getServerMock($backend);
			
		$ldapProvider = $this->getLDAPProvider($server);
		$ldapProvider->unflagRecord('existing_user');
		$this->assertTrue(TRUE);
	}
}
