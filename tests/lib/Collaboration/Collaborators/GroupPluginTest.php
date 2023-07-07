<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\GroupPlugin;
use OC\Collaboration\Collaborators\SearchResult;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShare;
use Test\TestCase;

class GroupPluginTest extends TestCase {
	/** @var  IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var  IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $groupManager;

	/** @var  IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $session;

	/** @var  ISearchResult */
	protected $searchResult;

	/** @var  GroupPlugin */
	protected $plugin;

	/** @var int */
	protected $limit = 2;

	/** @var int */
	protected $offset = 0;

	/** @var  IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);

		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->session = $this->createMock(IUserSession::class);

		$this->searchResult = new SearchResult();

		$this->user = $this->getUserMock('admin', 'Administrator');
	}

	public function instantiatePlugin() {
		// cannot be done within setUp, because dependent mocks needs to be set
		// up with configuration etc. first
		$this->plugin = new GroupPlugin(
			$this->config,
			$this->groupManager,
			$this->session
		);
	}

	public function getUserMock($uid, $displayName) {
		$user = $this->createMock(IUser::class);

		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);

		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);

		return $user;
	}

	/**
	 * @param string $gid
	 * @param null $displayName
	 * @param bool $hide
	 * @return IGroup|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getGroupMock($gid, $displayName = null, $hide = false) {
		$group = $this->createMock(IGroup::class);

		$group->expects($this->any())
			->method('getGID')
			->willReturn($gid);

		if (is_null($displayName)) {
			// note: this is how the Group class behaves
			$displayName = $gid;
		}

		$group->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);

		$group->method('hideFromCollaboration')
			->willReturn($hide);

		return $group;
	}

	public function dataGetGroups(): array {
		return [
			['test', false, true, false, [], [], [], [], true, false],
			['test', false, false, false, [], [], [], [], true, false],
			// group sharing disabled
			['test', false, true, true, [], [], [], [], false, false],
			// group without display name
			[
				'test', false, true, false,
				[$this->getGroupMock('test1')],
				[],
				[],
				[['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				true,
				false,
			],
			// group with display name, search by id
			[
				'test', false, true, false,
				[$this->getGroupMock('test1', 'Test One')],
				[],
				[],
				[['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				true,
				false,
			],
			// group with display name, search by display name
			[
				'one', false, true, false,
				[$this->getGroupMock('test1', 'Test One')],
				[],
				[],
				[['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				true,
				false,
			],
			// group with display name, search by display name, exact expected
			[
				'Test One', false, true, false,
				[$this->getGroupMock('test1', 'Test One')],
				[],
				[['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				[],
				true,
				false,
			],
			[
				'test', false, false, false,
				[$this->getGroupMock('test1')],
				[],
				[],
				[],
				true,
				false,
			],
			[
				'test', false, true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[],
				[['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']]],
				[['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', false, false, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[],
				[['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				true,
				false,
			],
			[
				'test', false, true, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[],
				[
					['label' => 'test0', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				null,
			],
			[
				'test', false, false, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[],
				[],
				true,
				null,
			],
			[
				'test', false, true, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[
					['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']],
				],
				[
					['label' => 'test0', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				$this->getGroupMock('test'),
			],
			[
				'test', false, false, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[
					['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']],
				],
				[],
				true,
				$this->getGroupMock('test'),
			],
			['test', true, true, false, [], [], [], [], true, false],
			['test', true, false, false, [], [], [], [], true, false],
			[
				'test', true, true, false,
				[
					$this->getGroupMock('test1'),
					$this->getGroupMock('test2'),
				],
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true, false, false,
				[
					$this->getGroupMock('test1'),
					$this->getGroupMock('test2'),
				],
				[$this->getGroupMock('test1')],
				[],
				[],
				true,
				false,
			],
			[
				'test', true, true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test')],
				[['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				false,
				false,
			],
			[
				'test', true, false, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test')],
				[['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				true,
				false,
			],
			[
				'test', true, true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true, false, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test1')],
				[],
				[],
				true,
				false,
			],
			[
				'test', true, true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']]],
				[['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true, false, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				true,
				false,
			],
			[
				'test', true, true, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[],
				[
					['label' => 'test0', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				null,
			],
			[
				'test', true, false, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[],
				[],
				true,
				null,
			],
			[
				'test', true, true, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[
					['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']],
				],
				[
					['label' => 'test0', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				$this->getGroupMock('test'),
			],
			[
				'test', true, false, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[
					['label' => 'test', 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => 'test']],
				],
				[],
				true,
				$this->getGroupMock('test'),
			],
			[
				'test', false, false, false,
				[
					$this->getGroupMock('test', null, true),
					$this->getGroupMock('test1'),
				],
				[],
				[],
				[],
				true,
				false,
			],
		];
	}

	/**
	 * @dataProvider dataGetGroups
	 *
	 * @param string $searchTerm
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param bool $groupSharingDisabled
	 * @param array $groupResponse
	 * @param array $userGroupsResponse
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 * @param bool|IGroup $singleGroup
	 */
	public function testSearch(
		string $searchTerm,
		bool $shareWithGroupOnly,
		bool $shareeEnumeration,
		bool $groupSharingDisabled,
		array $groupResponse,
		array $userGroupsResponse,
		array $exactExpected,
		array $expected,
		bool $reachedEnd,
		$singleGroup
	) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) use ($shareWithGroupOnly, $shareeEnumeration, $groupSharingDisabled) {
					if ($appName !== 'core') {
						return $default;
					}
					return match ($key) {
						'shareapi_only_share_with_group_members' => $shareWithGroupOnly ? 'yes' : 'no',
						'shareapi_allow_share_dialog_user_enumeration' => $shareeEnumeration ? 'yes' : 'no',
						'shareapi_allow_group_sharing' => $groupSharingDisabled ? 'no' : 'yes',
						default => $default,
					};
				}
			);

		$this->instantiatePlugin();

		if (!$groupSharingDisabled) {
			$this->groupManager->expects($this->once())
				->method('search')
				->with($searchTerm, $this->limit, $this->offset)
				->willReturn($groupResponse);
		}

		if ($singleGroup !== false) {
			$this->groupManager->expects($this->once())
				->method('get')
				->with($searchTerm)
				->willReturn($singleGroup);
		}

		if ($shareWithGroupOnly) {
			$this->session->expects($this->any())
				->method('getUser')
				->willReturn($this->user);

			$numGetUserGroupsCalls = empty($groupResponse) ? 0 : 1;
			$this->groupManager->expects($this->exactly($numGetUserGroupsCalls))
				->method('getUserGroups')
				->with($this->user)
				->willReturn($userGroupsResponse);
		}

		$moreResults = $this->plugin->search($searchTerm, $this->limit, $this->offset, $this->searchResult);
		$result = $this->searchResult->asArray();

		if (!$groupSharingDisabled) {
			$this->assertEquals($exactExpected, $result['exact']['groups']);
			$this->assertEquals($expected, $result['groups']);
		}
		$this->assertSame($reachedEnd, $moreResults);
	}
}
