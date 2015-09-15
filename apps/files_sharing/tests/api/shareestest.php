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

use Doctrine\DBAL\Connection;
use OC\Share\Constants;
use OCA\Files_Sharing\API\Sharees;
use OCA\Files_sharing\Tests\TestCase;
use OCP\Share;

class ShareesTest extends TestCase {
	/** @var Sharees */
	protected $sharees;

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

	/** @var \OCP\IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var \OCP\Contacts\IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $contactsManager;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();

		$this->contactsManager = $this->getMockBuilder('OCP\Contacts\IManager')
			->disableOriginalConstructor()
			->getMock();

		$this->session = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();

		$this->sharees = new Sharees(
			$this->groupManager,
			$this->userManager,
			$this->contactsManager,
			$this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock(),
			$this->session,
			$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
			$this->request,
			$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock()
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
			['test', false, [], [], [], [], true, false],
			['test', true, [], [], [], [], true, false],
			[
				'test', false, [], [],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test', true, [], [],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test',
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
				true,
				false,
			],
			[
				'test',
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				false,
				[],
				[
					$this->getUserMock('test0', 'Test'),
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test0']],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, ['test1' => 'Test One']],
					['xyz', 'test', 2, 0, []],
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
				true,
				false,
			],
			[
				'test',
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
					['xyz', 'test', 2, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, [
						'test' => 'Test One',
					]],
					['xyz', 'test', 2, 0, [
						'test2' => 'Test Two',
					]],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				],
				[
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
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
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 * @param mixed $singleUser
	 */
	public function testGetUsers($searchTerm, $shareWithGroupOnly, $groupResponse, $userResponse, $exactExpected, $expected, $reachedEnd, $singleUser) {
		$this->invokePrivate($this->sharees, 'limit', [2]);
		$this->invokePrivate($this->sharees, 'offset', [0]);
		$this->invokePrivate($this->sharees, 'shareWithGroupOnly', [$shareWithGroupOnly]);

		$user = $this->getUserMock('admin', 'Administrator');
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($user);

		if (!$shareWithGroupOnly) {
			$this->userManager->expects($this->once())
				->method('searchDisplayName')
				->with($searchTerm, $this->invokePrivate($this->sharees, 'limit'), $this->invokePrivate($this->sharees, 'offset'))
				->willReturn($userResponse);
		} else {
			$this->groupManager->expects($this->once())
				->method('getUserGroupIds')
				->with($user)
				->willReturn($groupResponse);

			$this->groupManager->expects($this->exactly(sizeof($groupResponse)))
				->method('displayNamesInGroup')
				->with($this->anything(), $searchTerm, $this->invokePrivate($this->sharees, 'limit'), $this->invokePrivate($this->sharees, 'offset'))
				->willReturnMap($userResponse);
		}

		if ($singleUser !== false) {
			$this->userManager->expects($this->once())
				->method('get')
				->with($searchTerm)
				->willReturn($singleUser);
		}

		$this->invokePrivate($this->sharees, 'getUsers', [$searchTerm]);
		$result = $this->invokePrivate($this->sharees, 'result');

		$this->assertEquals($exactExpected, $result['exact']['users']);
		$this->assertEquals($expected, $result['users']);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	public function dataGetGroups() {
		return [
			['test', false, [], [], [], [], true, false],
			[
				'test', false,
				[$this->getGroupMock('test1')],
				[],
				[],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				true,
				false,
			],
			[
				'test', false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				null,
			],
			[
				'test', false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[
					['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']],
				],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				$this->getGroupMock('test'),
			],
			['test', true, [], [], [], [], true, false],
			[
				'test', true,
				[
					$this->getGroupMock('test1'),
					$this->getGroupMock('test2'),
				],
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test')],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				false,
				false,
			],
			[
				'test', true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				null,
			],
			[
				'test', true,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[
					['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']],
				],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				$this->getGroupMock('test'),
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
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 * @param mixed $singleGroup
	 */
	public function testGetGroups($searchTerm, $shareWithGroupOnly, $groupResponse, $userGroupsResponse, $exactExpected, $expected, $reachedEnd, $singleGroup) {
		$this->invokePrivate($this->sharees, 'limit', [2]);
		$this->invokePrivate($this->sharees, 'offset', [0]);
		$this->invokePrivate($this->sharees, 'shareWithGroupOnly', [$shareWithGroupOnly]);

		$this->groupManager->expects($this->once())
			->method('search')
			->with($searchTerm, $this->invokePrivate($this->sharees, 'limit'), $this->invokePrivate($this->sharees, 'offset'))
			->willReturn($groupResponse);

		if ($singleGroup !== false) {
			$this->groupManager->expects($this->once())
				->method('get')
				->with($searchTerm)
				->willReturn($singleGroup);
		}

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

		$this->invokePrivate($this->sharees, 'getGroups', [$searchTerm]);
		$result = $this->invokePrivate($this->sharees, 'result');

		$this->assertEquals($exactExpected, $result['exact']['groups']);
		$this->assertEquals($expected, $result['groups']);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	public function dataGetRemote() {
		return [
			['test', [], [], [], true],
			[
				'test@remote',
				[],
				[
					['label' => 'test@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']],
				],
				[],
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				[],
				[
					['label' => 'User @ Localhost', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost']],
				],
				true,
			],
			[
				'test@remote',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				[
					['label' => 'test@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']],
				],
				[
					['label' => 'User @ Localhost', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost']],
				],
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				[
					['label' => 'User @ Localhost', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost']],
				],
				[
				],
				true,
			],
		];
	}

	/**
	 * @dataProvider dataGetRemote
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 */
	public function testGetRemote($searchTerm, $contacts, $exactExpected, $expected, $reachedEnd) {
		$this->contactsManager->expects($this->any())
			->method('search')
			->with($searchTerm, ['CLOUD', 'FN'])
			->willReturn($contacts);

		$this->invokePrivate($this->sharees, 'getRemote', [$searchTerm]);
		$result = $this->invokePrivate($this->sharees, 'result');

		$this->assertEquals($exactExpected, $result['exact']['remotes']);
		$this->assertEquals($expected, $result['remotes']);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	public function dataSearch() {
		$allTypes = [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE];

		return [
			[[], '', true, '', null, $allTypes, 1, 200, false],

			// Test itemType
			[[
				'search' => '',
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'search' => 'foobar',
			], '', true, 'foobar', null, $allTypes, 1, 200, false],
			[[
				'search' => 0,
			], '', true, '0', null, $allTypes, 1, 200, false],

			// Test itemType
			[[
				'itemType' => '',
			], '', true, '', '', $allTypes, 1, 200, false],
			[[
				'itemType' => 'folder',
			], '', true, '', 'folder', $allTypes, 1, 200, false],
			[[
				'itemType' => 0,
			], '', true, '', '0', $allTypes, 1, 200, false],

			// Test shareType
			[[
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'shareType' => 0,
			], '', true, '', null, [0], 1, 200, false],
			[[
				'shareType' => '0',
			], '', true, '', null, [0], 1, 200, false],
			[[
				'shareType' => 1,
			], '', true, '', null, [1], 1, 200, false],
			[[
				'shareType' => 12,
			], '', true, '', null, [], 1, 200, false],
			[[
				'shareType' => 'foobar',
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'shareType' => [0, 1, 2],
			], '', true, '', null, [0, 1], 1, 200, false],
			[[
				'shareType' => [0, 1],
			], '', true, '', null, [0, 1], 1, 200, false],
			[[
				'shareType' => $allTypes,
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'shareType' => $allTypes,
			], '', false, '', null, [0, 1], 1, 200, false],

			// Test pagination
			[[
				'page' => 0,
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'page' => '0',
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'page' => -1,
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'page' => 1,
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'page' => 10,
			], '', true, '', null, $allTypes, 10, 200, false],

			// Test perPage
			[[
				'perPage' => 0,
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'perPage' => '0',
			], '', true, '', null, $allTypes, 1, 200, false],
			[[
				'perPage' => -1,
			], '', true, '', null, $allTypes, 1, 1, false],
			[[
				'perPage' => 1,
			], '', true, '', null, $allTypes, 1, 1, false],
			[[
				'perPage' => 10,
			], '', true, '', null, $allTypes, 1, 10, false],

			// Test $shareWithGroupOnly setting
			[[], 'no', true, '', null, $allTypes, 1, 200, false],
			[[], 'yes', true, '', null, $allTypes, 1, 200, true],

		];
	}

	/**
	 * @dataProvider dataSearch
	 *
	 * @param array $getData
	 * @param string $apiSetting
	 * @param bool $remoteSharingEnabled
	 * @param string $search
	 * @param string $itemType
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @param bool $shareWithGroupOnly
	 */
	public function testSearch($getData, $apiSetting, $remoteSharingEnabled, $search, $itemType, $shareTypes, $page, $perPage, $shareWithGroupOnly) {
		$oldGet = $_GET;
		$_GET = $getData;

		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_only_share_with_group_members', 'no')
			->willReturn($apiSetting);

		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\API\Sharees')
			->setConstructorArgs([
				$this->groupManager,
				$this->userManager,
				$this->contactsManager,
				$config,
				$this->session,
				$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock()
			])
			->setMethods(array('searchSharees', 'isRemoteSharingAllowed'))
			->getMock();
		$sharees->expects($this->once())
			->method('searchSharees')
			->with($search, $itemType, $shareTypes, $page, $perPage)
			->willReturnCallback(function
					($isearch, $iitemType, $ishareTypes, $ipage, $iperPage)
				use ($search, $itemType, $shareTypes, $page, $perPage) {

				// We are doing strict comparisons here, so we can differ 0/'' and null on shareType/itemType
				$this->assertSame($search, $isearch);
				$this->assertSame($itemType, $iitemType);
				$this->assertSame($shareTypes, $ishareTypes);
				$this->assertSame($page, $ipage);
				$this->assertSame($perPage, $iperPage);
				return new \OC_OCS_Result([]);
			});
		$sharees->expects($this->any())
			->method('isRemoteSharingAllowed')
			->with($itemType)
			->willReturn($remoteSharingEnabled);

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\API\Sharees $sharees */
		$this->assertInstanceOf('\OC_OCS_Result', $sharees->search());

		$this->assertSame($shareWithGroupOnly, $this->invokePrivate($sharees, 'shareWithGroupOnly'));

		$_GET = $oldGet;
	}

	public function dataIsRemoteSharingAllowed() {
		return [
			['file', true],
			['folder', true],
			['', false],
			['contacts', false],
		];
	}

	/**
	 * @dataProvider dataIsRemoteSharingAllowed
	 *
	 * @param string $itemType
	 * @param bool $expected
	 */
	public function testIsRemoteSharingAllowed($itemType, $expected) {
		$this->assertSame($expected, $this->invokePrivate($this->sharees, 'isRemoteSharingAllowed', [$itemType]));
	}

	public function dataSearchSharees() {
		return [
			['test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE], 1, 2, false, [], [], [],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [],
					'groups' => [],
					'remotes' => [],
				], false],
			['test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE], 1, 2, false, [], [], [],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [],
					'groups' => [],
					'remotes' => [],
				], false],
			[
				'test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], [
					['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [
						['label' => 'testgroup1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
					],
					'remotes' => [
						['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
					],
				], true,
			],
			// No groups requested
			[
				'test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_REMOTE], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], null, [
					['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [],
					'remotes' => [
						['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
					],
				], false,
			],
			// Share type restricted to user - Only one user
			[
				'test', 'folder', [Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], null, null,
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [],
					'remotes' => [],
				], false,
			],
			// Share type restricted to user - Multipage result
			[
				'test', 'folder', [Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test 1', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'test 2', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				], null, null,
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => []],
					'users' => [
						['label' => 'test 1', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
						['label' => 'test 2', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
					],
					'groups' => [],
					'remotes' => [],
				], true,
			],
		];
	}

	/**
	 * @dataProvider dataSearchSharees
	 *
	 * @param string $searchTerm
	 * @param string $itemType
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @param bool $shareWithGroupOnly
	 * @param array $mockedUserResult
	 * @param array $mockedGroupsResult
	 * @param array $mockedRemotesResult
	 * @param array $expected
	 * @param bool $nextLink
	 */
	public function testSearchSharees($searchTerm, $itemType, array $shareTypes, $page, $perPage, $shareWithGroupOnly,
									  $mockedUserResult, $mockedGroupsResult, $mockedRemotesResult, $expected, $nextLink) {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\API\Sharees $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\API\Sharees')
			->setConstructorArgs([
				$this->groupManager,
				$this->userManager,
				$this->contactsManager,
				$this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock(),
				$this->session,
				$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock()
			])
			->setMethods(array('getShareesForShareIds', 'getUsers', 'getGroups', 'getRemote'))
			->getMock();
		$sharees->expects(($mockedUserResult === null) ? $this->never() : $this->once())
			->method('getUsers')
			->with($searchTerm)
			->willReturnCallback(function() use ($sharees, $mockedUserResult) {
				$result = $this->invokePrivate($sharees, 'result');
				$result['users'] = $mockedUserResult;
				$this->invokePrivate($sharees, 'result', [$result]);
			});
		$sharees->expects(($mockedGroupsResult === null) ? $this->never() : $this->once())
			->method('getGroups')
			->with($searchTerm)
			->willReturnCallback(function() use ($sharees, $mockedGroupsResult) {
				$result = $this->invokePrivate($sharees, 'result');
				$result['groups'] = $mockedGroupsResult;
				$this->invokePrivate($sharees, 'result', [$result]);
			});
		$sharees->expects(($mockedRemotesResult === null) ? $this->never() : $this->once())
			->method('getRemote')
			->with($searchTerm)
			->willReturnCallback(function() use ($sharees, $mockedRemotesResult) {
				$result = $this->invokePrivate($sharees, 'result');
				$result['remotes'] = $mockedRemotesResult;
				$this->invokePrivate($sharees, 'result', [$result]);
			});

		/** @var \OC_OCS_Result $ocs */
		$ocs = $this->invokePrivate($sharees, 'searchSharees', [$searchTerm, $itemType, $shareTypes, $page, $perPage, $shareWithGroupOnly]);
		$this->assertInstanceOf('\OC_OCS_Result', $ocs);
		$this->assertEquals($expected, $ocs->getData());

		// Check if next link is set
		if ($nextLink) {
			$headers = $ocs->getHeaders();
			$this->assertArrayHasKey('Link', $headers);
			$this->assertStringStartsWith('<', $headers['Link']);
			$this->assertStringEndsWith('>; rel="next"', $headers['Link']);
		}
	}

	public function testSearchShareesNoItemType() {
		/** @var \OC_OCS_Result $ocs */
		$ocs = $this->invokePrivate($this->sharees, 'searchSharees', ['', null, [], [], 0, 0, false]);
		$this->assertInstanceOf('\OC_OCS_Result', $ocs);

		$this->assertSame(400, $ocs->getStatusCode(), 'Expected status code 400');
		$this->assertSame([], $ocs->getData(), 'Expected that no data is send');

		$meta = $ocs->getMeta();
		$this->assertNotEmpty($meta);
		$this->assertArrayHasKey('message', $meta);
		$this->assertSame('missing itemType', $meta['message']);
	}

	public function dataGetPaginationLink() {
		return [
			[1, '/ocs/v1.php', ['perPage' => 2], '<?perPage=2&page=2>; rel="next"'],
			[10, '/ocs/v2.php', ['perPage' => 2], '<?perPage=2&page=11>; rel="next"'],
		];
	}

	/**
	 * @dataProvider dataGetPaginationLink
	 *
	 * @param int $page
	 * @param string $scriptName
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetPaginationLink($page, $scriptName, $params, $expected) {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'getPaginationLink', [$page, $params]));
	}

	public function dataIsV2() {
		return [
			['/ocs/v1.php', false],
			['/ocs/v2.php', true],
		];
	}

	/**
	 * @dataProvider dataIsV2
	 *
	 * @param string $scriptName
	 * @param bool $expected
	 */
	public function testIsV2($scriptName, $expected) {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'isV2'));
	}
}
