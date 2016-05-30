<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Frédéric Fortier <frederic.fortier@oronospolytechnique.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCA\User_LDAP\Group_LDAP as GroupLDAP;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;

/**
 * Class GroupLDAPTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class Group_LDAPTest extends \Test\TestCase {

	protected $ldapWrapperMock;
	protected $connectionMock;
	protected $ldapUserManagerMock;
	protected $accessMock;

	public function setUp() {
		parent::setUp();

		static $conMethods;
		static $accMethods;
		static $umMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\User_LDAP\Connection');
			$accMethods = get_class_methods('\OCA\User_LDAP\Access');
			$umMethods  = get_class_methods('\OCA\User_LDAP\User\Manager');
		}

		$this->ldapWrapperMock = $this->getMock('\OCA\User_LDAP\ILDAPWrapper');

		$this->connectionMock = $this->getMock('OCA\User_LDAP\Connection',
				$conMethods,
				array($this->ldapWrapperMock, null, null));

 		$this->ldapUserManagerMock = $this->getMock('\OCA\User_LDAP\User\Manager',
			$umMethods, array(
				$this->getMock('\OCP\IConfig'),
				$this->getMock('\OCA\User_LDAP\FilesystemHelper'),
				$this->getMock('\OCA\User_LDAP\LogWrapper'),
				$this->getMock('\OCP\IAvatarManager'),
				$this->getMock('\OCP\Image'),
				$this->getMock('\OCP\IDBConnection'),
				$this->getMock('\OCP\IUserManager')));

		$this->accessMock = $this->getMock('\OCA\User_LDAP\Access',
			$accMethods,
			array($this->connectionMock, $this->ldapWrapperMock, $this->ldapUserManagerMock));
	}

	private function enableGroups() {
		$this->accessMock->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'ldapDynamicGroupMemberURL') {
					return '';
				}
				return 1;
			}));
	}

	public function testCountEmptySearchString() {
		$this->enableGroups();

		$this->accessMock->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$this->accessMock->expects($this->any())
			->method('readAttribute')
			->will($this->returnValue(array('u11', 'u22', 'u33', 'u34')));

		// for primary groups
		$this->accessMock->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(2));

		$groupBackend = new GroupLDAP($this->accessMock);
		$users = $groupBackend->countUsersInGroup('group');

		$this->assertSame(6, $users);
	}

	public function testCountWithSearchString() {
		$this->enableGroups();

		$this->accessMock->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$this->accessMock->expects($this->any())
			->method('fetchListOfUsers')
			->will($this->returnValue(array()));

		$this->accessMock->expects($this->any())
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

		$this->accessMock->expects($this->any())
			->method('dn2username')
			->will($this->returnCallback(function() {
				return 'foobar' . \OCP\Util::generateRandomBytes(7);
			}));

		$groupBackend = new GroupLDAP($this->accessMock);
		$users = $groupBackend->countUsersInGroup('group', '3');

		$this->assertSame(2, $users);
	}

	public function testPrimaryGroupID2NameSuccess() {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->accessMock->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$this->accessMock->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$this->accessMock->expects($this->once())
			->method('dn2groupname')
			->with('cn=foo,dc=barfoo,dc=bar')
			->will($this->returnValue('MyGroup'));

		$groupBackend = new GroupLDAP($this->accessMock);
		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testPrimaryGroupID2NameNoSID() {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->accessMock->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue(false));

		$this->accessMock->expects($this->never())
			->method('searchGroups');

		$this->accessMock->expects($this->never())
			->method('dn2groupname');

		$groupBackend = new GroupLDAP($this->accessMock);
		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoGroup() {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->accessMock->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$this->accessMock->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue(array()));

		$this->accessMock->expects($this->never())
			->method('dn2groupname');

		$groupBackend = new GroupLDAP($this->accessMock);
		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoName() {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->accessMock->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$this->accessMock->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$this->accessMock->expects($this->once())
			->method('dn2groupname')
			->will($this->returnValue(false));

		$groupBackend = new GroupLDAP($this->accessMock);
		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	/**
	 * tests getEntryGroupID via getGroupPrimaryGroupID
	 * which is basically identical to getUserPrimaryGroupIDs
	 */
	public function testGetEntryGroupIDValue() {
		$this->enableGroups();

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$this->accessMock->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(array('3117')));

		$groupBackend = new GroupLDAP($this->accessMock);
		$gid = $groupBackend->getGroupPrimaryGroupID($dn);

		$this->assertSame('3117', $gid);
	}

	/**
	 * tests getEntryGroupID via getGroupPrimaryGroupID
	 * which is basically identical to getUserPrimaryGroupIDs
	 */
	public function testGetEntryGroupIDNoValue() {
		$this->enableGroups();

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$this->accessMock->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(false));

		$groupBackend = new GroupLDAP($this->accessMock);
		$gid = $groupBackend->getGroupPrimaryGroupID($dn);

		$this->assertSame(false, $gid);
	}

	/**
	 * tests whether Group Backend behaves correctly when cache with uid and gid
	 * is hit
	 */
	public function testInGroupHitsUidGidCache() {
		$this->enableGroups();

		$uid = 'someUser';
		$gid = 'someGroup';
		$cacheKey = 'inGroup'.$uid.':'.$gid;
		$this->accessMock->connection->expects($this->once())
			->method('getFromCache')
			->with($cacheKey)
			->will($this->returnValue(true));
		$this->accessMock->expects($this->never())
			->method('username2dn');
		$groupBackend = new GroupLDAP($this->accessMock);
		$groupBackend->inGroup($uid, $gid);
	}

	public function testGetGroupsWithOffset() {
		$this->enableGroups();

		$this->accessMock->expects($this->once())
			->method('ownCloudGroupNames')
			->will($this->returnValue(array('group1', 'group2')));

		$groupBackend = new GroupLDAP($this->accessMock);
		$groups = $groupBackend->getGroups('', 2, 2);

		$this->assertSame(2, count($groups));
	}

	/**
	 * tests that a user listing is complete, if all it's members have the group
	 * as their primary.
	 */
	public function  testUsersInGroupPrimaryMembersOnly() {
		$this->enableGroups();

		$this->accessMock->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));

		$this->accessMock->expects($this->any())
			->method('escapeFilterPart')
			->with('', true)
			->will($this->returnValue(''));

		$this->accessMock->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				if($attr === 'primaryGroupToken') {
					return array(1337);
				}
				return array();
			}));

		$this->accessMock->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$this->accessMock->expects($this->once())
			->method('ownCloudUserNames')
			->will($this->returnValue(array('lisa', 'bart', 'kira', 'brad')));

		$groupBackend = new GroupLDAP($this->accessMock);
		$users = $groupBackend->usersInGroup('foobar');

		$this->assertSame(4, count($users));
	}

	/**
	 * tests that a user counting is complete, if all it's members have the group
	 * as their primary.
	 */
	public function  testCountUsersInGroupPrimaryMembersOnly() {
		$this->enableGroups();

		$this->accessMock->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));

		$this->accessMock->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				if($attr === 'primaryGroupToken') {
					return array(1337);
				}
				return array();
			}));

		$this->accessMock->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$this->accessMock->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(4));

		$groupBackend = new GroupLDAP($this->accessMock);
		$users = $groupBackend->countUsersInGroup('foobar');

		$this->assertSame(4, $users);
	}

	public function testGetUserGroupsMemberOf() {
		$this->enableGroups();

		$dn = 'cn=userX,dc=foobar';

		$this->accessMock->connection->hasPrimaryGroups = false;

		$this->accessMock->expects($this->any())
			->method('username2dn')
			->will($this->returnValue($dn));

		$this->accessMock->expects($this->exactly(3))
			->method('readAttribute')
			->will($this->onConsecutiveCalls(['cn=groupA,dc=foobar', 'cn=groupB,dc=foobar'], [], []));

		$this->accessMock->expects($this->exactly(2))
			->method('dn2groupname')
			->will($this->returnArgument(0));

		$this->accessMock->expects($this->exactly(3))
			->method('groupsMatchFilter')
			->will($this->returnArgument(0));

		$groupBackend = new GroupLDAP($this->accessMock);
		$groups = $groupBackend->getUserGroups('userX');

		$this->assertSame(2, count($groups));
	}

	public function testGetUserGroupsMemberOfDisabled() {
		$this->accessMock->connection->expects($this->any())
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

		$this->accessMock->connection->hasPrimaryGroups = false;

		$this->accessMock->expects($this->once())
			->method('username2dn')
			->will($this->returnValue($dn));

		$this->accessMock->expects($this->never())
			->method('readAttribute')
			->with($dn, 'memberOf');

		$this->accessMock->expects($this->once())
			->method('ownCloudGroupNames')
			->will($this->returnValue([]));

		$groupBackend = new GroupLDAP($this->accessMock);
		$groupBackend->getUserGroups('userX');
	}

	public function testCountUsersInPrimaryGroupIllegalSearch() {
		$groupDN = 'cn=foobar';

		$groupBackend = new GroupLDAP($this->accessMock);
		$count = $groupBackend->countUsersInPrimaryGroup($groupDN, null);

		$this->assertSame(0, $count);

		$groupBackend = new GroupLDAP($this->accessMock);
		$count = $groupBackend->countUsersInPrimaryGroup($groupDN, 0);

		$this->assertSame(0, $count);
	}

	public function testCountUsersInPrimaryGroup() {
		$groupDN = 'cn=foobar';
		$expected = 7;

		$this->accessMock->expects($this->any())
			->method('readAttribute')
			->will($this->returnValue(['3117']));

		$this->accessMock->expects($this->any())
			->method('countUsers')
			->with('properFilter')
			->will($this->returnValue($expected));

		$this->accessMock->expects($this->exactly(2))
			->method('combineFilterWithAnd')
			->will($this->returnValue('properFilter'));

		$groupBackend = new GroupLDAP($this->accessMock);

		$count = $groupBackend->countUsersInPrimaryGroup($groupDN);
		$this->assertSame($expected, $count);

		$count = $groupBackend->countUsersInPrimaryGroup($groupDN, 'barfoo');
		$this->assertSame($expected, $count);
	}

}