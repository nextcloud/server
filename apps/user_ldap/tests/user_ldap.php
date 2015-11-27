<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
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

namespace OCA\user_ldap\tests;

use \OCA\user_ldap\USER_LDAP as UserLDAP;
use \OCA\user_ldap\lib\Access;
use \OCA\user_ldap\lib\Connection;
use \OCA\user_ldap\lib\ILDAPWrapper;

class Test_User_Ldap_Direct extends \Test\TestCase {
	protected $backend;
	protected $access;
	protected $configMock;

	protected function setUp() {
		parent::setUp();

		\OC_User::clearBackends();
		\OC_Group::clearBackends();
	}

	private function getAccessMock() {
		static $conMethods;
		static $accMethods;
		static $uMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\user_ldap\lib\Connection');
			$accMethods = get_class_methods('\OCA\user_ldap\lib\Access');
			unset($accMethods[array_search('getConnection', $accMethods)]);
			$uMethods   = get_class_methods('\OCA\user_ldap\lib\user\User');
			unset($uMethods[array_search('getUsername', $uMethods)]);
			unset($uMethods[array_search('getDN', $uMethods)]);
			unset($uMethods[array_search('__construct', $uMethods)]);
		}
		$lw  = $this->getMock('\OCA\user_ldap\lib\ILDAPWrapper');
		$connector = $this->getMock('\OCA\user_ldap\lib\Connection',
									$conMethods,
									array($lw, null, null));

		$this->configMock = $this->getMock('\OCP\IConfig');

		$offlineUser = $this->getMockBuilder('\OCA\user_ldap\lib\user\OfflineUser')
			->disableOriginalConstructor()
			->getMock();

		$um = $this->getMockBuilder('\OCA\user_ldap\lib\user\Manager')
			->setMethods(['getDeletedUser'])
			->setConstructorArgs([
				$this->configMock,
				$this->getMock('\OCA\user_ldap\lib\FilesystemHelper'),
				$this->getMock('\OCA\user_ldap\lib\LogWrapper'),
				$this->getMock('\OCP\IAvatarManager'),
				$this->getMock('\OCP\Image'),
				$this->getMock('\OCP\IDBConnection')
			  ])
			->getMock();

		$um->expects($this->any())
			->method('getDeletedUser')
			->will($this->returnValue($offlineUser));

		$access = $this->getMock('\OCA\user_ldap\lib\Access',
								 $accMethods,
								 array($connector, $lw, $um));

		$um->setLdapAccess($access);

		return $access;
	}

	private function prepareMockForUserExists(&$access) {
		$access->expects($this->any())
			   ->method('username2dn')
			   ->will($this->returnCallback(function($uid) {
					switch ($uid) {
						case 'gunslinger':
							return 'dnOfRoland,dc=test';
							break;
						case 'formerUser':
							return 'dnOfFormerUser,dc=test';
							break;
						case 'newyorker':
							return 'dnOfNewYorker,dc=test';
							break;
						case 'ladyofshadows':
							return 'dnOfLadyOfShadows,dc=test';
							break;
						default:
							return false;
					}
			   }));
	}

	/**
	 * Prepares the Access mock for checkPassword tests
	 * @param \OCA\user_ldap\lib\Access $access mock
	 * @param bool noDisplayName
	 * @return void
	 */
	private function prepareAccessForCheckPassword(&$access, $noDisplayName = false) {
		$access->connection->expects($this->any())
			   ->method('__get')
			   ->will($this->returnCallback(function($name) {
					if($name === 'ldapLoginFilter') {
						return '%uid';
					}
					return null;
			   }));

		$access->expects($this->any())
			   ->method('fetchListOfUsers')
			   ->will($this->returnCallback(function($filter) {
					if($filter === 'roland') {
						return array(array('dn' => ['dnOfRoland,dc=test']));
					}
					return array();
			   }));

		$access->expects($this->any())
			->method('fetchUsersByLoginName')
			->will($this->returnCallback(function($uid) {
				if($uid === 'roland') {
					return array(array('dn' => ['dnOfRoland,dc=test']));
				}
				return array();
			}));

		$retVal = 'gunslinger';
		if($noDisplayName === true) {
			$retVal = false;
		}
		$access->expects($this->any())
			   ->method('dn2username')
			   ->with($this->equalTo('dnOfRoland,dc=test'))
			   ->will($this->returnValue($retVal));

		$access->expects($this->any())
			   ->method('stringResemblesDN')
			   ->with($this->equalTo('dnOfRoland,dc=test'))
			   ->will($this->returnValue(true));

		$access->expects($this->any())
			   ->method('areCredentialsValid')
			   ->will($this->returnCallback(function($dn, $pwd) {
					if($pwd === 'dt19') {
						return true;
					}
					return false;
			   }));
	}

	public function testCheckPasswordUidReturn() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertEquals('gunslinger', $result);
	}

	public function testCheckPasswordWrongPassword() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordWrongUser() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('mallory', 'evil');
		$this->assertFalse($result);
	}

	public function testCheckPasswordNoDisplayName() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access, true);
		$access->expects($this->once())
			->method('username2dn')
			->will($this->returnValue(false));

		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPI() {
		$access = $this->getAccessMock();
		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::checkPassword('roland', 'dt19');
		$this->assertEquals('gunslinger', $result);
	}

	public function testCheckPasswordPublicAPIWrongPassword() {
		$access = $this->getAccessMock();
		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::checkPassword('roland', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPIWrongUser() {
		$access = $this->getAccessMock();
		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::checkPassword('mallory', 'evil');
		$this->assertFalse($result);
	}

	public function testDeleteUserCancel() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$result = $backend->deleteUser('notme');
		$this->assertFalse($result);
	}

	public function testDeleteUserSuccess() {
		$access = $this->getAccessMock();
		$mapping = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
			->disableOriginalConstructor()
			->getMock();
		$mapping->expects($this->once())
			->method('unmap')
			->will($this->returnValue(true));
		$access->expects($this->once())
			->method('getUserMapper')
			->will($this->returnValue($mapping));

		$config = $this->getMock('\OCP\IConfig');
		$config->expects($this->exactly(2))
			->method('getUserValue')
			->will($this->onConsecutiveCalls('1', '/var/vhome/jdings/'));

		$backend = new UserLDAP($access, $config);

		$result = $backend->deleteUser('jeremy');
		$this->assertTrue($result);

		$home = $backend->getHome('jeremy');
		$this->assertSame($home, '/var/vhome/jdings/');
	}

	/**
	 * Prepares the Access mock for getUsers tests
	 * @param \OCA\user_ldap\lib\Access $access mock
	 * @return void
	 */
	private function prepareAccessForGetUsers(&$access) {
		$access->expects($this->once())
			   ->method('escapeFilterPart')
			   ->will($this->returnCallback(function($search) {
				   return $search;
			   }));

		$access->expects($this->any())
			   ->method('getFilterPartForUserSearch')
			   ->will($this->returnCallback(function($search) {
					return $search;
			   }));

		$access->expects($this->any())
			   ->method('combineFilterWithAnd')
			   ->will($this->returnCallback(function($param) {
					return $param[2];
			   }));

		$access->expects($this->any())
			   ->method('fetchListOfUsers')
			   ->will($this->returnCallback(function($search, $a, $l, $o) {
					$users = array('gunslinger', 'newyorker', 'ladyofshadows');
					if(empty($search)) {
						$result = $users;
					} else {
						$result = array();
						foreach($users as $user) {
							if(stripos($user,  $search) !== false) {
								$result[] = $user;
							}
						}
					}
					if(!is_null($l) || !is_null($o)) {
						$result = array_slice($result, $o, $l);
					}
					return $result;
			   }));

		$access->expects($this->any())
			   ->method('ownCloudUserNames')
			   ->will($this->returnArgument(0));
	}

	public function testGetUsersNoParam() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->getUsers();
		$this->assertEquals(3, count($result));
	}

	public function testGetUsersLimitOffset() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersLimitOffset2() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchWithResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->getUsers('yo');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchEmptyResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->getUsers('nix');
		$this->assertEquals(0, count($result));
	}

	public function testGetUsersViaAPINoParam() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers();
		$this->assertEquals(3, count($result));
	}

	public function testGetUsersViaAPILimitOffset() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersViaAPILimitOffset2() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersViaAPISearchWithResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('yo');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersViaAPISearchEmptyResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('nix');
		$this->assertEquals(0, count($result));
	}

	public function testUserExists() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn) {
				if($dn === 'dnOfRoland,dc=test') {
					return array();
				}
				return false;
			}));

		//test for existing user
		$result = $backend->userExists('gunslinger');
		$this->assertTrue($result);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testUserExistsForDeleted() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn) {
				if($dn === 'dnOfRoland,dc=test') {
					return array();
				}
				return false;
			}));

		//test for deleted user
		$result = $backend->userExists('formerUser');
	}

	public function testUserExistsForNeverExisting() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn) {
				if($dn === 'dnOfRoland,dc=test') {
					return array();
				}
				return false;
			}));

		//test for never-existing user
		$result = $backend->userExists('mallory');
		$this->assertFalse($result);
	}

	public function testUserExistsPublicAPI() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);
		\OC_User::useBackend($backend);

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn) {
				if($dn === 'dnOfRoland,dc=test') {
					return array();
				}
				return false;
			}));

		//test for existing user
		$result = \OCP\User::userExists('gunslinger');
		$this->assertTrue($result);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testUserExistsPublicAPIForDeleted() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);
		\OC_User::useBackend($backend);

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn) {
				if($dn === 'dnOfRoland,dc=test') {
					return array();
				}
				return false;
			}));

		//test for deleted user
		$result = \OCP\User::userExists('formerUser');
	}

	public function testUserExistsPublicAPIForNeverExisting() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);
		\OC_User::useBackend($backend);

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn) {
				if($dn === 'dnOfRoland,dc=test') {
					return array();
				}
				return false;
			}));

		//test for never-existing user
		$result = \OCP\User::userExists('mallory');
		$this->assertFalse($result);
	}

	public function testDeleteUser() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		//we do not support deleting users at all
		$result = $backend->deleteUser('gunslinger');
		$this->assertFalse($result);
	}

	public function testGetHomeAbsolutePath() {
		$access = $this->getAccessMock();
		$config = $this->getMock('\OCP\IConfig');
		$backend = new UserLDAP($access, $config);
		$this->prepareMockForUserExists($access);

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			}));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				switch ($dn) {
					case 'dnOfRoland,dc=test':
						if($attr === 'testAttribute') {
							return array('/tmp/rolandshome/');
						}
						return array();
						break;
					default:
						return false;
				}
			}));

		//absolut path
		$result = $backend->getHome('gunslinger');
		$this->assertEquals('/tmp/rolandshome/', $result);
	}

	public function testGetHomeRelative() {
		$access = $this->getAccessMock();
		$config = $this->getMock('\OCP\IConfig');
		$backend = new UserLDAP($access, $config);
		$this->prepareMockForUserExists($access);

		$dataDir = \OC::$server->getConfig()->getSystemValue(
			'datadirectory', \OC::$SERVERROOT.'/data');

		$this->configMock->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue($dataDir));

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			}));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				switch ($dn) {
					case 'dnOfLadyOfShadows,dc=test':
						if($attr === 'testAttribute') {
							return array('susannah/');
						}
						return array();
						break;
					default:
						return false;
				}
			}));

		$result = $backend->getHome('ladyofshadows');
		$this->assertEquals($dataDir.'/susannah/', $result);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHomeNoPath() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			}));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				switch ($dn) {
					default:
						return false;
				}
			}));

		//no path at all – triggers OC default behaviour
		$result = $backend->getHome('newyorker');
		$this->assertFalse($result);
	}

	/**
	 * @expectedException \OC\User\NoUserException
	 */
	public function testGetHomeDeletedUser() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->connection->expects($this->any())
				->method('__get')
				->will($this->returnCallback(function($name) {
					if($name === 'homeFolderNamingRule') {
						return 'attr:testAttribute';
					}
					return null;
				}));

		$access->expects($this->any())
				->method('readAttribute')
				->will($this->returnValue([]));

		$userMapper = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
				->disableOriginalConstructor()
				->getMock();

		$access->expects($this->any())
				->method('getUserMapper')
				->will($this->returnValue($userMapper));

		$this->configMock->expects($this->any())
			->method('getUserValue')
			->will($this->returnValue(true));

		//no path at all – triggers OC default behaviour
		$result = $backend->getHome('newyorker');
		$this->assertFalse($result);
	}

	private function prepareAccessForGetDisplayName(&$access) {
		$access->connection->expects($this->any())
			   ->method('__get')
			   ->will($this->returnCallback(function($name) {
					if($name === 'ldapUserDisplayName') {
						return 'displayname';
					}
					return null;
			   }));

		$access->expects($this->any())
			   ->method('readAttribute')
			   ->will($this->returnCallback(function($dn, $attr) {
					switch ($dn) {
						case 'dnOfRoland,dc=test':
							if($attr === 'displayname') {
								return array('Roland Deschain');
							}
							return array();
							break;

						default:
							return false;
				   }
			   }));

		$userMapper = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
			->disableOriginalConstructor()
			->getMock();

		$access->expects($this->any())
			->method('getUserMapper')
			->will($this->returnValue($userMapper));
	}

	public function testGetDisplayName() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetDisplayName($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->connection->expects($this->any())
			->method('getConnectionResource')
			->will($this->returnCallback(function() {
				return true;
			}));

		//with displayName
		$result = $backend->getDisplayName('gunslinger');
		$this->assertEquals('Roland Deschain', $result);

		//empty displayname retrieved
		$result = $backend->getDisplayName('newyorker');
		$this->assertEquals(null, $result);
	}

	public function testGetDisplayNamePublicAPI() {
		$access = $this->getAccessMock();
		$access->expects($this->any())
			->method('username2dn')
			->will($this->returnCallback(function($uid) {
				switch ($uid) {
					case 'gunslinger':
						return 'dnOfRoland,dc=test';
						break;
					case 'formerUser':
						return 'dnOfFormerUser,dc=test';
						break;
					case 'newyorker':
						return 'dnOfNewYorker,dc=test';
						break;
					case 'ladyofshadows':
						return 'dnOfLadyOfShadows,dc=test';
						break;
					default:
						return false;
				}
			}));
		$this->prepareAccessForGetDisplayName($access);
		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));
		$this->prepareMockForUserExists($access);

		$access->connection->expects($this->any())
			->method('getConnectionResource')
			->will($this->returnCallback(function() {
				return true;
			}));

		\OC_User::useBackend($backend);

		//with displayName
		$result = \OCP\User::getDisplayName('gunslinger');
		$this->assertEquals('Roland Deschain', $result);

		//empty displayname retrieved
		$result = \OCP\User::getDisplayName('newyorker');
		$this->assertEquals('newyorker', $result);
	}

	//no test for getDisplayNames, because it just invokes getUsers and
	//getDisplayName

	public function testCountUsers() {
		$access = $this->getAccessMock();

		$access->expects($this->once())
			   ->method('countUsers')
			   ->will($this->returnValue(5));

		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->countUsers();
		$this->assertEquals(5, $result);
	}

	public function testCountUsersFailing() {
		$access = $this->getAccessMock();

		$access->expects($this->once())
			   ->method('countUsers')
			   ->will($this->returnValue(false));

		$backend = new UserLDAP($access, $this->getMock('\OCP\IConfig'));

		$result = $backend->countUsers();
		$this->assertFalse($result);
	}
}
