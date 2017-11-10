<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Frédéric Fortier <frederic.fortier@oronospolytechnique.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Xuanwo <xuanwo@yunify.com>
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

use OCA\User_LDAP\GroupPluginManager;
use OCP\GroupInterface;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Group_LDAP as GroupLDAP;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\User\Manager;

/**
 * Class GroupLDAPTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class Group_LDAPTest extends \Test\TestCase {
	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|Access
	 */
	private function getAccessMock() {
		static $conMethods;
		static $accMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\User_LDAP\Connection');
			$accMethods = get_class_methods('\OCA\User_LDAP\Access');
		}
		$lw  = $this->createMock(ILDAPWrapper::class);
		$connector = $this->getMockBuilder('\OCA\User_LDAP\Connection')
			->setMethods($conMethods)
			->setConstructorArgs([$lw, null, null])
			->getMock();

		$access = $this->createMock(Access::class);

		$access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($connector));

		return $access;
	}

	private function getPluginManagerMock() {
		return $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')->getMock();
	}
	
	/**
	 * @param Access|\PHPUnit_Framework_MockObject_MockObject $access
	 */
	private function enableGroups($access) {
		$access->connection = $this->createMock(Connection::class);

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'ldapDynamicGroupMemberURL') {
					return '';
				}
				return 1;
			}));
	}

	public function testCountEmptySearchString() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnValue(array('u11', 'u22', 'u33', 'u34')));

		// for primary groups
		$access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(2));

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$users = $groupBackend->countUsersInGroup('group');

		$this->assertSame(6, $users);
	}

	public function testCountWithSearchString() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$access->expects($this->any())
			->method('fetchListOfUsers')
			->will($this->returnValue(array()));

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
			->will($this->returnCallback(function() {
				return 'foobar' . \OCP\Util::generateRandomBytes(7);
			}));

		$groupBackend = new GroupLDAP($access,$pluginManager);
		$users = $groupBackend->countUsersInGroup('group', '3');

		$this->assertSame(2, $users);
	}

	public function testCountUsersWithPlugin() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions','countUsersInGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::COUNT_USERS)
			->willReturn(true);

		$pluginManager->expects($this->once())
			->method('countUsersInGroup')
			->with('gid', 'search')
			->willReturn(42);

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$this->assertEquals($ldap->countUsersInGroup('gid', 'search'),42);
	}	

	public function testGidNumber2NameSuccess() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$access->expects($this->once())
			->method('dn2groupname')
			->with('cn=foo,dc=barfoo,dc=bar')
			->will($this->returnValue('MyGroup'));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->gidNumber2Name('3117', $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testGidNumberID2NameNoGroup() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue(array()));

		$access->expects($this->never())
			->method('dn2groupname');

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->gidNumber2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGidNumberID2NameNoName() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$access->expects($this->once())
			->method('dn2groupname')
			->will($this->returnValue(false));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->gidNumber2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGetEntryGidNumberValue() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'gidNumber';

		$access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(array('3117')));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$gid = $groupBackend->getGroupGidNumber($dn);

		$this->assertSame('3117', $gid);
	}

	public function testGetEntryGidNumberNoValue() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'gidNumber';

		$access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(false));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$gid = $groupBackend->getGroupGidNumber($dn);

		$this->assertSame(false, $gid);
	}

	public function testPrimaryGroupID2NameSuccess() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$access->expects($this->once())
			->method('dn2groupname')
			->with('cn=foo,dc=barfoo,dc=bar')
			->will($this->returnValue('MyGroup'));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testPrimaryGroupID2NameNoSID() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue(false));

		$access->expects($this->never())
			->method('searchGroups');

		$access->expects($this->never())
			->method('dn2groupname');

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoGroup() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue(array()));

		$access->expects($this->never())
			->method('dn2groupname');

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoName() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$access->expects($this->once())
			->method('dn2groupname')
			->will($this->returnValue(false));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGetEntryGroupIDValue() {
		//tests getEntryGroupID via getGroupPrimaryGroupID
		//which is basically identical to getUserPrimaryGroupIDs
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(array('3117')));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$gid = $groupBackend->getGroupPrimaryGroupID($dn);

		$this->assertSame('3117', $gid);
	}

	public function testGetEntryGroupIDNoValue() {
		//tests getEntryGroupID via getGroupPrimaryGroupID
		//which is basically identical to getUserPrimaryGroupIDs
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(false));

		$groupBackend = new GroupLDAP($access, $pluginManager);

		$gid = $groupBackend->getGroupPrimaryGroupID($dn);

		$this->assertSame(false, $gid);
	}

	/**
	 * tests whether Group Backend behaves correctly when cache with uid and gid
	 * is hit
	 */
	public function testInGroupHitsUidGidCache() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$uid = 'someUser';
		$gid = 'someGroup';
		$cacheKey = 'inGroup'.$uid.':'.$gid;

		$access->connection->expects($this->once())
			->method('getFromCache')
			->with($cacheKey)
			->will($this->returnValue(true));

		$access->expects($this->never())
			->method('username2dn');

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$groupBackend->inGroup($uid, $gid);
	}

	public function testGetGroupsWithOffset() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$access->expects($this->once())
			->method('nextcloudGroupNames')
			->will($this->returnValue(array('group1', 'group2')));

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$groups = $groupBackend->getGroups('', 2, 2);

		$this->assertSame(2, count($groups));
	}

	/**
	 * tests that a user listing is complete, if all it's members have the group
	 * as their primary.
	 */
	public function testUsersInGroupPrimaryMembersOnly() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$access->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));
		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				if($attr === 'primaryGroupToken') {
					return array(1337);
				} else if($attr === 'gidNumber') {
					return [4211];
				}
				return array();
			}));
		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));
		$access->expects($this->exactly(2))
			->method('nextcloudUserNames')
			->willReturnOnConsecutiveCalls(['lisa', 'bart', 'kira', 'brad'], ['walle', 'dino', 'xenia']);
		$access->userManager = $this->createMock(Manager::class);

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$users = $groupBackend->usersInGroup('foobar');

		$this->assertSame(7, count($users));
	}

	/**
	 * tests that a user listing is complete, if all it's members have the group
	 * as their primary.
	 */
	public function testUsersInGroupPrimaryAndUnixMembers() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$access->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));
		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				if($attr === 'primaryGroupToken') {
					return array(1337);
				}
				return array();
			}));
		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));
		$access->expects($this->once())
			->method('nextcloudUserNames')
			->will($this->returnValue(array('lisa', 'bart', 'kira', 'brad')));
		$access->userManager = $this->createMock(Manager::class);

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$users = $groupBackend->usersInGroup('foobar');

		$this->assertSame(4, count($users));
	}

	/**
	 * tests that a user counting is complete, if all it's members have the group
	 * as their primary.
	 */
	public function testCountUsersInGroupPrimaryMembersOnly() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$access->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				if($attr === 'primaryGroupToken') {
					return array(1337);
				}
				return array();
			}));

		$access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(4));

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$users = $groupBackend->countUsersInGroup('foobar');

		$this->assertSame(4, $users);
	}

	public function testGetUserGroupsMemberOf() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$this->enableGroups($access);

		$dn = 'cn=userX,dc=foobar';

		$access->connection->hasPrimaryGroups = false;
		$access->connection->hasGidNumber = false;

		$access->expects($this->any())
			->method('username2dn')
			->will($this->returnValue($dn));

		$access->expects($this->exactly(3))
			->method('readAttribute')
			->will($this->onConsecutiveCalls(['cn=groupA,dc=foobar', 'cn=groupB,dc=foobar'], [], []));

		$access->expects($this->exactly(2))
			->method('dn2groupname')
			->will($this->returnArgument(0));

		$access->expects($this->exactly(3))
			->method('groupsMatchFilter')
			->will($this->returnArgument(0));

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$groups = $groupBackend->getUserGroups('userX');

		$this->assertSame(2, count($groups));
	}

	public function testGetUserGroupsMemberOfDisabled() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$access->connection = $this->createMock(Connection::class);
		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'useMemberOfToDetectMembership') {
					return 0;
				} else if($name === 'ldapDynamicGroupMemberURL') {
					return '';
				}
				return 1;
			}));

		$dn = 'cn=userX,dc=foobar';

		$access->connection->hasPrimaryGroups = false;
		$access->connection->hasGidNumber = false;

		$access->expects($this->once())
			->method('username2dn')
			->will($this->returnValue($dn));

		$access->expects($this->never())
			->method('readAttribute')
			->with($dn, 'memberOf');

		$access->expects($this->once())
			->method('nextcloudGroupNames')
			->will($this->returnValue([]));

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$groupBackend->getUserGroups('userX');
	}

	public function testGetGroupsByMember() {
		$access = $this->getAccessMock();
		$pluginManager = $this->getPluginManagerMock();

		$access->connection = $this->createMock(Connection::class);
		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'useMemberOfToDetectMembership') {
					return 0;
				} else if($name === 'ldapDynamicGroupMemberURL') {
					return '';
				} else if($name === 'ldapNestedGroups') {
					return false;
				}
				return 1;
			}));

		$dn = 'cn=userX,dc=foobar';

		$access->connection->hasPrimaryGroups = false;
		$access->connection->hasGidNumber = false;

		$access->expects($this->exactly(2))
			->method('username2dn')
			->will($this->returnValue($dn));

		$access->expects($this->never())
			->method('readAttribute')
			->with($dn, 'memberOf');

		$group1 = [
			'cn' => 'group1',
			'dn' => ['cn=group1,ou=groups,dc=domain,dc=com'],
		];
		$group2 = [
			'cn' => 'group2',
			'dn' => ['cn=group2,ou=groups,dc=domain,dc=com'],
		];

		$access->expects($this->once())
			->method('nextcloudGroupNames')
			->with([$group1, $group2])
			->will($this->returnValue(['group1', 'group2']));

		$access->expects($this->once())
			->method('fetchListOfGroups')
			->will($this->returnValue([$group1, $group2]));

		$groupBackend = new GroupLDAP($access, $pluginManager);
		$groups = $groupBackend->getUserGroups('userX');
		$this->assertEquals(['group1', 'group2'], $groups);

		$groupsAgain = $groupBackend->getUserGroups('userX');
		$this->assertEquals(['group1', 'group2'], $groupsAgain);
	}

	public function testCreateGroupWithPlugin() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions','createGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::CREATE_GROUP)
			->willReturn(true);

		$pluginManager->expects($this->once())
			->method('createGroup')
			->with('gid')
			->willReturn('result');

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$this->assertEquals($ldap->createGroup('gid'),true);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testCreateGroupFailing() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions', 'createGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::CREATE_GROUP)
			->willReturn(false);

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$ldap->createGroup('gid');
	}

	public function testDeleteGroupWithPlugin() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions','deleteGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::DELETE_GROUP)
			->willReturn(true);

		$pluginManager->expects($this->once())
			->method('deleteGroup')
			->with('gid')
			->willReturn('result');

		$mapper = $this->getMockBuilder('\OCA\User_LDAP\Mapping\GroupMapping')
			->setMethods(['unmap'])
			->disableOriginalConstructor()
			->getMock();

		$access = $this->getAccessMock();
		$access->expects($this->any())
			->method('getGroupMapper')
			->will($this->returnValue($mapper));

		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$this->assertEquals($ldap->deleteGroup('gid'),'result');
	}

	/**
	 * @expectedException \Exception
	 */
	public function testDeleteGroupFailing() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions', 'deleteGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::DELETE_GROUP)
			->willReturn(false);

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$ldap->deleteGroup('gid');
	}

	public function testAddToGroupWithPlugin() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions','addToGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::ADD_TO_GROUP)
			->willReturn(true);

		$pluginManager->expects($this->once())
			->method('addToGroup')
			->with('uid', 'gid')
			->willReturn('result');

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$this->assertEquals($ldap->addToGroup('uid', 'gid'),'result');
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAddToGroupFailing() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions', 'addToGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::ADD_TO_GROUP)
			->willReturn(false);

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$ldap->addToGroup('uid', 'gid');
	}

	public function testRemoveFromGroupWithPlugin() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions','removeFromGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::REMOVE_FROM_GROUP)
			->willReturn(true);

		$pluginManager->expects($this->once())
			->method('removeFromGroup')
			->with('uid', 'gid')
			->willReturn('result');

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$this->assertEquals($ldap->removeFromGroup('uid', 'gid'),'result');
	}

	/**
	 * @expectedException \Exception
	 */
	public function testRemoveFromGroupFailing() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions', 'removeFromGroup'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::REMOVE_FROM_GROUP)
			->willReturn(false);

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$ldap->removeFromGroup('uid', 'gid');
	}

	public function testGetGroupDetailsWithPlugin() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions','getGroupDetails'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::GROUP_DETAILS)
			->willReturn(true);

		$pluginManager->expects($this->once())
			->method('getGroupDetails')
			->with('gid')
			->willReturn('result');

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$this->assertEquals($ldap->getGroupDetails('gid'),'result');
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetGroupDetailsFailing() {
		/** @var GroupPluginManager|\PHPUnit_Framework_MockObject_MockObject $pluginManager */
		$pluginManager = $this->getMockBuilder('\OCA\User_LDAP\GroupPluginManager')
			->setMethods(['implementsActions', 'getGroupDetails'])
			->getMock();

		$pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::GROUP_DETAILS)
			->willReturn(false);

		$access = $this->getAccessMock();
		$access->connection = $this->createMock(Connection::class);

		$ldap = new GroupLDAP($access, $pluginManager);

		$ldap->getGroupDetails('gid');
	}	

}
