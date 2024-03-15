<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\user_ldap\tests\Service;

use OCA\User_LDAP\Db\GroupMembership;
use OCA\User_LDAP\Db\GroupMembershipMapper;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Service\UpdateGroupsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UpdateGroupsServiceTest extends TestCase {
	/** @var Group_Proxy|MockObject  */
	protected $groupBackend;
	/** @var IEventDispatcher|MockObject  */
	protected $dispatcher;
	/** @var IGroupManager|MockObject  */
	protected $groupManager;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var GroupMembershipMapper|MockObject  */
	protected $groupMembershipMapper;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var ITimeFactory|MockObject */
	protected $timeFactory;

	protected UpdateGroupsService $updateGroupsService;

	public function setUp(): void {
		$this->groupBackend = $this->createMock(Group_Proxy::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->groupMembershipMapper = $this->createMock(GroupMembershipMapper::class);
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->updateGroupsService = new UpdateGroupsService(
			$this->groupBackend,
			$this->dispatcher,
			$this->groupManager,
			$this->userManager,
			$this->logger,
			$this->groupMembershipMapper,
			$this->config,
			$this->timeFactory
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
			->willReturnCallback(function ($event) use (&$addedEvents, &$removedEvents) {
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
