<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Service;

use OCA\User_LDAP\Db\GroupMembership;
use OCA\User_LDAP\Db\GroupMembershipMapper;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Service\UpdateGroupsService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UpdateGroupsServiceTest extends TestCase {
	protected Group_Proxy&MockObject $groupBackend;
	protected IEventDispatcher&MockObject $dispatcher;
	protected IGroupManager&MockObject $groupManager;
	protected IUserManager&MockObject $userManager;
	protected LoggerInterface&MockObject $logger;
	protected GroupMembershipMapper&MockObject $groupMembershipMapper;
	protected UpdateGroupsService $updateGroupsService;

	public function setUp(): void {
		$this->groupBackend = $this->createMock(Group_Proxy::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->groupMembershipMapper = $this->createMock(GroupMembershipMapper::class);

		$this->updateGroupsService = new UpdateGroupsService(
			$this->groupBackend,
			$this->dispatcher,
			$this->groupManager,
			$this->userManager,
			$this->logger,
			$this->groupMembershipMapper,
		);
	}

	public function testHandleKnownGroups(): void {
		$knownGroups = [
			'emptyGroup' => [],
			'stableGroup' => ['userA', 'userC', 'userE'],
			'groupWithAdditions' => ['userA', 'userC', 'userE'],
			'groupWithRemovals' => ['userA', 'userC', 'userDeleted', 'userE'],
			'groupWithAdditionsAndRemovals' => ['userA', 'userC', 'userE'],
			'vanishedGroup' => ['userB', 'userDeleted'],
		];
		$knownGroupsDB = [];
		foreach ($knownGroups as $gid => $members) {
			$knownGroupsDB[] = [
				'owncloudname' => $gid,
				'owncloudusers' => $members
			];
		}
		$actualGroups = [
			'emptyGroup' => [],
			'stableGroup' => ['userA', 'userC', 'userE'],
			'groupWithAdditions' => ['userA', 'userC', 'userE', 'userF'],
			'groupWithRemovals' => ['userA', 'userE'],
			'groupWithAdditionsAndRemovals' => ['userC', 'userE', 'userF'],
			'newGroup' => ['userB', 'userF'],
		];
		$groups = array_intersect(array_keys($knownGroups), array_keys($actualGroups));

		$this->groupMembershipMapper->expects($this->never())
			->method('getKnownGroups');
		$this->groupMembershipMapper->expects($this->exactly(5))
			->method('findGroupMemberships')
			->willReturnCallback(
				fn ($group) => array_map(
					fn ($userid) => GroupMembership::fromParams(['groupid' => $group,'userid' => $userid]),
					$knownGroups[$group]
				)
			);
		$this->groupMembershipMapper->expects($this->exactly(3))
			->method('delete');
		$this->groupMembershipMapper->expects($this->exactly(2))
			->method('insert');

		$this->groupBackend->expects($this->any())
			->method('usersInGroup')
			->willReturnCallback(function ($groupID) use ($actualGroups) {
				return $actualGroups[$groupID] ?? [];
			});

		$this->groupManager->expects($this->any())
			->method('get')
			->willReturnCallback(function (string $groupId): ?IGroup {
				if ($groupId === 'vanishedGroup') {
					return null;
				}
				return $this->createMock(IGroup::class);
			});

		$this->userManager->expects($this->exactly(5))
			->method('get')
			->willReturnCallback(function (string $userId) {
				if ($userId === 'userDeleted') {
					// user already deleted
					return null;
				}
				return $this->createMock(IUser::class);
			});

		$addedEvents = 0;
		$removedEvents = 0;
		$this->dispatcher->expects($this->exactly(4))
			->method('dispatchTyped')
			->willReturnCallback(function ($event) use (&$addedEvents, &$removedEvents): void {
				if ($event instanceof UserRemovedEvent) {
					$removedEvents++;
				} elseif ($event instanceof UserAddedEvent) {
					$addedEvents++;
				}
			});

		$this->updateGroupsService->handleKnownGroups($groups);

		$this->assertSame(2, $removedEvents);
		$this->assertSame(2, $addedEvents);
		// and no event for the user that is already deleted, the DB is nevertheless updated, hence 5
	}
}
