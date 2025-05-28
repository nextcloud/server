<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Group_LDAP as GroupLDAP;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\OfflineUser;
use OCA\User_LDAP\User\User;
use OCA\User_LDAP\User_Proxy;
use OCP\GroupInterface;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class GroupLDAPTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class Group_LDAPTest extends TestCase {
	private Access&MockObject $access;
	private GroupPluginManager&MockObject $pluginManager;
	private IConfig&MockObject $config;
	private IUserManager&MockObject $ncUserManager;
	private GroupLDAP $groupBackend;

	public function setUp(): void {
		parent::setUp();

		$this->access = $this->getAccessMock();
		$this->pluginManager = $this->createMock(GroupPluginManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->ncUserManager = $this->createMock(IUserManager::class);
	}

	public function initBackend(): void {
		$this->groupBackend = new GroupLDAP($this->access, $this->pluginManager, $this->config, $this->ncUserManager);
	}

	public function testCountEmptySearchString(): void {
		$groupDN = 'cn=group,dc=foo,dc=bar';

		$this->enableGroups();

		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturn($groupDN);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn) use ($groupDN) {
				if ($dn === $groupDN) {
					return [
						'uid=u11,ou=users,dc=foo,dc=bar',
						'uid=u22,ou=users,dc=foo,dc=bar',
						'uid=u33,ou=users,dc=foo,dc=bar',
						'uid=u34,ou=users,dc=foo,dc=bar'
					];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);
		// for primary groups
		$this->access->expects($this->once())
			->method('countUsers')
			->willReturn(2);

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['displayName', 'mail']);

		$this->initBackend();
		$users = $this->groupBackend->countUsersInGroup('group');

		$this->assertSame(6, $users);
	}

	/**
	 * @return MockObject|Access
	 */
	private function getAccessMock() {
		$lw = $this->createMock(ILDAPWrapper::class);
		$connector = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([$lw, '', null])
			->getMock();

		$this->access = $this->createMock(Access::class);
		$this->access->connection = $connector;
		$this->access->userManager = $this->createMock(Manager::class);

		return $this->access;
	}

	private function enableGroups(): void {
		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapDynamicGroupMemberURL') {
					return '';
				} elseif ($name === 'ldapBaseGroups') {
					return [];
				}
				return 1;
			});
	}

	public function testCountWithSearchString(): void {
		$this->enableGroups();

		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturn('cn=group,dc=foo,dc=bar');
		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->willReturn([]);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($name) {
				//the search operation will call readAttribute, thus we need
				//to analyze the "dn". All other times we just need to return
				//something that is neither null or false, but once an array
				//with the users in the group – so we do so all other times for
				//simplicity.
				if (str_starts_with($name, 'u')) {
					return strpos($name, '3');
				}
				return ['u11', 'u22', 'u33', 'u34'];
			});
		$this->access->expects($this->any())
			->method('dn2username')
			->willReturnCallback(function () {
				return 'foobar' . Server::get(ISecureRandom::class)->generate(7);
			});
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);
		$this->access->expects($this->any())
			->method('escapeFilterPart')
			->willReturnArgument(0);

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['displayName', 'mail']);

		$this->initBackend();
		$users = $this->groupBackend->countUsersInGroup('group', '3');

		$this->assertSame(2, $users);
	}

	public function testCountUsersWithPlugin(): void {
		/** @var GroupPluginManager|MockObject $pluginManager */
		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'countUsersInGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::COUNT_USERS)
			->willReturn(true);

		$this->pluginManager->expects($this->once())
			->method('countUsersInGroup')
			->with('gid', 'search')
			->willReturn(42);

		$this->initBackend();
		$this->assertEquals($this->groupBackend->countUsersInGroup('gid', 'search'), 42);
	}

	public function testGidNumber2NameSuccess(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('searchGroups')
			->willReturn([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]);

		$this->access->expects($this->once())
			->method('dn2groupname')
			->with('cn=foo,dc=barfoo,dc=bar')
			->willReturn('MyGroup');

		$this->initBackend();
		$group = $this->groupBackend->gidNumber2Name('3117', $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testGidNumberID2NameNoGroup(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('searchGroups')
			->willReturn([]);

		$this->access->expects($this->never())
			->method('dn2groupname');

		$this->initBackend();
		$group = $this->groupBackend->gidNumber2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGidNumberID2NameNoName(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('searchGroups')
			->willReturn([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]);

		$this->access->expects($this->once())
			->method('dn2groupname')
			->willReturn(false);

		$this->initBackend();
		$group = $this->groupBackend->gidNumber2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGetEntryGidNumberValue(): void {
		$this->enableGroups();

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'gidNumber';

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->willReturn(['3117']);

		$this->initBackend();
		$gid = $this->groupBackend->getGroupGidNumber($dn);

		$this->assertSame('3117', $gid);
	}

	public function testGetEntryGidNumberNoValue(): void {
		$this->enableGroups();

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'gidNumber';

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->willReturn(false);

		$this->initBackend();
		$gid = $this->groupBackend->getGroupGidNumber($dn);

		$this->assertSame(false, $gid);
	}

	public function testPrimaryGroupID2NameSuccessCache(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';
		$gid = '3117';

		/** @var MockObject $connection */
		$connection = $this->access->connection;
		$connection->expects($this->once())
			->method('getFromCache')
			->with('primaryGroupIDtoName_' . $gid)
			->willReturn('MyGroup');

		$this->access->expects($this->never())
			->method('getSID');

		$this->access->expects($this->never())
			->method('searchGroups');

		$this->access->expects($this->never())
			->method('dn2groupname');

		$this->initBackend();
		$group = $this->groupBackend->primaryGroupID2Name($gid, $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testPrimaryGroupID2NameSuccess(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->willReturn('S-1-5-21-249921958-728525901-1594176202');

		$this->access->expects($this->once())
			->method('searchGroups')
			->willReturn([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]);

		$this->access->expects($this->once())
			->method('dn2groupname')
			->with('cn=foo,dc=barfoo,dc=bar')
			->willReturn('MyGroup');

		$this->initBackend();
		$group = $this->groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testPrimaryGroupID2NameNoSID(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->willReturn(false);

		$this->access->expects($this->never())
			->method('searchGroups');

		$this->access->expects($this->never())
			->method('dn2groupname');

		$this->initBackend();
		$group = $this->groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoGroup(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->willReturn('S-1-5-21-249921958-728525901-1594176202');

		$this->access->expects($this->once())
			->method('searchGroups')
			->willReturn([]);

		$this->access->expects($this->never())
			->method('dn2groupname');

		$this->initBackend();
		$group = $this->groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoName(): void {
		$this->enableGroups();

		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->willReturn('S-1-5-21-249921958-728525901-1594176202');

		$this->access->expects($this->once())
			->method('searchGroups')
			->willReturn([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]);

		$this->access->expects($this->once())
			->method('dn2groupname')
			->willReturn(false);

		$this->initBackend();
		$group = $this->groupBackend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGetEntryGroupIDValue(): void {
		//tests getEntryGroupID via getGroupPrimaryGroupID
		//which is basically identical to getUserPrimaryGroupIDs
		$this->enableGroups();

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->willReturn(['3117']);

		$this->initBackend();
		$gid = $this->groupBackend->getGroupPrimaryGroupID($dn);

		$this->assertSame('3117', $gid);
	}

	public function testGetEntryGroupIDNoValue(): void {
		//tests getEntryGroupID via getGroupPrimaryGroupID
		//which is basically identical to getUserPrimaryGroupIDs
		$this->enableGroups();

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->willReturn(false);

		$this->initBackend();
		$gid = $this->groupBackend->getGroupPrimaryGroupID($dn);

		$this->assertSame(false, $gid);
	}

	/**
	 * tests whether Group Backend behaves correctly when cache with uid and gid
	 * is hit
	 */
	public function testInGroupHitsUidGidCache(): void {
		$this->enableGroups();

		$uid = 'someUser';
		$gid = 'someGroup';
		$cacheKey = 'inGroup' . $uid . ':' . $gid;

		$this->access->connection->expects($this->once())
			->method('getFromCache')
			->with($cacheKey)
			->willReturn(true);

		$this->access->expects($this->never())
			->method('username2dn');

		$this->initBackend();
		$this->groupBackend->inGroup($uid, $gid);
	}

	public static function groupWithMembersProvider(): array {
		return [
			[
				'someGroup',
				'cn=someGroup,ou=allTheGroups,ou=someDepartment,dc=someDomain,dc=someTld',
				[
					'uid=oneUser,ou=someTeam,ou=someDepartment,dc=someDomain,dc=someTld',
					'uid=someUser,ou=someTeam,ou=someDepartment,dc=someDomain,dc=someTld',
					'uid=anotherUser,ou=someTeam,ou=someDepartment,dc=someDomain,dc=someTld',
					'uid=differentUser,ou=someTeam,ou=someDepartment,dc=someDomain,dc=someTld',
				],
			],
		];
	}

	/**
	 * @dataProvider groupWithMembersProvider
	 */
	public function testInGroupMember(string $gid, string $groupDn, array $memberDNs): void {
		$uid = 'someUser';
		$userDn = $memberDNs[0];

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				switch ($name) {
					case 'ldapGroupMemberAssocAttr':
						return 'member';
					case 'ldapDynamicGroupMemberURL':
						return '';
					case 'hasPrimaryGroups':
					case 'ldapNestedGroups':
						return 0;
					default:
						return 1;
				}
			});
		$this->access->connection->expects($this->any())
			->method('getFromCache')
			->willReturn(null);

		$this->access->expects($this->once())
			->method('username2dn')
			->with($uid)
			->willReturn($userDn);
		$this->access->expects($this->once())
			->method('groupname2dn')
			->willReturn($groupDn);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn($memberDNs);

		$this->initBackend();
		$this->assertTrue($this->groupBackend->inGroup($uid, $gid));
	}

	/**
	 * @dataProvider groupWithMembersProvider
	 */
	public function testInGroupMemberNot(string $gid, string $groupDn, array $memberDNs): void {
		$uid = 'unelatedUser';
		$userDn = 'uid=unrelatedUser,ou=unrelatedTeam,ou=unrelatedDepartment,dc=someDomain,dc=someTld';

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				switch ($name) {
					case 'ldapGroupMemberAssocAttr':
						return 'member';
					case 'ldapDynamicGroupMemberURL':
						return '';
					case 'hasPrimaryGroups':
					case 'ldapNestedGroups':
						return 0;
					default:
						return 1;
				}
			});
		$this->access->connection->expects($this->any())
			->method('getFromCache')
			->willReturn(null);

		$this->access->expects($this->once())
			->method('username2dn')
			->with($uid)
			->willReturn($userDn);
		$this->access->expects($this->once())
			->method('groupname2dn')
			->willReturn($groupDn);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn($memberDNs);

		$this->initBackend();
		$this->assertFalse($this->groupBackend->inGroup($uid, $gid));
	}

	/**
	 * @dataProvider groupWithMembersProvider
	 */
	public function testInGroupMemberUid(string $gid, string $groupDn, array $memberDNs): void {
		$memberUids = [];
		$userRecords = [];
		foreach ($memberDNs as $dn) {
			$memberUids[] = ldap_explode_dn($dn, 0)[0];
			$userRecords[] = ['dn' => [$dn]];
		}

		$uid = 'someUser';
		$userDn = $memberDNs[0];

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				switch ($name) {
					case 'ldapGroupMemberAssocAttr':
						return 'memberUid';
					case 'ldapDynamicGroupMemberURL':
						return '';
					case 'ldapLoginFilter':
						return 'uid=%uid';
					case 'hasPrimaryGroups':
					case 'ldapNestedGroups':
						return 0;
					default:
						return 1;
				}
			});
		$this->access->connection->expects($this->any())
			->method('getFromCache')
			->willReturn(null);

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['uid', 'mail', 'displayname']);

		$this->access->expects($this->once())
			->method('username2dn')
			->with($uid)
			->willReturn($userDn);
		$this->access->expects($this->once())
			->method('groupname2dn')
			->willReturn($groupDn);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn($memberUids);
		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->willReturn($userRecords);
		$this->access->expects($this->any())
			->method('combineFilterWithOr')
			->willReturn('(|(pseudo=filter)(filter=pseudo))');

		$this->initBackend();
		$this->assertTrue($this->groupBackend->inGroup($uid, $gid));
	}

	public function testGetGroupsWithOffset(): void {
		$this->enableGroups();

		$this->access->expects($this->once())
			->method('nextcloudGroupNames')
			->willReturn(['group1', 'group2']);

		$this->initBackend();
		$groups = $this->groupBackend->getGroups('', 2, 2);

		$this->assertSame(2, count($groups));
	}

	/**
	 * tests that a user listing is complete, if all its members have the group
	 * as their primary.
	 */
	public function testUsersInGroupPrimaryMembersOnly(): void {
		$this->enableGroups();

		$this->access->connection->expects($this->any())
			->method('getFromCache')
			->willReturn(null);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				if ($attr === 'primaryGroupToken') {
					return [1337];
				} elseif ($attr === 'gidNumber') {
					return [4211];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturn('cn=foobar,dc=foo,dc=bar');
		$this->access->expects($this->exactly(2))
			->method('nextcloudUserNames')
			->willReturnOnConsecutiveCalls(['lisa', 'bart', 'kira', 'brad'], ['walle', 'dino', 'xenia']);
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);
		$this->access->expects($this->any())
			->method('combineFilterWithAnd')
			->willReturn('pseudo=filter');

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['displayName', 'mail']);

		$this->initBackend();
		$users = $this->groupBackend->usersInGroup('foobar');

		$this->assertSame(7, count($users));
	}

	/**
	 * tests that a user listing is complete, if all its members have the group
	 * as their primary.
	 */
	public function testUsersInGroupPrimaryAndUnixMembers(): void {
		$this->enableGroups();

		$this->access->connection->expects($this->any())
			->method('getFromCache')
			->willReturn(null);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				if ($attr === 'primaryGroupToken') {
					return [1337];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturn('cn=foobar,dc=foo,dc=bar');
		$this->access->expects($this->once())
			->method('nextcloudUserNames')
			->willReturn(['lisa', 'bart', 'kira', 'brad']);
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);
		$this->access->expects($this->any())
			->method('combineFilterWithAnd')
			->willReturn('pseudo=filter');

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['displayName', 'mail']);

		$this->initBackend();
		$users = $this->groupBackend->usersInGroup('foobar');

		$this->assertSame(4, count($users));
	}

	/**
	 * tests that a user counting is complete, if all its members have the group
	 * as their primary.
	 */
	public function testCountUsersInGroupPrimaryMembersOnly(): void {
		$this->enableGroups();

		$this->access->connection->expects($this->any())
			->method('getFromCache')
			->willReturn(null);

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($dn, $attr) {
				if ($attr === 'primaryGroupToken') {
					return [1337];
				}
				return [];
			});
		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturn('cn=foobar,dc=foo,dc=bar');
		$this->access->expects($this->once())
			->method('countUsers')
			->willReturn(4);
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);

		$this->access->userManager->expects($this->any())
			->method('getAttributes')
			->willReturn(['displayName', 'mail']);

		$this->initBackend();
		$users = $this->groupBackend->countUsersInGroup('foobar');

		$this->assertSame(4, $users);
	}

	public function testGetUserGroupsMemberOf(): void {
		$this->enableGroups();

		$dn = 'cn=userX,dc=foobar';

		$this->access->connection->hasPrimaryGroups = false;
		$this->access->connection->hasGidNumber = false;

		$expectedGroups = ['cn=groupA,dc=foobar', 'cn=groupB,dc=foobar'];

		$this->access->expects($this->any())
			->method('username2dn')
			->willReturn($dn);
		$this->access->expects($this->exactly(5))
			->method('readAttribute')
			->will($this->onConsecutiveCalls($expectedGroups, [], [], [], []));
		$this->access->expects($this->any())
			->method('dn2groupname')
			->willReturnArgument(0);
		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturnArgument(0);
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('userX', 'user_ldap', 'cached-group-memberships-', \json_encode($expectedGroups));

		$this->initBackend();
		$groups = $this->groupBackend->getUserGroups('userX');

		$this->assertSame(2, count($groups));
	}

	public function testGetUserGroupsMemberOfDisabled(): void {
		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'useMemberOfToDetectMembership') {
					return 0;
				} elseif ($name === 'ldapDynamicGroupMemberURL') {
					return '';
				}
				return 1;
			});

		$dn = 'cn=userX,dc=foobar';

		$this->access->connection->hasPrimaryGroups = false;
		$this->access->connection->hasGidNumber = false;

		$this->access->expects($this->once())
			->method('username2dn')
			->willReturn($dn);
		$this->access->expects($this->never())
			->method('readAttribute')
			->with($dn, 'memberOf');
		$this->access->expects($this->once())
			->method('nextcloudGroupNames')
			->willReturn([]);

		// empty group result should not be oer
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('userX', 'user_ldap', 'cached-group-memberships-', '[]');

		$ldapUser = $this->createMock(User::class);

		$this->access->userManager->expects($this->any())
			->method('get')
			->with('userX')
			->willReturn($ldapUser);

		$userBackend = $this->createMock(User_Proxy::class);
		$userBackend->expects($this->once())
			->method('userExistsOnLDAP')
			->with('userX', true)
			->willReturn(true);

		$ncUser = $this->createMock(IUser::class);
		$ncUser->expects($this->any())
			->method('getBackend')
			->willReturn($userBackend);

		$this->ncUserManager->expects($this->once())
			->method('get')
			->with('userX')
			->willReturn($ncUser);

		$this->initBackend();
		$this->groupBackend->getUserGroups('userX');
	}

	public function testGetUserGroupsOfflineUser(): void {
		$this->enableGroups();

		$offlineUser = $this->createMock(OfflineUser::class);

		$this->config->expects($this->any())
			->method('getUserValue')
			->with('userX', 'user_ldap', 'cached-group-memberships-', $this->anything())
			->willReturn(\json_encode(['groupB', 'groupF']));

		$this->access->userManager->expects($this->any())
			->method('get')
			->with('userX')
			->willReturn($offlineUser);

		$this->initBackend();
		$returnedGroups = $this->groupBackend->getUserGroups('userX');
		$this->assertCount(2, $returnedGroups);
		$this->assertContains('groupB', $returnedGroups);
		$this->assertContains('groupF', $returnedGroups);
	}

	/**
	 * regression tests against a case where a json object was stored instead of expected list
	 * @see https://github.com/nextcloud/server/issues/42374
	 */
	public function testGetUserGroupsOfflineUserUnexpectedJson(): void {
		$this->enableGroups();

		$offlineUser = $this->createMock(OfflineUser::class);

		$this->config->expects($this->any())
			->method('getUserValue')
			->with('userX', 'user_ldap', 'cached-group-memberships-', $this->anything())
			// results in a json object: {"0":"groupB","2":"groupF"}
			->willReturn(\json_encode([0 => 'groupB', 2 => 'groupF']));

		$this->access->userManager->expects($this->any())
			->method('get')
			->with('userX')
			->willReturn($offlineUser);

		$this->initBackend();
		$returnedGroups = $this->groupBackend->getUserGroups('userX');
		$this->assertCount(2, $returnedGroups);
		$this->assertContains('groupB', $returnedGroups);
		$this->assertContains('groupF', $returnedGroups);
	}

	public function testGetUserGroupsUnrecognizedOfflineUser(): void {
		$this->enableGroups();
		$dn = 'cn=userX,dc=foobar';

		$ldapUser = $this->createMock(User::class);

		$userBackend = $this->createMock(User_Proxy::class);
		$userBackend->expects($this->once())
			->method('userExistsOnLDAP')
			->with('userX', true)
			->willReturn(false);

		$ncUser = $this->createMock(IUser::class);
		$ncUser->expects($this->any())
			->method('getBackend')
			->willReturn($userBackend);

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('userX', 'user_ldap', 'cached-group-memberships-', $this->anything())
			->willReturn(\json_encode(['groupB', 'groupF']));

		$this->access->expects($this->any())
			->method('username2dn')
			->willReturn($dn);

		$this->access->userManager->expects($this->any())
			->method('get')
			->with('userX')
			->willReturn($ldapUser);

		$this->ncUserManager->expects($this->once())
			->method('get')
			->with('userX')
			->willReturn($ncUser);

		$this->initBackend();
		$returnedGroups = $this->groupBackend->getUserGroups('userX');
		$this->assertCount(2, $returnedGroups);
		$this->assertContains('groupB', $returnedGroups);
		$this->assertContains('groupF', $returnedGroups);
	}

	public static function nestedGroupsProvider(): array {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider nestedGroupsProvider
	 */
	public function testGetGroupsByMember(bool $nestedGroups): void {
		$groupFilter = '(&(objectclass=nextcloudGroup)(nextcloudEnabled=TRUE))';
		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function (string $name) use ($nestedGroups, $groupFilter) {
				switch ($name) {
					case 'useMemberOfToDetectMembership':
						return 0;
					case 'ldapDynamicGroupMemberURL':
						return '';
					case 'ldapNestedGroups':
						return (int)$nestedGroups;
					case 'ldapGroupMemberAssocAttr':
						return 'member';
					case 'ldapGroupFilter':
						return $groupFilter;
					case 'ldapBaseGroups':
						return [];
					case 'ldapGroupDisplayName':
						return 'cn';
				}
				return 1;
			});

		$dn = 'cn=userX,dc=foobar';

		$this->access->connection->hasPrimaryGroups = false;
		$this->access->connection->hasGidNumber = false;

		$this->access->expects($this->exactly(2))
			->method('username2dn')
			->willReturn($dn);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn([]);
		$this->access->expects($this->any())
			->method('combineFilterWithAnd')
			->willReturnCallback(function (array $filterParts) {
				// ⚠ returns a pseudo-filter only, not real LDAP Filter syntax
				return implode('&', $filterParts);
			});

		$group1 = [
			'cn' => 'group1',
			'dn' => ['cn=group1,ou=groups,dc=domain,dc=com'],
			'member' => [$dn],
		];
		$group2 = [
			'cn' => 'group2',
			'dn' => ['cn=group2,ou=groups,dc=domain,dc=com'],
			'member' => [$dn],
		];
		$group3 = [
			'cn' => 'group3',
			'dn' => ['cn=group3,ou=groups,dc=domain,dc=com'],
			'member' => [$group2['dn'][0]],
		];

		$expectedGroups = ($nestedGroups ? [$group1, $group2, $group3] : [$group1, $group2]);
		$expectedGroupsNames = ($nestedGroups ? ['group1', 'group2', 'group3'] : ['group1', 'group2']);

		$this->access->expects($this->any())
			->method('nextcloudGroupNames')
			->with($expectedGroups)
			->willReturn($expectedGroupsNames);
		$this->access->expects($nestedGroups ? $this->atLeastOnce() : $this->once())
			->method('fetchListOfGroups')
			->willReturnCallback(function ($filter, $attr, $limit, $offset) use ($nestedGroups, $groupFilter, $group1, $group2, $group3, $dn) {
				static $firstRun = true;
				if (!$nestedGroups) {
					// When nested groups are enabled, groups cannot be filtered early as it would
					// exclude intermediate groups. But we can, and should, when working with flat groups.
					$this->assertTrue(str_contains($filter, $groupFilter));
				}
				[$memberFilter] = explode('&', $filter);
				if ($memberFilter === 'member=' . $dn) {
					return [$group1, $group2];
					return [];
				} elseif ($memberFilter === 'member=' . $group2['dn'][0]) {
					return [$group3];
				} else {
					return [];
				}
			});
		$this->access->expects($this->any())
			->method('dn2groupname')
			->willReturnCallback(function (string $dn) {
				return ldap_explode_dn($dn, 1)[0];
			});
		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturnCallback(function (string $gid) use ($group1, $group2, $group3) {
				if ($gid === $group1['cn']) {
					return $group1['dn'][0];
				}
				if ($gid === $group2['cn']) {
					return $group2['dn'][0];
				}
				if ($gid === $group3['cn']) {
					return $group3['dn'][0];
				}
			});
		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->willReturn(true);

		$this->initBackend();
		$groups = $this->groupBackend->getUserGroups('userX');
		$this->assertEquals($expectedGroupsNames, $groups);

		$groupsAgain = $this->groupBackend->getUserGroups('userX');
		$this->assertEquals($expectedGroupsNames, $groupsAgain);
	}

	public function testCreateGroupWithPlugin(): void {
		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'createGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::CREATE_GROUP)
			->willReturn(true);

		$this->pluginManager->expects($this->once())
			->method('createGroup')
			->with('gid')
			->willReturn('result');

		$this->initBackend();
		$this->assertTrue($this->groupBackend->createGroup('gid'));
	}


	public function testCreateGroupFailing(): void {
		$this->expectException(\Exception::class);

		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'createGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::CREATE_GROUP)
			->willReturn(false);

		$this->initBackend();
		$this->groupBackend->createGroup('gid');
	}

	public function testDeleteGroupWithPlugin(): void {
		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'deleteGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::DELETE_GROUP)
			->willReturn(true);

		$this->pluginManager->expects($this->once())
			->method('deleteGroup')
			->with('gid')
			->willReturn(true);

		$mapper = $this->getMockBuilder(GroupMapping::class)
			->onlyMethods(['unmap'])
			->disableOriginalConstructor()
			->getMock();

		$this->access->expects($this->any())
			->method('getGroupMapper')
			->willReturn($mapper);

		$this->initBackend();
		$this->assertTrue($this->groupBackend->deleteGroup('gid'));
	}


	public function testDeleteGroupFailing(): void {
		$this->expectException(\Exception::class);

		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'deleteGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::DELETE_GROUP)
			->willReturn(false);

		$this->initBackend();
		$this->groupBackend->deleteGroup('gid');
	}

	public function testAddToGroupWithPlugin(): void {
		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'addToGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::ADD_TO_GROUP)
			->willReturn(true);

		$this->pluginManager->expects($this->once())
			->method('addToGroup')
			->with('uid', 'gid')
			->willReturn('result');

		$this->initBackend();
		$this->assertEquals('result', $this->groupBackend->addToGroup('uid', 'gid'));
	}


	public function testAddToGroupFailing(): void {
		$this->expectException(\Exception::class);

		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'addToGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::ADD_TO_GROUP)
			->willReturn(false);

		$this->initBackend();
		$this->groupBackend->addToGroup('uid', 'gid');
	}

	public function testRemoveFromGroupWithPlugin(): void {
		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'removeFromGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::REMOVE_FROM_GROUP)
			->willReturn(true);

		$this->pluginManager->expects($this->once())
			->method('removeFromGroup')
			->with('uid', 'gid')
			->willReturn('result');

		$this->initBackend();
		$this->assertEquals('result', $this->groupBackend->removeFromGroup('uid', 'gid'));
	}


	public function testRemoveFromGroupFailing(): void {
		$this->expectException(\Exception::class);

		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'removeFromGroup'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::REMOVE_FROM_GROUP)
			->willReturn(false);

		$this->initBackend();
		$this->groupBackend->removeFromGroup('uid', 'gid');
	}

	public function testGetGroupDetailsWithPlugin(): void {
		/** @var GroupPluginManager|MockObject $pluginManager */
		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'getGroupDetails'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::GROUP_DETAILS)
			->willReturn(true);

		$this->pluginManager->expects($this->once())
			->method('getGroupDetails')
			->with('gid')
			->willReturn('result');

		$this->initBackend();
		$this->assertEquals('result', $this->groupBackend->getGroupDetails('gid'));
	}

	public function testGetGroupDetailsFailing(): void {
		$this->expectException(\Exception::class);

		$this->pluginManager = $this->getMockBuilder(GroupPluginManager::class)
			->onlyMethods(['implementsActions', 'getGroupDetails'])
			->getMock();

		$this->pluginManager->expects($this->once())
			->method('implementsActions')
			->with(GroupInterface::GROUP_DETAILS)
			->willReturn(false);

		$this->initBackend();
		$this->groupBackend->getGroupDetails('gid');
	}

	public static function groupMemberProvider(): array {
		$base = 'dc=species,dc=earth';

		$birdsDn = [
			'uid=3723,' . $base,
			'uid=8372,' . $base,
			'uid=8427,' . $base,
			'uid=2333,' . $base,
			'uid=4754,' . $base,
		];
		$birdsUid = [
			'3723',
			'8372',
			'8427',
			'2333',
			'4754',
		];
		$animalsDn = [
			'uid=lion,' . $base,
			'uid=tiger,' . $base,
		];
		$plantsDn = [
			'uid=flower,' . $base,
			'uid=tree,' . $base,
		];
		$thingsDn = [
			'uid=thing1,' . $base,
			'uid=thing2,' . $base,
		];

		return [
			[ #0 – test DNs
				['cn=Birds,' . $base => $birdsDn],
				['cn=Birds,' . $base => $birdsDn]
			],
			[ #1 – test uids
				['cn=Birds,' . $base => $birdsUid],
				['cn=Birds,' . $base => $birdsUid]
			],
			[ #2 – test simple nested group
				['cn=Animals,' . $base => array_merge($birdsDn, $animalsDn)],
				[
					'cn=Animals,' . $base => array_merge(['cn=Birds,' . $base], $animalsDn),
					'cn=Birds,' . $base => $birdsDn,
				]
			],
			[ #3 – test recursive nested group
				[
					'cn=Animals,' . $base => array_merge($birdsDn, $animalsDn),
					'cn=Birds,' . $base => array_merge($birdsDn, $animalsDn),
				],
				[
					'cn=Animals,' . $base => array_merge(['cn=Birds,' . $base,'cn=Birds,' . $base,'cn=Animals,' . $base], $animalsDn),
					'cn=Birds,' . $base => array_merge(['cn=Animals,' . $base,'cn=Birds,' . $base], $birdsDn),
				]
			],
			[ #4 – Complicated nested group
				['cn=Things,' . $base => array_merge($birdsDn, $animalsDn, $thingsDn, $plantsDn)],
				[
					'cn=Animals,' . $base => array_merge(['cn=Birds,' . $base], $animalsDn),
					'cn=Birds,' . $base => $birdsDn,
					'cn=Plants,' . $base => $plantsDn,
					'cn=Things,' . $base => array_merge(['cn=Animals,' . $base,'cn=Plants,' . $base], $thingsDn),
				]
			],
		];
	}

	/**
	 * @dataProvider groupMemberProvider
	 */
	public function testGroupMembers(array $expectedResult, array $groupsInfo): void {
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function ($group) use ($groupsInfo) {
				if (isset($groupsInfo[$group])) {
					return $groupsInfo[$group];
				}
				return [];
			});

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function (string $name) {
				if ($name === 'ldapNestedGroups') {
					return 1;
				} elseif ($name === 'ldapGroupMemberAssocAttr') {
					return 'attr';
				}
				return null;
			});

		$this->initBackend();
		foreach ($expectedResult as $groupDN => $expectedMembers) {
			$resultingMembers = $this->invokePrivate($this->groupBackend, '_groupMembers', [$groupDN]);

			sort($expectedMembers);
			sort($resultingMembers);
			$this->assertEquals($expectedMembers, $resultingMembers);
		}
	}

	public static function displayNameProvider(): array {
		return [
			['Graphic Novelists', ['Graphic Novelists']],
			['', false],
		];
	}

	/**
	 * @dataProvider displayNameProvider
	 */
	public function testGetDisplayName(string $expected, bool|array $ldapResult): void {
		$gid = 'graphic_novelists';

		$this->access->expects($this->atLeastOnce())
			->method('readAttribute')
			->willReturn($ldapResult);

		$this->access->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapGroupMemberAssocAttr') {
					return 'member';
				} elseif ($name === 'ldapGroupFilter') {
					return 'objectclass=nextcloudGroup';
				} elseif ($name === 'ldapGroupDisplayName') {
					return 'cn';
				}
				return null;
			});

		$this->access->expects($this->any())
			->method('groupname2dn')
			->willReturn('fakedn');

		$this->initBackend();
		$this->assertSame($expected, $this->groupBackend->getDisplayName($gid));
	}
}
