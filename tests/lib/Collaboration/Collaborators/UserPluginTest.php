<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\SearchResult;
use OC\Collaboration\Collaborators\UserPlugin;
use OC\KnownUser\KnownUserService;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;
use OCP\UserStatus\IManager as IUserStatusManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserPluginTest extends TestCase {
	/** @var IConfig|MockObject */
	protected $config;

	/** @var IUserManager|MockObject */
	protected $userManager;

	/** @var IGroupManager|MockObject */
	protected $groupManager;

	/** @var IUserSession|MockObject */
	protected $session;

	/** @var KnownUserService|MockObject */
	protected $knownUserService;

	/** @var IUserStatusManager|MockObject */
	protected $userStatusManager;

	/** @var UserPlugin */
	protected $plugin;

	/** @var ISearchResult */
	protected $searchResult;

	protected int $limit = 2;

	protected int $offset = 0;

	/** @var IUser|MockObject */
	protected $user;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);

		$this->userManager = $this->createMock(IUserManager::class);

		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->session = $this->createMock(IUserSession::class);

		$this->knownUserService = $this->createMock(KnownUserService::class);

		$this->userStatusManager = $this->createMock(IUserStatusManager::class);

		$this->searchResult = new SearchResult();

		$this->user = $this->getUserMock('admin', 'Administrator');
	}

	public function instantiatePlugin() {
		// cannot be done within setUp, because dependent mocks needs to be set
		// up with configuration etc. first
		$this->plugin = new UserPlugin(
			$this->config,
			$this->userManager,
			$this->groupManager,
			$this->session,
			$this->knownUserService,
			$this->userStatusManager
		);
	}

	public function mockConfig($mockedSettings) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(
				function ($appName, $key, $default) use ($mockedSettings) {
					return $mockedSettings[$appName][$key] ?? $default;
				}
			);
	}

	public function getUserMock($uid, $displayName, $enabled = true, $groups = []) {
		$user = $this->createMock(IUser::class);

		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);

		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);

		$user->expects($this->any())
			->method('isEnabled')
			->willReturn($enabled);

		return $user;
	}

	public function getGroupMock($gid) {
		$group = $this->createMock(IGroup::class);

		$group->expects($this->any())
			->method('getGID')
			->willReturn($gid);

		return $group;
	}

	public function dataGetUsers() {
		return [
			['test', false, true, [], [], [], [], true, false],
			['test', false, false, [], [], [], [], true, false],
			['test', true, true, [], [], [], [], true, false],
			['test', true, false, [], [], [], [], true, false],
			[
				'test', false, true, [], [],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test'],
				], [], true, $this->getUserMock('test', 'Test'),
			],
			[
				'test', false, false, [], [],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test'],
				], [], true, $this->getUserMock('test', 'Test'),
			],
			[
				'test', true, true, [], [],
				[], [], true, $this->getUserMock('test', 'Test'),
			],
			[
				'test', true, false, [], [],
				[], [], true, $this->getUserMock('test', 'Test'),
			],
			[
				'test', true, true, ['test-group'], [['test-group', 'test', 2, 0, []]],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test'],
				], [], true, $this->getUserMock('test', 'Test'),
			],
			[
				'test', true, false, ['test-group'], [['test-group', 'test', 2, 0, []]],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test'],
				], [], true, $this->getUserMock('test', 'Test'),
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test1'],
				],
				true,
				false,
			],
			[
				'test',
				false,
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
				],
				[],
				[],
				true,
				false,
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test1'],
					['label' => 'Test Two', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test2'],
				],
				false,
				false,
			],
			[
				'test',
				false,
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[],
				[],
				true,
				false,
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test0', 'Test'),
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test0'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test0'],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test1'],
					['label' => 'Test Two', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test2'],
				],
				false,
				false,
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test0', 'Test'),
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test0'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test0'],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test1'],
					['label' => 'Test Two', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test2'],
				],
				false,
				false,
				[],
				true,
			],
			[
				'test',
				false,
				false,
				[],
				[
					$this->getUserMock('test0', 'Test'),
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test0'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test0'],
				],
				[],
				true,
				false,
			],
			[
				'test',
				true,
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, ['test1' => 'Test One']],
					['xyz', 'test', 2, 0, []],
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test1'],
				],
				true,
				false,
				[['test1', $this->getUserMock('test1', 'Test One')]],
			],
			[
				'test',
				true,
				false,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, ['test1' => 'Test One']],
					['xyz', 'test', 2, 0, []],
				],
				[],
				[],
				true,
				false,
				[['test1', $this->getUserMock('test1', 'Test One')]],
			],
			[
				'test',
				true,
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
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test1'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test1'],
					['label' => 'Test Two', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test2'],
				],
				true,
				false,
				[
					['test1', $this->getUserMock('test1', 'Test One')],
					['test2', $this->getUserMock('test2', 'Test Two')],
				],
			],
			[
				'test',
				true,
				false,
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
				[],
				true,
				false,
				[
					['test1', $this->getUserMock('test1', 'Test One')],
					['test2', $this->getUserMock('test2', 'Test Two')],
				],
			],
			[
				'test',
				true,
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
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test'],
				],
				[
					['label' => 'Test Two', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test2'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test2'],
				],
				false,
				false,
				[
					['test', $this->getUserMock('test', 'Test One')],
					['test2', $this->getUserMock('test2', 'Test Two')],
				],
			],
			[
				'test',
				true,
				false,
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
					['label' => 'Test One', 'value' => ['shareType' => IShare::TYPE_USER, 'shareWith' => 'test'], 'icon' => 'icon-user', 'subline' => null, 'status' => [], 'shareWithDisplayNameUnique' => 'test'],
				],
				[],
				true,
				false,
				[
					['test', $this->getUserMock('test', 'Test One')],
					['test2', $this->getUserMock('test2', 'Test Two')],
				],
			],
		];
	}

	/**
	 * @dataProvider dataGetUsers
	 *
	 * @param string $searchTerm
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param array $groupResponse
	 * @param array $userResponse
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 * @param bool|IUser $singleUser
	 * @param array $users
	 */
	public function testSearch(
		$searchTerm,
		$shareWithGroupOnly,
		$shareeEnumeration,
		array $groupResponse,
		array $userResponse,
		array $exactExpected,
		array $expected,
		$reachedEnd,
		$singleUser,
		array $users = [],
		$shareeEnumerationPhone = false,
	): void {
		$this->mockConfig(['core' => [
			'shareapi_only_share_with_group_members' => $shareWithGroupOnly ? 'yes' : 'no',
			'shareapi_allow_share_dialog_user_enumeration' => $shareeEnumeration? 'yes' : 'no',
			'shareapi_restrict_user_enumeration_to_group' => false ? 'yes' : 'no',
			'shareapi_restrict_user_enumeration_to_phone' => $shareeEnumerationPhone ? 'yes' : 'no',
		]]);

		$this->instantiatePlugin();

		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		if (!$shareWithGroupOnly) {
			if ($shareeEnumerationPhone) {
				$this->userManager->expects($this->once())
					->method('searchKnownUsersByDisplayName')
					->with($this->user->getUID(), $searchTerm, $this->limit, $this->offset)
					->willReturn($userResponse);

				$this->knownUserService->method('isKnownToUser')
					->willReturnMap([
						[$this->user->getUID(), 'test0', true],
						[$this->user->getUID(), 'test1', true],
						[$this->user->getUID(), 'test2', true],
					]);
			} else {
				$this->userManager->expects($this->once())
					->method('searchDisplayName')
					->with($searchTerm, $this->limit, $this->offset)
					->willReturn($userResponse);
			}
		} else {
			$this->groupManager->method('getUserGroupIds')
				->with($this->user)
				->willReturn($groupResponse);

			if ($singleUser !== false) {
				$this->groupManager->method('getUserGroupIds')
					->with($singleUser)
					->willReturn($groupResponse);
			}

			$this->groupManager->method('displayNamesInGroup')
				->willReturnMap($userResponse);
		}

		if ($singleUser !== false) {
			$users[] = [$searchTerm, $singleUser];
		}

		if (!empty($users)) {
			$this->userManager->expects($this->atLeastOnce())
				->method('get')
				->willReturnMap($users);
		}

		$moreResults = $this->plugin->search($searchTerm, $this->limit, $this->offset, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertEquals($exactExpected, $result['exact']['users']);
		$this->assertEquals($expected, $result['users']);
		$this->assertSame($reachedEnd, $moreResults);
	}

	public function takeOutCurrentUserProvider() {
		$inputUsers = [
			'alice' => 'Alice',
			'bob' => 'Bob',
			'carol' => 'Carol',
		];
		return [
			[
				$inputUsers,
				['alice', 'carol'],
				'bob',
			],
			[
				$inputUsers,
				['alice', 'bob', 'carol'],
				'dave',
			],
			[
				$inputUsers,
				['alice', 'bob', 'carol'],
				null,
			],
		];
	}

	/**
	 * @dataProvider takeOutCurrentUserProvider
	 * @param array $users
	 * @param array $expectedUIDs
	 * @param $currentUserId
	 */
	public function testTakeOutCurrentUser(array $users, array $expectedUIDs, $currentUserId): void {
		$this->instantiatePlugin();

		$this->session->expects($this->once())
			->method('getUser')
			->willReturnCallback(function () use ($currentUserId) {
				if ($currentUserId !== null) {
					return $this->getUserMock($currentUserId, $currentUserId);
				}
				return null;
			});

		$this->plugin->takeOutCurrentUser($users);
		$this->assertSame($expectedUIDs, array_keys($users));
	}

	public function dataSearchEnumeration() {
		return [
			[
				'test',
				['groupA'],
				[
					['uid' => 'test1', 'groups' => ['groupA']],
					['uid' => 'test2', 'groups' => ['groupB']],
				],
				['exact' => [], 'wide' => ['test1']],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				['groupA'],
				[
					['uid' => 'test1', 'displayName' => 'Test user 1', 'groups' => ['groupA']],
					['uid' => 'test2', 'displayName' => 'Test user 2', 'groups' => ['groupA']],
				],
				['exact' => [], 'wide' => []],
				['core' => ['shareapi_allow_share_dialog_user_enumeration' => 'no']],
			],
			[
				'test1',
				['groupA'],
				[
					['uid' => 'test1', 'displayName' => 'Test user 1', 'groups' => ['groupA']],
					['uid' => 'test2', 'displayName' => 'Test user 2', 'groups' => ['groupA']],
				],
				['exact' => ['test1'], 'wide' => []],
				['core' => ['shareapi_allow_share_dialog_user_enumeration' => 'no']],
			],
			[
				'test1',
				['groupA'],
				[
					['uid' => 'test1', 'displayName' => 'Test user 1', 'groups' => ['groupA']],
					['uid' => 'test2', 'displayName' => 'Test user 2', 'groups' => ['groupA']],
				],
				['exact' => [], 'wide' => []],
				[
					'core' => [
						'shareapi_allow_share_dialog_user_enumeration' => 'no',
						'shareapi_restrict_user_enumeration_full_match_userid' => 'no',
					],
				]
			],
			[
				'Test user 1',
				['groupA'],
				[
					['uid' => 'test1', 'displayName' => 'Test user 1', 'groups' => ['groupA']],
					['uid' => 'test2', 'displayName' => 'Test user 2', 'groups' => ['groupA']],
				],
				['exact' => ['test1'], 'wide' => []],
				[
					'core' => [
						'shareapi_allow_share_dialog_user_enumeration' => 'no',
						'shareapi_restrict_user_enumeration_full_match_userid' => 'no',
					],
				]
			],
			[
				'Test user 1',
				['groupA'],
				[
					['uid' => 'test1', 'displayName' => 'Test user 1 (Second displayName for user 1)', 'groups' => ['groupA']],
					['uid' => 'test2', 'displayName' => 'Test user 2 (Second displayName for user 2)', 'groups' => ['groupA']],
				],
				['exact' => [], 'wide' => []],
				['core' => ['shareapi_allow_share_dialog_user_enumeration' => 'no'],
				]
			],
			[
				'Test user 1',
				['groupA'],
				[
					['uid' => 'test1', 'displayName' => 'Test user 1 (Second displayName for user 1)', 'groups' => ['groupA']],
					['uid' => 'test2', 'displayName' => 'Test user 2 (Second displayName for user 2)', 'groups' => ['groupA']],
				],
				['exact' => ['test1'], 'wide' => []],
				[
					'core' => [
						'shareapi_allow_share_dialog_user_enumeration' => 'no',
						'shareapi_restrict_user_enumeration_full_match_ignore_second_dn' => 'yes',
					],
				]
			],
			[
				'test1',
				['groupA'],
				[
					['uid' => 'test1', 'groups' => ['groupA']],
					['uid' => 'test2', 'groups' => ['groupB']],
				],
				['exact' => ['test1'], 'wide' => []],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				['groupA'],
				[
					['uid' => 'test1', 'groups' => ['groupA']],
					['uid' => 'test2', 'groups' => ['groupB', 'groupA']],
				],
				['exact' => [], 'wide' => ['test1', 'test2']],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				['groupA'],
				[
					['uid' => 'test1', 'groups' => ['groupA', 'groupC']],
					['uid' => 'test2', 'groups' => ['groupB', 'groupA']],
				],
				['exact' => [], 'wide' => ['test1', 'test2']],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				['groupC', 'groupB'],
				[
					['uid' => 'test1', 'groups' => ['groupA', 'groupC']],
					['uid' => 'test2', 'groups' => ['groupB', 'groupA']],
				],
				['exact' => [], 'wide' => ['test1', 'test2']],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				[],
				[
					['uid' => 'test1', 'groups' => ['groupA']],
					['uid' => 'test2', 'groups' => ['groupB', 'groupA']],
				],
				['exact' => [], 'wide' => []],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				['groupC', 'groupB'],
				[
					['uid' => 'test1', 'groups' => []],
					['uid' => 'test2', 'groups' => []],
				],
				['exact' => [], 'wide' => []],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
			[
				'test',
				['groupC', 'groupB'],
				[
					['uid' => 'test1', 'groups' => []],
					['uid' => 'test2', 'groups' => []],
				],
				['exact' => [], 'wide' => []],
				['core' => ['shareapi_restrict_user_enumeration_to_group' => 'yes']],
			],
		];
	}

	/**
	 * @dataProvider dataSearchEnumeration
	 */
	public function testSearchEnumerationLimit($search, $userGroups, $matchingUsers, $result, $mockedSettings): void {
		$this->mockConfig($mockedSettings);

		$userResults = [];
		foreach ($matchingUsers as $user) {
			$userResults[$user['uid']] = $user['uid'];
		}

		$usersById = [];
		foreach ($matchingUsers as $user) {
			$usersById[$user['uid']] = $user;
		}

		$mappedResultExact = array_map(function ($user) use ($usersById, $search) {
			return [
				'label' => $search === $user ? $user : $usersById[$user]['displayName'],
				'value' => ['shareType' => 0, 'shareWith' => $user],
				'icon' => 'icon-user',
				'subline' => null,
				'status' => [],
				'shareWithDisplayNameUnique' => $user,
			];
		}, $result['exact']);
		$mappedResultWide = array_map(function ($user) {
			return [
				'label' => $user,
				'value' => ['shareType' => 0, 'shareWith' => $user],
				'icon' => 'icon-user',
				'subline' => null,
				'status' => [],
				'shareWithDisplayNameUnique' => $user,
			];
		}, $result['wide']);

		$this->userManager
			->method('get')
			->willReturnCallback(function ($userId) use ($userResults) {
				if (isset($userResults[$userId])) {
					return $this->getUserMock($userId, $userId);
				}
				return null;
			});
		$this->userManager
			->method('searchDisplayName')
			->willReturnCallback(function ($search) use ($matchingUsers) {
				$users = array_filter(
					$matchingUsers,
					fn ($user) => str_contains(strtolower($user['displayName']), strtolower($search))
				);
				return array_map(
					fn ($user) => $this->getUserMock($user['uid'], $user['displayName']),
					$users);
			});

		$this->groupManager->method('displayNamesInGroup')
			->willReturn($userResults);


		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($this->getUserMock('test', 'foo'));
		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->willReturnCallback(function ($user) use ($matchingUsers, $userGroups) {
				static $firstCall = true;
				if ($firstCall) {
					$firstCall = false;
					// current user
					return $userGroups;
				}
				$neededObject = array_filter(
					$matchingUsers,
					function ($e) use ($user) {
						return $user->getUID() === $e['uid'];
					}
				);
				if (count($neededObject) > 0) {
					return array_shift($neededObject)['groups'];
				}
				return [];
			});

		$this->instantiatePlugin();
		$this->plugin->search($search, $this->limit, $this->offset, $this->searchResult);
		$result = $this->searchResult->asArray();

		$this->assertEquals($mappedResultExact, $result['exact']['users']);
		$this->assertEquals($mappedResultWide, $result['users']);
	}
}
