<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Provisioning_API\Tests\Controller;

use OC\Group\Manager;
use OC\User\NoUserException;
use OCA\Provisioning_API\Controller\GroupsController;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\OCS\OCSException;
use OCP\Files\IRootFolder;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class GroupsControllerTest extends \Test\TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	protected $request;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
	protected $groupManager;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;
	/** @var IAccountManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $accountManager;
	/** @var ISubAdmin|\PHPUnit\Framework\MockObject\MockObject */
	protected $subAdminManager;
	/** @var IFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10nFactory;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;

	/** @var GroupsController|\PHPUnit\Framework\MockObject\MockObject */
	protected $api;

	private IRootFolder $rootFolder;


	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->subAdminManager = $this->createMock(ISubAdmin::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->groupManager
			->method('getSubAdmin')
			->willReturn($this->subAdminManager);

		$this->api = $this->getMockBuilder(GroupsController::class)
			->setConstructorArgs([
				'provisioning_api',
				$this->request,
				$this->userManager,
				$this->config,
				$this->groupManager,
				$this->userSession,
				$this->accountManager,
				$this->subAdminManager,
				$this->l10nFactory,
				$this->rootFolder,
				$this->logger
			])
			->setMethods(['fillStorageInfo'])
			->getMock();
	}

	/**
	 * @param string $gid
	 * @return IGroup|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function createGroup($gid) {
		$group = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$group
			->method('getGID')
			->willReturn($gid);
		$group
			->method('getDisplayName')
			->willReturn($gid . '-name');
		$group
			->method('count')
			->willReturn(123);
		$group
			->method('countDisabled')
			->willReturn(11);
		$group
			->method('canAddUser')
			->willReturn(true);
		$group
			->method('canRemoveUser')
			->willReturn(true);

		return $group;
	}

	/**
	 * @param string $uid
	 * @return IUser|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function createUser($uid) {
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$user
			->method('getUID')
			->willReturn($uid);
		$backendMock = $this->createMock(UserInterface::class);
		$user
			->method('getBackend')
			->willReturn($backendMock);
		return $user;
	}

	private function asUser() {
		$user = $this->createUser('user');
		$this->userSession
			->method('getUser')
			->willReturn($user);
	}

	private function asAdmin() {
		$user = $this->createUser('admin');
		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->groupManager
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
	}

	private function asSubAdminOfGroup($group) {
		$user = $this->createUser('subAdmin');
		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->subAdminManager
			->method('isSubAdminOfGroup')
			->willReturnCallback(function ($_user, $_group) use ($user, $group) {
				if ($_user === $user && $_group === $group) {
					return true;
				}
				return false;
			});
	}

	public function dataGetGroups() {
		return [
			[null, 0, 0],
			['foo', 0, 0],
			[null, 1, 0],
			[null, 0, 2],
			['foo', 1, 2],
		];
	}

	/**
	 * @dataProvider dataGetGroups
	 *
	 * @param string|null $search
	 * @param int|null $limit
	 * @param int|null $offset
	 */
	public function testGetGroups($search, $limit, $offset): void {
		$groups = [$this->createGroup('group1'), $this->createGroup('group2')];

		$search = $search === null ? '' : $search;

		$this->groupManager
			->expects($this->once())
			->method('search')
			->with($search, $limit, $offset)
			->willReturn($groups);

		$result = $this->api->getGroups($search, $limit, $offset);
		$this->assertEquals(['groups' => ['group1', 'group2']], $result->getData());
	}

	/**
	 * @dataProvider dataGetGroups
	 *
	 * @param string|null $search
	 * @param int|null $limit
	 * @param int|null $offset
	 */
	public function testGetGroupsDetails($search, $limit, $offset): void {
		$groups = [$this->createGroup('group1'), $this->createGroup('group2')];

		$search = $search === null ? '' : $search;

		$this->groupManager
			->expects($this->once())
			->method('search')
			->with($search, $limit, $offset)
			->willReturn($groups);

		$result = $this->api->getGroupsDetails($search, $limit, $offset);
		$this->assertEquals(['groups' => [
			[
				'id' => 'group1',
				'displayname' => 'group1-name',
				'usercount' => 123,
				'disabled' => 11,
				'canAdd' => true,
				'canRemove' => true
			],
			[
				'id' => 'group2',
				'displayname' => 'group2-name',
				'usercount' => 123,
				'disabled' => 11,
				'canAdd' => true,
				'canRemove' => true
			]
		]], $result->getData());
	}

	public function testGetGroupAsSubadmin(): void {
		$group = $this->createGroup('group');
		$this->asSubAdminOfGroup($group);

		$this->groupManager
			->method('get')
			->with('group')
			->willReturn($group);
		$this->groupManager
			->method('groupExists')
			->with('group')
			->willReturn(true);
		$group
			->method('getUsers')
			->willReturn([
				$this->createUser('user1'),
				$this->createUser('user2')
			]);

		$result = $this->api->getGroup('group');

		$this->assertEquals(['users' => ['user1', 'user2']], $result->getData());
	}


	public function testGetGroupAsIrrelevantSubadmin(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);

		$group = $this->createGroup('group');
		$otherGroup = $this->createGroup('otherGroup');
		$this->asSubAdminOfGroup($otherGroup);

		$this->groupManager
			->method('get')
			->with('group')
			->willReturn($group);
		$this->groupManager
			->method('groupExists')
			->with('group')
			->willReturn(true);

		$this->api->getGroup('group');
	}

	public function testGetGroupAsAdmin(): void {
		$group = $this->createGroup('group');
		$this->asAdmin();

		$this->groupManager
			->method('get')
			->with('group')
			->willReturn($group);
		$this->groupManager
			->method('groupExists')
			->with('group')
			->willReturn(true);
		$group
			->method('getUsers')
			->willReturn([
				$this->createUser('user1'),
				$this->createUser('user2')
			]);

		$result = $this->api->getGroup('group');

		$this->assertEquals(['users' => ['user1', 'user2']], $result->getData());
	}


	public function testGetGroupNonExisting(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('The requested group could not be found');
		$this->expectExceptionCode(404);

		$this->asUser();

		$this->api->getGroup($this->getUniqueID());
	}


	public function testGetSubAdminsOfGroupsNotExists(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('Group does not exist');
		$this->expectExceptionCode(101);

		$this->api->getSubAdminsOfGroup('NonExistingGroup');
	}

	public function testGetSubAdminsOfGroup(): void {
		$group = $this->createGroup('GroupWithSubAdmins');
		$this->groupManager
			->method('get')
			->with('GroupWithSubAdmins')
			->willReturn($group);

		$this->subAdminManager
			->expects($this->once())
			->method('getGroupsSubAdmins')
			->with($group)
			->willReturn([
				$this->createUser('SubAdmin1'),
				$this->createUser('SubAdmin2'),
			]);

		$result = $this->api->getSubAdminsOfGroup('GroupWithSubAdmins');
		$this->assertEquals(['SubAdmin1', 'SubAdmin2'], $result->getData());
	}

	public function testGetSubAdminsOfGroupEmptyList(): void {
		$group = $this->createGroup('GroupWithOutSubAdmins');
		$this->groupManager
			->method('get')
			->with('GroupWithOutSubAdmins')
			->willReturn($group);

		$this->subAdminManager
			->expects($this->once())
			->method('getGroupsSubAdmins')
			->with($group)
			->willReturn([
			]);

		$result = $this->api->getSubAdminsOfGroup('GroupWithOutSubAdmins');
		$this->assertEquals([], $result->getData());
	}


	public function testAddGroupEmptyGroup(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('Invalid group name');
		$this->expectExceptionCode(101);

		$this->api->addGroup('');
	}


	public function testAddGroupExistingGroup(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(102);

		$this->groupManager
			->method('groupExists')
			->with('ExistingGroup')
			->willReturn(true);

		$this->api->addGroup('ExistingGroup');
	}

	public function testAddGroup(): void {
		$this->groupManager
			->method('groupExists')
			->with('NewGroup')
			->willReturn(false);

		$group = $this->createGroup('NewGroup');
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup')
			->willReturn($group);

		$this->api->addGroup('NewGroup');
	}

	public function testAddGroupWithSpecialChar(): void {
		$this->groupManager
			->method('groupExists')
			->with('Iñtërnâtiônàlizætiøn')
			->willReturn(false);

		$group = $this->createGroup('Iñtërnâtiônàlizætiøn');
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('Iñtërnâtiônàlizætiøn')
			->willReturn($group);

		$this->api->addGroup('Iñtërnâtiônàlizætiøn');
	}


	public function testDeleteGroupNonExisting(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(101);

		$this->api->deleteGroup('NonExistingGroup');
	}


	public function testDeleteAdminGroup(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(102);

		$this->groupManager
			->method('groupExists')
			->with('admin')
			->willReturn('true');

		$this->api->deleteGroup('admin');
	}

	public function testDeleteGroup(): void {
		$this->groupManager
			->method('groupExists')
			->with('ExistingGroup')
			->willReturn('true');

		$group = $this->createGroup('ExistingGroup');
		$this->groupManager
			->method('get')
			->with('ExistingGroup')
			->willReturn($group);
		$group
			->expects($this->once())
			->method('delete')
			->willReturn(true);

		$this->api->deleteGroup('ExistingGroup');
	}

	public function testDeleteGroupEncoding(): void {
		$this->groupManager
			->method('groupExists')
			->with('ExistingGroup A/B')
			->willReturn('true');

		$group = $this->createGroup('ExistingGroup');
		$this->groupManager
			->method('get')
			->with('ExistingGroup A/B')
			->willReturn($group);
		$group
			->expects($this->once())
			->method('delete')
			->willReturn(true);

		$this->api->deleteGroup(urlencode('ExistingGroup A/B'));
	}

	public function testGetGroupUsersDetails(): void {
		$gid = 'ncg1';

		$this->asAdmin();

		$users = [
			'ncu1' => $this->createUser('ncu1'), # regular
			'ncu2' => $this->createUser('ncu2'), # the zombie
		];
		$users['ncu2']->expects($this->atLeastOnce())
			->method('getHome')
			->willThrowException(new NoUserException());

		$this->userManager->expects($this->any())
			->method('get')
			->willReturnCallback(function (string $uid) use ($users) {
				return $users[$uid] ?? null;
			});

		$group = $this->createGroup($gid);
		$group->expects($this->once())
			->method('searchUsers')
			->with('', null, 0)
			->willReturn(array_values($users));

		$this->groupManager
			->method('get')
			->with($gid)
			->willReturn($group);
		$this->groupManager->expects($this->any())
			->method('getUserGroups')
			->willReturn([$group]);

		/** @var \PHPUnit\Framework\MockObject\MockObject */
		$this->subAdminManager->expects($this->any())
			->method('isSubAdminOfGroup')
			->willReturn(false);
		$this->subAdminManager->expects($this->any())
			->method('getSubAdminsGroups')
			->willReturn([]);


		$this->api->getGroupUsersDetails($gid);
	}

	public function testGetGroupUsersDetailsEncoded(): void {
		$gid = 'Department A/B C/D';

		$this->asAdmin();

		$users = [
			'ncu1' => $this->createUser('ncu1'), # regular
			'ncu2' => $this->createUser('ncu2'), # the zombie
		];
		$users['ncu2']->expects($this->atLeastOnce())
			->method('getHome')
			->willThrowException(new NoUserException());

		$this->userManager->expects($this->any())
			->method('get')
			->willReturnCallback(function (string $uid) use ($users) {
				return $users[$uid] ?? null;
			});

		$group = $this->createGroup($gid);
		$group->expects($this->once())
			->method('searchUsers')
			->with('', null, 0)
			->willReturn(array_values($users));

		$this->groupManager
			->method('get')
			->with($gid)
			->willReturn($group);
		$this->groupManager->expects($this->any())
			->method('getUserGroups')
			->willReturn([$group]);

		/** @var \PHPUnit\Framework\MockObject\MockObject */
		$this->subAdminManager->expects($this->any())
			->method('isSubAdminOfGroup')
			->willReturn(false);
		$this->subAdminManager->expects($this->any())
			->method('getSubAdminsGroups')
			->willReturn([]);


		$this->api->getGroupUsersDetails(urlencode($gid));
	}
}
