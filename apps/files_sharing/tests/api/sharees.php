<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OCA\Files_Sharing\Tests\API;

use OCA\Files_Sharing\API\Sharees;
use OCA\Files_sharing\Tests\TestCase;

class ShareesTest extends TestCase {
	/** @var Sharees */
	protected $sharees;

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

	/** @var \OCP\IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();

		$this->session = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->sharees = new Sharees(
			$this->groupManager,
			$this->userManager,
			$this->getMockBuilder('OCP\Contacts\IManager')->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder('OCP\IAppConfig')->disableOriginalConstructor()->getMock(),
			$this->session,
			$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock()
		);
	}

	protected function getUserMock($uid, $displayName) {
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);

		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);

		return $user;
	}

	protected function getGroupMock($gid) {
		$group = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();

		$group->expects($this->any())
			->method('getGID')
			->willReturn($gid);

		return $group;
	}

	public function dataGetUsers() {
		return [
			['test', false, [], [], []],
			['test', true, [], [], []],
			[
				'test',
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				]
			],
			[
				'test',
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				]
			],
			[
				'test',
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', -1, 0, ['test1' => 'Test One']],
					['xyz', 'test', -1, 0, []],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				]
			],
			[
				'test',
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', -1, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
					['xyz', 'test', -1, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				]
			],
			[
				'test',
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', -1, 0, [
						'test1' => 'Test One',
					]],
					['xyz', 'test', -1, 0, [
						'test2' => 'Test Two',
					]],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				]
			],
		];
	}

	/**
	 * @dataProvider dataGetUsers
	 *
	 * @param string $searchTerm
	 * @param bool $shareWithGroupOnly
	 * @param array $groupResponse
	 * @param array $userResponse
	 * @param array $expected
	 */
	public function testGetUsers($searchTerm, $shareWithGroupOnly, $groupResponse, $userResponse, $expected) {
		if (!$shareWithGroupOnly) {
			$this->userManager->expects($this->once())
				->method('searchDisplayName')
				->with($searchTerm)
				->willReturn($userResponse);
		} else {
			$user = $this->getUserMock('admin', 'Administrator');
			$this->session->expects($this->any())
				->method('getUser')
				->willReturn($user);

			$this->groupManager->expects($this->once())
				->method('getUserGroupIds')
				->with($user)
				->willReturn($groupResponse);

			$this->groupManager->expects($this->exactly(sizeof($groupResponse)))
				->method('displayNamesInGroup')
				->with($this->anything(), $searchTerm)
				->willReturnMap($userResponse);
		}

		$users = $this->invokePrivate($this->sharees, 'getUsers', [$searchTerm, $shareWithGroupOnly]);

		$this->assertEquals($expected, $users);
	}

	public function dataGetGroups() {
		return [
			['test', false, [], [], []],
			[
				'test', false,
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
			],
			['test', true, [], [], []],
			[
				'test', true,
				[
					$this->getGroupMock('test1'),
					$this->getGroupMock('test2'),
				],
				[$this->getGroupMock('test1')],
				[['label' => 'test1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
			],
		];
	}

	/**
	 * @dataProvider dataGetGroups
	 *
	 * @param string $searchTerm
	 * @param bool $shareWithGroupOnly
	 * @param array $groupResponse
	 * @param array $userGroupsResponse
	 * @param array $expected
	 */
	public function testGetGroups($searchTerm, $shareWithGroupOnly, $groupResponse, $userGroupsResponse, $expected) {
		$this->groupManager->expects($this->once())
			->method('search')
			->with($searchTerm)
			->willReturn($groupResponse);

		if ($shareWithGroupOnly) {
			$user = $this->getUserMock('admin', 'Administrator');
			$this->session->expects($this->any())
				->method('getUser')
				->willReturn($user);

			$numGetUserGroupsCalls = empty($groupResponse) ? 0 : 1;
			$this->groupManager->expects($this->exactly($numGetUserGroupsCalls))
				->method('getUserGroups')
				->with($user)
				->willReturn($userGroupsResponse);
		}

		$users = $this->invokePrivate($this->sharees, 'getGroups', [$searchTerm, $shareWithGroupOnly]);

		$this->assertEquals($expected, $users);
	}

//	public function testArguments() {
//
//	}
//
//	public function testsOnlyUsers() {
//
//	}
//
//	public function testOnlyGroups() {
//
//	}
//
//	public function testRemoteAddress() {
//
//	}
//
//	public function testRemoteFromContacts() {
//
//	}
//
//	public function testAll() {
//
//	}
//
//	public function testSorting() {
//
//	}
//
//	public function testPagination() {
//
//	}
//
//	public function testShareWithinGroup() {
//
//	}
}
