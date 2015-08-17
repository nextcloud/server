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

		$this->sharees = new Sharees(
			$this->groupManager,
			$this->userManager,
			$this->contactsManager,
			$this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock(),
			$this->session,
			$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
			\OC::$server->getDatabaseConnection()
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
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
					$this->getUserMock('admin', 'Should be removed'),
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
					['admin', 'Should be removed', -1, 0, [
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
		$user = $this->getUserMock('admin', 'Administrator');
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($user);

		if (!$shareWithGroupOnly) {
			$this->userManager->expects($this->once())
				->method('searchDisplayName')
				->with($searchTerm)
				->willReturn($userResponse);
		} else {
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

	public function dataGetRemote() {
		return [
			['test', [], []],
			[
				'test@remote',
				[],
				[
					['label' => 'test@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']],
				],
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
				[
					['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost']],
				],
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
					['label' => 'test@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']],
					['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost']],
				],
			],
		];
	}

	/**
	 * @dataProvider dataGetRemote
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param array $expected
	 */
	public function testGetRemote($searchTerm, $contacts, $expected) {
		$this->contactsManager->expects($this->any())
			->method('search')
			->with($searchTerm, ['CLOUD', 'FN'])
			->willReturn($contacts);

		$users = $this->invokePrivate($this->sharees, 'getRemote', [$searchTerm]);

		$this->assertEquals($expected, $users);
	}

	public function dataSearch() {
		$allTypes = [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_REMOTE];

		return [
			[[], '', true, [], '', null, $allTypes, 1, 200, false],

			// Test itemType
			[[
				'search' => '',
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'search' => 'foobar',
			], '', true, [], 'foobar', null, $allTypes, 1, 200, false],
			[[
				'search' => 0,
			], '', true, [], '0', null, $allTypes, 1, 200, false],

			// Test itemType
			[[
				'itemType' => '',
			], '', true, [], '', '', $allTypes, 1, 200, false],
			[[
				'itemType' => 'folder',
			], '', true, [], '', 'folder', $allTypes, 1, 200, false],
			[[
				'itemType' => 0,
			], '', true, [], '', '0', $allTypes, 1, 200, false],

			// Test existingShares
			[[
				'existingShares' => [],
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'existingShares' => [12, 42],
			], '', true, [12, 42], '', null, $allTypes, 1, 200, false],

			// Test shareType
			[[
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'shareType' => 0,
			], '', true, [], '', null, [0], 1, 200, false],
			[[
				'shareType' => '0',
			], '', true, [], '', null, [0], 1, 200, false],
			[[
				'shareType' => 1,
			], '', true, [], '', null, [1], 1, 200, false],
			[[
				'shareType' => 12,
			], '', true, [], '', null, [], 1, 200, false],
			[[
				'shareType' => 'foobar',
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'shareType' => [0, 1, 2],
			], '', true, [], '', null, [0, 1], 1, 200, false],
			[[
				'shareType' => [0, 1],
			], '', true, [], '', null, [0, 1], 1, 200, false],
			[[
				'shareType' => $allTypes,
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'shareType' => $allTypes,
			], '', false, [], '', null, [0, 1], 1, 200, false],

			// Test pagination
			[[
				'page' => 0,
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'page' => '0',
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'page' => -1,
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'page' => 1,
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'page' => 10,
			], '', true, [], '', null, $allTypes, 10, 200, false],

			// Test limit
			[[
				'limit' => 0,
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'limit' => '0',
			], '', true, [], '', null, $allTypes, 1, 200, false],
			[[
				'limit' => -1,
			], '', true, [], '', null, $allTypes, 1, 1, false],
			[[
				'limit' => 1,
			], '', true, [], '', null, $allTypes, 1, 1, false],
			[[
				'limit' => 10,
			], '', true, [], '', null, $allTypes, 1, 10, false],

			// Test $shareWithGroupOnly setting
			[[], 'no', true, [], '', null, $allTypes, 1, 200, false],
			[[], 'yes', true, [], '', null, $allTypes, 1, 200, true],

		];
	}

	/**
	 * @dataProvider dataSearch
	 *
	 * @param array $getData
	 * @param string $apiSetting
	 * @param bool $remoteSharingEnabled
	 * @param array $shareIds
	 * @param string $search
	 * @param string $itemType
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @param bool $shareWithGroupOnly
	 */
	public function testSearch($getData, $apiSetting, $remoteSharingEnabled, $shareIds, $search, $itemType, $shareTypes, $page, $perPage, $shareWithGroupOnly) {
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
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
				\OC::$server->getDatabaseConnection()
			])
			->setMethods(array('searchSharees', 'isRemoteSharingAllowed'))
			->getMock();
		$sharees->expects($this->once())
			->method('searchSharees')
			->with($search, $itemType, $shareIds, $shareTypes, $page, $perPage, $shareWithGroupOnly)
			->willReturnCallback(function
					($isearch, $iitemType, $ishareIds, $ishareTypes, $ipage, $iperPage, $ishareWithGroupOnly)
				use ($search, $itemType, $shareIds, $shareTypes, $page, $perPage, $shareWithGroupOnly) {

				// We are doing strict comparisons here, so we can differ 0/'' and null on shareType/itemType
				$this->assertSame($search, $isearch);
				$this->assertSame($itemType, $iitemType);
				$this->assertSame($shareIds, $ishareIds);
				$this->assertSame($shareTypes, $ishareTypes);
				$this->assertSame($page, $ipage);
				$this->assertSame($perPage, $iperPage);
				$this->assertSame($shareWithGroupOnly, $ishareWithGroupOnly);
				return new \OC_OCS_Result([]);
			});
		$sharees->expects($this->any())
			->method('isRemoteSharingAllowed')
			->with($itemType)
			->willReturn($remoteSharingEnabled);

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\API\Sharees $sharees */
		$this->assertInstanceOf('\OC_OCS_Result', $sharees->search());

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
			['test', 'folder', [], [], [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_REMOTE], 1, 2, false, [], [], [], [], 0, false],
			['test', 'folder', [1, 2], [0 => ['test1'], 1 => ['test2 group']], [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_REMOTE], 1, 2, false, [], [], [], [], 0, false],
			// First page with 2 of 3 results
			[
				'test', 'folder', [], [], [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_REMOTE], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], [
					['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				], [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'testgroup1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], 3, true,
			],
			// Second page with the 3rd result
			[
				'test', 'folder', [], [], [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_REMOTE], 2, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], [
					['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				], [
					['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				], 3, false,
			],
			// No groups requested
			[
				'test', 'folder', [], [], [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_REMOTE], 1, 2, false, [
				['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
			], null, [
				['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
			], [
				['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
			], 2, false,
			],
			// Ingnoring already shared user
			[
				'test', 'folder', [1], [\OCP\Share::SHARE_TYPE_USER => ['test1']], [\OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_REMOTE], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], [
					['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
					['label' => 'testz@remote', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
				], 2, false,
			],
			// Share type restricted to user - Only one user
			[
				'test', 'folder', [], [], [\OCP\Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], null, null, [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], 1, false,
			],
			// Share type restricted to user - Multipage result
			[
				'test', 'folder', [], [], [\OCP\Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test 1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'test 2', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
					['label' => 'test 3', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test3']],
				], null, null, [
					['label' => 'test 1', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'test 2', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				], 3, true,
			],
			// Share type restricted to user - Only user already shared
			[
				'test', 'folder', [1], [\OCP\Share::SHARE_TYPE_USER => ['test1']], [\OCP\Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], null, null, [], 0, false,
			],
		];
	}

	/**
	 * @dataProvider dataSearchSharees
	 *
	 * @param string $searchTerm
	 * @param string $itemType
	 * @param array $shareIds
	 * @param array $existingShares
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @param bool $shareWithGroupOnly
	 * @param array $expected
	 */
	public function testSearchSharees($searchTerm, $itemType, array $shareIds, array $existingShares, array $shareTypes, $page, $perPage, $shareWithGroupOnly,
									  $mockedUserResult, $mockedGroupsResult, $mockedRemotesResult, $expected, $totalItems, $nextLink) {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\API\Sharees $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\API\Sharees')
			->setConstructorArgs([
				$this->groupManager,
				$this->userManager,
				$this->contactsManager,
				$this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock(),
				$this->session,
				$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
				\OC::$server->getDatabaseConnection()
			])
			->setMethods(array('getShareesForShareIds', 'getUsers', 'getGroups', 'getRemote'))
			->getMock();
		$sharees->expects($this->once())
			->method('getShareesForShareIds')
			->with($shareIds)
			->willReturn($existingShares);
		$sharees->expects(($mockedUserResult === null) ? $this->never() : $this->once())
			->method('getUsers')
			->with($searchTerm, $shareWithGroupOnly)
			->willReturn($mockedUserResult);
		$sharees->expects(($mockedGroupsResult === null) ? $this->never() : $this->once())
			->method('getGroups')
			->with($searchTerm, $shareWithGroupOnly)
			->willReturn($mockedGroupsResult);
		$sharees->expects(($mockedRemotesResult === null) ? $this->never() : $this->once())
			->method('getRemote')
			->with($searchTerm)
			->willReturn($mockedRemotesResult);

		/** @var \OC_OCS_Result $ocs */
		$ocs = $this->invokePrivate($sharees, 'searchSharees', [$searchTerm, $itemType, $shareIds, $shareTypes, $page, $perPage, $shareWithGroupOnly]);
		$this->assertInstanceOf('\OC_OCS_Result', $ocs);

		$this->assertEquals($expected, $ocs->getData());

		// Check number of total results
		$meta = $ocs->getMeta();
		$this->assertArrayHasKey('totalitems', $meta);
		$this->assertSame($totalItems, $meta['totalitems']);

		// Check if next link is set
		if ($nextLink) {
			$headers = $ocs->getHeaders();
			$this->assertArrayHasKey('Link', $headers);
			$this->assertStringStartsWith('<', $headers['Link']);
			$this->assertStringEndsWith('"', $headers['Link']);
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

	public function dataFilterSharees() {
		return [
			[[], [], []],
			[
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
			],
			[
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				['test1'],
				[
					1 => ['label' => 'Test Two', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
			],
			[
				[
					['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				['test2'],
				[
					0 => ['label' => 'Test One', 'value' => ['shareType' => \OCP\Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
			],
		];
	}

	/**
	 * @dataProvider dataFilterSharees
	 *
	 * @param array $potentialSharees
	 * @param array $existingSharees
	 * @param array $expectedSharees
	 */
	public function testFilterSharees($potentialSharees, $existingSharees, $expectedSharees) {
		$this->assertEquals($expectedSharees, $this->invokePrivate($this->sharees, 'filterSharees', [$potentialSharees, $existingSharees]));
	}

	public function dataGetShareesForShareIds() {
		return [
			[[], []],
			[[1, 2, 3], [Constants::SHARE_TYPE_USER => ['user1'], Constants::SHARE_TYPE_GROUP => ['group1']]],
		];
	}

	/**
	 * @dataProvider dataGetShareesForShareIds
	 *
	 * @param array $shareIds
	 * @param array $expectedSharees
	 */
	public function testGetShareesForShareIds(array $shareIds, array $expectedSharees) {
		$owner = $this->getUniqueID('test');
		$shares2delete = [];

		if (!empty($shareIds)) {
			$userShare = $this->createShare(Constants::SHARE_TYPE_USER, 'user1', $owner, null);
			$shares2delete[] = $userShare;
			$shares2delete[] = $this->createShare(Constants::SHARE_TYPE_USER, 'user3', $owner . '2', null);

			$groupShare = $this->createShare(Constants::SHARE_TYPE_GROUP, 'group1', $owner, null);
			$shares2delete[] = $groupShare;
			$groupShare2 = $this->createShare(Constants::SHARE_TYPE_GROUP, 'group2', $owner . '2', null);
			$shares2delete[] = $groupShare2;

			$shares2delete[] = $this->createShare($this->invokePrivate(new Constants(), 'shareTypeGroupUserUnique'), 'user2', $owner, $groupShare);
			$shares2delete[] = $this->createShare($this->invokePrivate(new Constants(), 'shareTypeGroupUserUnique'), 'user4', $owner, $groupShare2);
		}

		$user = $this->getUserMock($owner, 'Sharee OCS test user');
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$this->assertEquals($expectedSharees, $this->invokePrivate($this->sharees, 'getShareesForShareIds', [$shares2delete]));

		$this->deleteShares($shares2delete);
	}

	/**
	 * @param int $type
	 * @param string $with
	 * @param string $owner
	 * @param int $parent
	 * @return int
	 */
	protected function createShare($type, $with, $owner, $parent) {
		$connection = \OC::$server->getDatabaseConnection();
		$queryBuilder = $connection->getQueryBuilder();
		$queryBuilder->insert('share')
			->values([
				'share_type'	=> $queryBuilder->createParameter('share_type'),
				'share_with'	=> $queryBuilder->createParameter('share_with'),
				'uid_owner'		=> $queryBuilder->createParameter('uid_owner'),
				'parent'		=> $queryBuilder->createParameter('parent'),
				'item_type'		=> $queryBuilder->expr()->literal('fake'),
				'item_source'	=> $queryBuilder->expr()->literal(''),
				'item_target'	=> $queryBuilder->expr()->literal(''),
				'file_source'	=> $queryBuilder->expr()->literal(0),
				'file_target'	=> $queryBuilder->expr()->literal(''),
				'permissions'	=> $queryBuilder->expr()->literal(0),
				'stime'			=> $queryBuilder->expr()->literal(0),
				'accepted'		=> $queryBuilder->expr()->literal(0),
				'expiration'	=> $queryBuilder->expr()->literal(''),
				'token'			=> $queryBuilder->expr()->literal(''),
				'mail_send'		=> $queryBuilder->expr()->literal(0),
			])
			->setParameters([
				'share_type'	=> $type,
				'share_with'	=> $with,
				'uid_owner'		=> $owner,
				'parent'		=> $parent,
			])
			->execute();
		return $connection->lastInsertId('share');
	}

	/**
	 * @param int[] $shareIds
	 * @return null
	 */
	protected function deleteShares(array $shareIds) {
		$connection = \OC::$server->getDatabaseConnection();
		$queryBuilder = $connection->getQueryBuilder();
		$queryBuilder->delete('share')
			->where($queryBuilder->expr()->in('id', $queryBuilder->createParameter('ids')))
			->setParameter('ids', $shareIds, Connection::PARAM_INT_ARRAY)
			->execute();
	}

	public function dataGetPaginationLinks() {
		return [
			[1, 1, ['limit' => 2], []],
			[1, 3, ['limit' => 2], [
				'<?limit=2&page=2>; rel="next"',
				'<?limit=2&page=2>; rel="last"',
			]],
			[1, 21, ['limit' => 2], [
				'<?limit=2&page=2>; rel="next"',
				'<?limit=2&page=11>; rel="last"',
			]],
			[2, 21, ['limit' => 2], [
				'<?limit=2&page=1>; rel="first"',
				'<?limit=2&page=1>; rel="prev"',
				'<?limit=2&page=3>; rel="next"',
				'<?limit=2&page=11>; rel="last"',
			]],
			[5, 21, ['limit' => 2], [
				'<?limit=2&page=1>; rel="first"',
				'<?limit=2&page=4>; rel="prev"',
				'<?limit=2&page=6>; rel="next"',
				'<?limit=2&page=11>; rel="last"',
			]],
			[10, 21, ['limit' => 2], [
				'<?limit=2&page=1>; rel="first"',
				'<?limit=2&page=9>; rel="prev"',
				'<?limit=2&page=11>; rel="next"',
				'<?limit=2&page=11>; rel="last"',
			]],
			[11, 21, ['limit' => 2], [
				'<?limit=2&page=1>; rel="first"',
				'<?limit=2&page=10>; rel="prev"',
			]],
		];
	}

	/**
	 * @dataProvider dataGetPaginationLinks
	 *
	 * @param int $page
	 * @param int $total
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetPaginationLinks($page, $total, $params, $expected) {
		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'getPaginationLinks', [$page, $total, $params]));
	}
}
