<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Provisioning_API\Tests\Controller;

use OCA\Provisioning_API\Controller\GroupsController;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class GroupsControllerTest extends \Test\TestCase {
	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;
	/** @var \OC\SubAdmin|\PHPUnit_Framework_MockObject_MockObject */
	protected $subAdminManager;

	/** @var GroupsController */
	protected $api;

	protected function setUp() {
		parent::setUp();

		$this->subAdminManager = $this->getMockBuilder('OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = $this->getMockBuilder('OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager
			->method('getSubAdmin')
			->willReturn($this->subAdminManager);

		$this->userSession = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->getMock();
		$request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$logger = $this->createMock(ILogger::class);

		$this->api = new GroupsController(
			'provisioning_api',
			$request,
			$this->groupManager,
			$this->userSession,
			$logger
		);
	}

	/**
	 * @param string $gid
	 * @return \OCP\IGroup|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function createGroup($gid) {
		$group = $this->getMockBuilder('\OCP\IGroup')->disableOriginalConstructor()->getMock();
		$group
			->method('getGID')
			->willReturn($gid);
		return $group;
	}

	/**
	 * @param string $uid
	 * @return \OCP\IUser|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function createUser($uid) {
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$user
			->method('getUID')
			->willReturn($uid);
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
			->will($this->returnCallback(function($_user, $_group) use ($user, $group) {
				if ($_user === $user && $_group === $group) {
					return true;
				}
				return false;
			}));
	}

	public function dataGetGroups() {
		return [
			[null, null, null],
			['foo', null, null],
			[null, 1, null],
			[null, null, 2],
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
	public function testGetGroups($search, $limit, $offset) {
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

	public function testGetGroupAsSubadmin() {
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

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 997
	 */
	public function testGetGroupAsIrrelevantSubadmin() {
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

	public function testGetGroupAsAdmin() {
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

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 998
	 * @expectedExceptionMessage The requested group could not be found
	 */
	public function testGetGroupNonExisting() {
		$this->asUser();

		$this->api->getGroup($this->getUniqueID());
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage Group does not exist
	 */
	public function testGetSubAdminsOfGroupsNotExists() {
		$this->api->getSubAdminsOfGroup('NonExistingGroup');
	}

	public function testGetSubAdminsOfGroup() {
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

	public function testGetSubAdminsOfGroupEmptyList() {
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

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 * @expectedExceptionMessage Invalid group name
	 */
	public function testAddGroupEmptyGroup() {
		$this->api->addGroup('');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 */
	public function testAddGroupExistingGroup() {
		$this->groupManager
			->method('groupExists')
			->with('ExistingGroup')
			->willReturn(true);

		$this->api->addGroup('ExistingGroup');
	}

	public function testAddGroup() {
		$this->groupManager
			->method('groupExists')
			->with('NewGroup')
			->willReturn(false);

		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup');

		$this->api->addGroup('NewGroup');
	}

	public function testAddGroupWithSpecialChar() {
		$this->groupManager
			->method('groupExists')
			->with('Iñtërnâtiônàlizætiøn')
			->willReturn(false);

		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('Iñtërnâtiônàlizætiøn');

		$this->api->addGroup('Iñtërnâtiônàlizætiøn');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 101
	 */
	public function testDeleteGroupNonExisting() {
		$this->api->deleteGroup('NonExistingGroup');
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSException
	 * @expectedExceptionCode 102
	 */
	public function testDeleteAdminGroup() {
		$this->groupManager
			->method('groupExists')
			->with('admin')
			->willReturn('true');

		$this->api->deleteGroup('admin');
	}

	public function testDeleteGroup() {
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
}
