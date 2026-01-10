<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\ApplicableHelper;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ApplicableHelperTest extends TestCase {
	private IUserManager|MockObject $userManager;
	private IGroupManager|MockObject $groupManager;

	/** @var list<string> */
	private array $users = [];
	/** @var array<string, list<string>> */
	private array $groups = [];

	private ApplicableHelper $applicableHelper;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->userManager->method('get')
			->willReturnCallback(function (string $id) {
				$user = $this->createMock(IUser::class);
				$user->method('getUID')->willReturn($id);
				return $user;
			});
		$this->userManager->method('getSeenUsers')
			->willReturnCallback(fn () => new \ArrayIterator(array_map($this->userManager->get(...), $this->users)));
		$this->groupManager->method('get')
			->willReturnCallback(function (string $id) {
				$group = $this->createMock(IGroup::class);
				$group->method('getGID')->willReturn($id);
				$group->method('getUsers')
					->willReturn(array_map($this->userManager->get(...), $this->groups[$id] ?: []));
				return $group;
			});
		$this->groupManager->method('getUserGroupIds')
			->willReturnCallback(function (IUser $user) {
				$groups = [];
				foreach ($this->groups as $group => $users) {
					if (in_array($user->getUID(), $users)) {
						$groups[] = $group;
					}
				}
				return $groups;
			});

		$this->applicableHelper = new ApplicableHelper($this->userManager, $this->groupManager);

		$this->users = ['user1', 'user2', 'user3', 'user4'];
		$this->groups = [
			'group1' => ['user1', 'user2'],
			'group2' => ['user3'],
		];
	}

	public static function usersForStorageProvider(): array {
		return [
			[[], [], ['user1', 'user2', 'user3', 'user4']],
			[['user1'], [], ['user1']],
			[['user1', 'user3'], [], ['user1', 'user3']],
			[['user1'], ['group1'], ['user1', 'user2']],
			[['user1'], ['group2'], ['user1', 'user3']],
		];
	}

	#[DataProvider('usersForStorageProvider')]
	public function testGetUsersForStorage(array $applicableUsers, array $applicableGroups, array $expected) {
		$storage = $this->createMock(StorageConfig::class);
		$storage->method('getApplicableUsers')
			->willReturn($applicableUsers);
		$storage->method('getApplicableGroups')
			->willReturn($applicableGroups);

		$result = iterator_to_array($this->applicableHelper->getUsersForStorage($storage));
		$result = array_map(fn (IUser $user) => $user->getUID(), $result);
		sort($result);
		sort($expected);
		$this->assertEquals($expected, $result);
	}

	public static function applicableProvider(): array {
		return [
			[[], [], 'user1', true],
			[['user1'], [], 'user1', true],
			[['user1'], [], 'user2', false],
			[['user1', 'user3'], [], 'user1', true],
			[['user1', 'user3'], [], 'user2', false],
			[['user1'], ['group1'], 'user1', true],
			[['user1'], ['group1'], 'user2', true],
			[['user1'], ['group1'], 'user3', false],
			[['user1'], ['group1'], 'user4', false],
			[['user1'], ['group2'], 'user1', true],
			[['user1'], ['group2'], 'user2', false],
			[['user1'], ['group2'], 'user3', true],
			[['user1'], ['group1'], 'user4', false],
		];
	}

	#[DataProvider('applicableProvider')]
	public function testIsApplicable(array $applicableUsers, array $applicableGroups, string $user, bool $expected) {
		$storage = $this->createMock(StorageConfig::class);
		$storage->method('getApplicableUsers')
			->willReturn($applicableUsers);
		$storage->method('getApplicableGroups')
			->willReturn($applicableGroups);

		$this->assertEquals($expected, $this->applicableHelper->isApplicableForUser($storage, $this->userManager->get($user)));
	}

	public static function diffProvider(): array {
		return [
			[[], [], [], [], []], // both all
			[['user1'], [], [], [], []], // all added
			[[], [], ['user1'], [], ['user2', 'user3', 'user4']], // all removed
			[[], [], [], ['group1'], ['user3', 'user4']], // all removed
			[[], [], ['user3'], ['group1'], ['user4']], // all removed
			[['user1'], [], ['user1'], [], []],
			[['user1'], [], ['user1', 'user2'], [], []],
			[['user1'], [], ['user2'], [], ['user1']],
			[['user1'], [], [], ['group1'], []],
			[['user1'], [], [], ['group2'], ['user1']],
			[[], ['group1'], [], ['group2'], ['user1', 'user2']],
			[[], ['group1'], ['user1'], [], ['user2']],
			[['user1'], ['group1'], ['user1'], [], ['user2']],
			[['user1'], ['group1'], [], ['group1'], []],
			[['user1'], ['group1'], [], ['group2'], ['user1', 'user2']],
			[['user1'], ['group1'], ['user1'], ['group2'], ['user2']],
		];
	}

	#[DataProvider('diffProvider')]
	public function testDiff(array $applicableUsersA, array $applicableGroupsA, array $applicableUsersB, array $applicableGroupsB, array $expected) {
		$storageA = $this->createMock(StorageConfig::class);
		$storageA->method('getApplicableUsers')
			->willReturn($applicableUsersA);
		$storageA->method('getApplicableGroups')
			->willReturn($applicableGroupsA);

		$storageB = $this->createMock(StorageConfig::class);
		$storageB->method('getApplicableUsers')
			->willReturn($applicableUsersB);
		$storageB->method('getApplicableGroups')
			->willReturn($applicableGroupsB);

		$result = iterator_to_array($this->applicableHelper->diffApplicable($storageA, $storageB));
		$result = array_map(fn (IUser $user) => $user->getUID(), $result);
		sort($result);
		sort($expected);
		$this->assertEquals($expected, $result);
	}
}
