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

namespace OCA\Provisioning_API\Tests;

use OCA\Provisioning_API\Groups;
use OCP\API;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\IRequest;

class GroupsTest extends \Test\TestCase {
	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;
	/** @var \OC\SubAdmin|\PHPUnit_Framework_MockObject_MockObject */
	protected $subAdminManager;
	/** @var Groups */
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

		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->api = new Groups(
			$this->groupManager,
			$this->userSession,
			$this->request
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
		$user = $this->getMockBuilder('\OCP\IUser')->disableOriginalConstructor()->getMock();
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
		$this->request
			->expects($this->exactly(3))
			->method('getParam')
			->will($this->returnValueMap([
				['search', '', $search],
				['limit', null, $limit],
				['offset', null, $offset],
			]));

		$groups = [$this->createGroup('group1'), $this->createGroup('group2')];

		$search = $search === null ? '' : $search;

		$this->groupManager
			->expects($this->once())
			->method('search')
			->with($search, $limit, $offset)
			->willReturn($groups);

		$result = $this->api->getGroups([]);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(['group1', 'group2'], $result->getData()['groups']);
	}

	public function testGetGroupAsUser() {
		$result = $this->api->getGroup([]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(API::RESPOND_UNAUTHORISED, $result->getStatusCode());

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

		$result = $this->api->getGroup([
			'groupid' => 'group',
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(1, sizeof($result->getData()), 'Asserting the result data array only has the "users" key');
		$this->assertArrayHasKey('users', $result->getData());
		$this->assertEquals(['user1', 'user2'], $result->getData()['users']);
	}

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

		$result = $this->api->getGroup([
			'groupid' => 'group',
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(API::RESPOND_UNAUTHORISED, $result->getStatusCode());
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

		$result = $this->api->getGroup([
			'groupid' => 'group',
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals(1, sizeof($result->getData()), 'Asserting the result data array only has the "users" key');
		$this->assertArrayHasKey('users', $result->getData());
		$this->assertEquals(['user1', 'user2'], $result->getData()['users']);
	}

	public function testGetGroupNonExisting() {
		$this->asUser();

		$result = $this->api->getGroup([
			'groupid' => $this->getUniqueID()
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(API::RESPOND_NOT_FOUND, $result->getStatusCode());
		$this->assertEquals('The requested group could not be found', $result->getMeta()['message']);
	}

	public function testGetSubAdminsOfGroupsNotExists() {
		$result = $this->api->getSubAdminsOfGroup([
			'groupid' => 'NonExistingGroup',
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		$this->assertEquals('Group does not exist', $result->getMeta()['message']);
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

		$result = $this->api->getSubAdminsOfGroup([
			'groupid' => 'GroupWithSubAdmins',
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
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

		$result = $this->api->getSubAdminsOfGroup([
			'groupid' => 'GroupWithOutSubAdmins',
		]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
		$this->assertEquals([], $result->getData());
	}

	public function testAddGroupEmptyGroup() {
		$this->request
			->method('getParam')
			->with('groupid')
			->willReturn('');

		$result = $this->api->addGroup([]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
		$this->assertEquals('Invalid group name', $result->getMeta()['message']);
	}

	public function testAddGroupExistingGroup() {
		$this->request
			->method('getParam')
			->with('groupid')
			->willReturn('ExistingGroup');

		$this->groupManager
			->method('groupExists')
			->with('ExistingGroup')
			->willReturn(true);

		$result = $this->api->addGroup([]);

		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
	}

	public function testAddGroup() {
		$this->request
			->method('getParam')
			->with('groupid')
			->willReturn('NewGroup');

		$this->groupManager
			->method('groupExists')
			->with('NewGroup')
			->willReturn(false);

		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup');

		$result = $this->api->addGroup([]);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testAddGroupWithSpecialChar() {
		$this->request
			->method('getParam')
			->with('groupid')
			->willReturn('Iñtërnâtiônàlizætiøn');

		$this->groupManager
			->method('groupExists')
			->with('Iñtërnâtiônàlizætiøn')
			->willReturn(false);

		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('Iñtërnâtiônàlizætiøn');

		$result = $this->api->addGroup([]);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testDeleteGroupNonExisting() {
		$result = $this->api->deleteGroup([
			'groupid' => 'NonExistingGroup'
		]);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}

	public function testDeleteAdminGroup() {
		$this->groupManager
			->method('groupExists')
			->with('admin')
			->willReturn('true');

		$result = $this->api->deleteGroup([
			'groupid' => 'admin'
		]);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(102, $result->getStatusCode());
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

		$result = $this->api->deleteGroup([
			'groupid' => 'ExistingGroup',
		]);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
	}
}
