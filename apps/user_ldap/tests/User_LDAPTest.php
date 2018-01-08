<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Roger Szabo <roger.szabo@web.de>
 *
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

namespace OCA\User_LDAP\Tests;

use OC\User\Session;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LogWrapper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\OfflineUser;
use OC\HintException;
use OCA\User_LDAP\User\User;
use OCA\User_LDAP\User_LDAP as UserLDAP;
use OCA\User_LDAP\User_LDAP;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Image;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;
use OCP\Notification\IManager as INotificationManager;

/**
 * Class Test_User_Ldap_Direct
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class User_LDAPTest extends TestCase {
	protected $backend;
	protected $access;
	/** @var  IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $configMock;
	/** @var  OfflineUser|\PHPUnit_Framework_MockObject_MockObject */
	protected $offlineUser;

	protected function setUp() {
		parent::setUp();

		\OC_User::clearBackends();
		\OC::$server->getGroupManager()->clearBackends();
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|Access
	 */
	private function getAccessMock() {
		$lw  = $this->createMock(ILDAPWrapper::class);
		$connector = $this->getMockBuilder(Connection::class)
			->setMethodsExcept(['getConnection'])
			->setConstructorArgs([$lw, null, null])
			->getMock();

		$this->configMock = $this->createMock(IConfig::class);

		$this->offlineUser = $this->createMock(OfflineUser::class);

		/** @var Manager|\PHPUnit_Framework_MockObject_MockObject $um */
		$um = $this->getMockBuilder(Manager::class)
			->setMethods(['getDeletedUser'])
			->setConstructorArgs([
				$this->configMock,
				$this->createMock(FilesystemHelper::class),
				$this->createMock(LogWrapper::class),
				$this->createMock(IAvatarManager::class),
				$this->createMock(Image::class),
				$this->createMock(IDBConnection::class),
				$this->createMock(IUserManager::class),
				$this->createMock(INotificationManager::class)
			  ])
			->getMock();

		$um->expects($this->any())
			->method('getDeletedUser')
			->will($this->returnValue($this->offlineUser));

		$helper = new Helper(\OC::$server->getConfig());

		$access = $this->getMockBuilder(Access::class)
			->setMethodsExcept(['getConnection'])
			->setConstructorArgs([$connector, $lw, $um, $helper])
			->getMock();

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
	 * @param Access|\PHPUnit_Framework_MockObject_MockObject $access mock
	 * @param bool $noDisplayName
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertEquals('gunslinger', $result);
	}

	public function testCheckPasswordWrongPassword() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordWrongUser() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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

		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPI() {
		$access = $this->getAccessMock();
		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::checkPassword('roland', 'dt19');
		$this->assertEquals('gunslinger', $result);
	}

	public function testCheckPasswordPublicAPIWrongPassword() {
		$access = $this->getAccessMock();
		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::checkPassword('roland', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPIWrongUser() {
		$access = $this->getAccessMock();
		$this->prepareAccessForCheckPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::checkPassword('mallory', 'evil');
		$this->assertFalse($result);
	}

	public function testDeleteUserCancel() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		$result = $backend->deleteUser('notme');
		$this->assertFalse($result);
	}

	public function testDeleteUserSuccess() {
		$uid = 'jeremy';
		$home = '/var/vhome/jdings/';

		$access = $this->getAccessMock();
		$mapping = $this->createMock(UserMapping::class);
		$mapping->expects($this->once())
			->method('unmap')
			->will($this->returnValue(true));
		$access->expects($this->once())
			->method('getUserMapper')
			->will($this->returnValue($mapping));

		$this->configMock->expects($this->any())
			->method('getUserValue')
			->with($uid, 'user_ldap', 'isDeleted')
			->willReturn('1');

		$this->offlineUser->expects($this->once())
			->method('getHomePath')
			->willReturn($home);
		$this->offlineUser->expects($this->once())
			->method('getOCName')
			->willReturn($uid);

		$backend = new UserLDAP($access, $this->configMock, $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn($uid);

		$backend->preDeleteUser($user);
		$result = $backend->deleteUser($uid);
		$this->assertTrue($result);
		$this->assertSame($backend->getHome($uid), $home);
	}

	/**
	 * Prepares the Access mock for getUsers tests
	 * @param Access $access mock
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
			   ->method('nextcloudUserNames')
			   ->will($this->returnArgument(0));
	}

	public function testGetUsersNoParam() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->getUsers();
		$this->assertEquals(3, count($result));
	}

	public function testGetUsersLimitOffset() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersLimitOffset2() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchWithResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->getUsers('yo');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchEmptyResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->getUsers('nix');
		$this->assertEquals(0, count($result));
	}

	public function testGetUsersViaAPINoParam() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers();
		$this->assertEquals(3, count($result));
	}

	public function testGetUsersViaAPILimitOffset() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersViaAPILimitOffset2() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersViaAPISearchWithResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('yo');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersViaAPISearchEmptyResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('nix');
		$this->assertEquals(0, count($result));
	}

	public function testUserExists() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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

	public function testDeleteUserExisting() {
		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		//we do not support deleting existing users at all
		$result = $backend->deleteUser('gunslinger');
		$this->assertFalse($result);
	}

	public function testGetHomeAbsolutePath() {
		$access = $this->getAccessMock();
		$config = $this->createMock(IConfig::class);
		$noti = $this->createMock(INotificationManager::class);
		$backend = new UserLDAP($access, $config, $noti, $this->createMock(Session::class));
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
		$config = $this->createMock(IConfig::class);
		$noti = $this->createMock(INotificationManager::class);
		$backend = new UserLDAP($access, $config, $noti, $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$uid = 'newyorker';

		$access = $this->getAccessMock();
		$backend = new UserLDAP($access, $this->configMock, $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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

		$userMapper = $this->createMock(UserMapping::class);

		$access->expects($this->any())
				->method('getUserMapper')
				->will($this->returnValue($userMapper));

		$this->configMock->expects($this->any())
			->method('getUserValue')
			->will($this->returnValue(true));

		$this->offlineUser->expects($this->never())
			->method('getHomePath');

		$backend->getHome($uid);
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
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

		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->countUsers();
		$this->assertEquals(5, $result);
	}

	public function testCountUsersFailing() {
		$access = $this->getAccessMock();

		$access->expects($this->once())
			   ->method('countUsers')
			   ->will($this->returnValue(false));

		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));

		$result = $backend->countUsers();
		$this->assertFalse($result);
	}

	public function testLoginName2UserNameSuccess() {
		$loginName = 'Alice';
		$username  = 'alice';
		$dn        = 'uid=alice,dc=what,dc=ever';

		$access = $this->getAccessMock();
		$access->expects($this->once())
			->method('fetchUsersByLoginName')
			->with($this->equalTo($loginName))
			->willReturn([['dn' => [$dn]]]);
		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($dn))
			->willReturn(true);
		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($dn))
			->willReturn($username);

		$access->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with($this->equalTo('loginName2UserName-'.$loginName))
			->willReturnOnConsecutiveCalls(null, $username);
		$access->connection->expects($this->once())
			->method('writeToCache')
			->with($this->equalTo('loginName2UserName-'.$loginName), $this->equalTo($username));

		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		$name = $backend->loginName2UserName($loginName);
		$this->assertSame($username, $name);

		// and once again to verify that caching works
		$backend->loginName2UserName($loginName);
	}

	public function testLoginName2UserNameNoUsersOnLDAP() {
		$loginName = 'Loki';

		$access = $this->getAccessMock();
		$access->expects($this->once())
			->method('fetchUsersByLoginName')
			->with($this->equalTo($loginName))
			->willReturn([]);
		$access->expects($this->never())
			->method('stringResemblesDN');
		$access->expects($this->never())
			->method('dn2username');

		$access->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with($this->equalTo('loginName2UserName-'.$loginName))
			->willReturnOnConsecutiveCalls(null, false);
		$access->connection->expects($this->once())
			->method('writeToCache')
			->with($this->equalTo('loginName2UserName-'.$loginName), false);

		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		$name = $backend->loginName2UserName($loginName);
		$this->assertSame(false, $name);

		// and once again to verify that caching works
		$backend->loginName2UserName($loginName);
	}

	public function testLoginName2UserNameOfflineUser() {
		$loginName = 'Alice';
		$username  = 'alice';
		$dn        = 'uid=alice,dc=what,dc=ever';

		$offlineUser = $this->getMockBuilder(OfflineUser::class)
			->disableOriginalConstructor()
			->getMock();

		$access = $this->getAccessMock();
		$access->expects($this->once())
			->method('fetchUsersByLoginName')
			->with($this->equalTo($loginName))
			->willReturn([['dn' => [$dn]]]);
		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($dn))
			->willReturn(true);
		$access->expects($this->once())
			->method('dn2username')
			->willReturn(false);	// this is fake, but allows us to force-enter the OfflineUser path

		$access->connection->expects($this->exactly(2))
			->method('getFromCache')
			->with($this->equalTo('loginName2UserName-'.$loginName))
			->willReturnOnConsecutiveCalls(null, false);
		$access->connection->expects($this->once())
			->method('writeToCache')
			->with($this->equalTo('loginName2UserName-'.$loginName), $this->equalTo(false));

		$access->userManager->expects($this->once())
			->method('getDeletedUser')
			->will($this->returnValue($offlineUser));

		//$config = $this->createMock(IConfig::class);
		$this->configMock->expects($this->once())
			->method('getUserValue')
			->willReturn(1);

		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		$name = $backend->loginName2UserName($loginName);
		$this->assertSame(false, $name);

		// and once again to verify that caching works
		$backend->loginName2UserName($loginName);
	}
	
	/**
	 * Prepares the Access mock for setPassword tests
	 * @param \OCA\User_LDAP\Access|\PHPUnit_Framework_MockObject_MockObject $access mock
	 * @return void
	 */
	private function prepareAccessForSetPassword(&$access, $enablePasswordChange = true) {
		$access->connection->expects($this->any())
			   ->method('__get')
			   ->will($this->returnCallback(function($name) use (&$enablePasswordChange) {
					if($name === 'ldapLoginFilter') {
						return '%uid';
					}
					if($name === 'turnOnPasswordChange') {
						return $enablePasswordChange?1:0;
					}
					return null;
			   }));
			   
		$access->connection->expects($this->any())
			   ->method('getFromCache')
			   ->will($this->returnCallback(function($uid) {
					if($uid === 'userExists'.'roland') {
						return true;
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

		$access->expects($this->any())
			   ->method('dn2username')
			   ->with($this->equalTo('dnOfRoland,dc=test'))
			   ->will($this->returnValue('roland'));

		$access->expects($this->any())
			->method('stringResemblesDN')
			->will($this->returnCallback(function($string) {
				// very simplified
				return strpos($string, ',') !== false;
			}));
			   
		$access->expects($this->any())
			   ->method('setPassword')
			   ->will($this->returnCallback(function($uid, $password) {
					if(strlen($password) <= 5) {
						throw new HintException('Password fails quality checking policy', '', 19);
					}
					return true;
			   }));
	}

	/**
	 * @expectedException \OC\HintException
	 * @expectedExceptionMessage Password fails quality checking policy
	 */
	public function testSetPasswordInvalid() {
		$access = $this->getAccessMock();

		$this->prepareAccessForSetPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$this->assertTrue(\OC_User::setPassword('roland', 'dt'));
	}
	
	public function testSetPasswordValid() {
		$access = $this->getAccessMock();

		$this->prepareAccessForSetPassword($access);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$this->assertTrue(\OC_User::setPassword('roland', 'dt12234$'));
	}

	public function testSetPasswordValidDisabled() {
		$access = $this->getAccessMock();

		$this->prepareAccessForSetPassword($access, false);
		$backend = new UserLDAP($access, $this->createMock(IConfig::class), $this->createMock(INotificationManager::class), $this->createMock(Session::class));
		\OC_User::useBackend($backend);

		$this->assertFalse(\OC_User::setPassword('roland', 'dt12234$'));
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage LDAP setPassword: Could not get user object for uid NotExistingUser. Maybe the LDAP entry has no set display name attribute?
	 */
	public function testSetPasswordWithInvalidUser() {
		$access = $this->createMock(Access::class);
		$access->userManager = $this->createMock(IUserManager::class);
		$access->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);
		$config = $this->createMock(IConfig::class);
		$noti = $this->createMock(INotificationManager::class);
		$userSession = $this->createMock(Session::class);
		$ldap = new User_LDAP(
			$access,
			$config,
			$noti,
			$userSession
		);
		$ldap->setPassword('NotExistingUser', 'Password');
	}

	public function testSetPasswordWithUsernameFalse() {
		$user = $this->createMock(User::class);
		$user
			->expects($this->once())
			->method('getUsername')
			->willReturn(false);
		$access = $this->createMock(Access::class);
		$access->userManager = $this->createMock(IUserManager::class);
		$access->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn($user);
		$config = $this->createMock(IConfig::class);
		$noti = $this->createMock(INotificationManager::class);
		$userSession = $this->createMock(Session::class);
		$ldap = new User_LDAP(
			$access,
			$config,
			$noti,
			$userSession
		);
		$this->assertFalse($ldap->setPassword('NotExistingUser', 'Password'));
	}
}
