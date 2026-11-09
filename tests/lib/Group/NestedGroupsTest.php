<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Group;

use OC\Group\Database;
use OC\Group\Manager;
use OC\User\Manager as UserManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\SubGroupAddedEvent;
use OCP\Group\Events\SubGroupRemovedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Group\Exception\CycleDetectedException;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\Security\Ip\IRemoteAddress;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Integration test for nested-group membership resolution.
 *
 * Exercises the real OC\Group\Database backend against the real
 * OC\Group\Manager to verify transitive closure, caching, and event
 * synthesis.
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class NestedGroupsTest extends TestCase {
	private Database $backend;
	/** @var UserManager&MockObject */
	private $userManager;
	/** @var IEventDispatcher&MockObject */
	private $dispatcher;
	/** @var LoggerInterface&MockObject */
	private $logger;
	/** @var ICacheFactory&MockObject */
	private $cacheFactory;
	/** @var IRemoteAddress&MockObject */
	private $remoteAddress;
	private Manager $manager;

	/** @var string[] */
	private array $createdGroups = [];

	protected function setUp(): void {
		parent::setUp();
		$this->backend = new Database();
		$this->userManager = $this->createMock(UserManager::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->remoteAddress = $this->createMock(IRemoteAddress::class);
		$this->remoteAddress->method('allowsAdminActions')->willReturn(true);

		$this->manager = new Manager(
			$this->userManager,
			$this->dispatcher,
			$this->logger,
			$this->cacheFactory,
			$this->remoteAddress,
		);
		$this->manager->addBackend($this->backend);
	}

	protected function tearDown(): void {
		foreach ($this->createdGroups as $gid) {
			$this->backend->deleteGroup($gid);
		}
		parent::tearDown();
	}

	private function newGroup(string $prefix): string {
		$gid = $this->getUniqueID('nest_' . $prefix . '_');
		$this->backend->createGroup($gid);
		$this->createdGroups[] = $gid;
		return $gid;
	}

	private function mockUser(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		return $user;
	}

	/** Map the userManager mock so get($uid) returns a mock whose getUID() === $uid. */
	private function userManagerReturnsIdentity(): void {
		$this->userManager->method('get')->willReturnCallback(
			fn (string $uid): IUser => $this->mockUser($uid),
		);
	}

	public function testGetUserEffectiveGroupIdsWalksAncestors(): void {
		$a = $this->newGroup('a');
		$b = $this->newGroup('b');
		$c = $this->newGroup('c');

		// Hierarchy: a -> b -> c (c is the leaf)
		$this->manager->addSubGroup($this->manager->get($a), $this->manager->get($b));
		$this->manager->addSubGroup($this->manager->get($b), $this->manager->get($c));

		$uid = 'nest_user_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uid, $c);
		$this->userManagerReturnsIdentity();

		$effective = $this->manager->getUserEffectiveGroupIds($this->mockUser($uid));
		sort($effective);
		$expected = [$a, $b, $c];
		sort($expected);
		$this->assertSame($expected, $effective);
	}

	public function testDirectMembershipUnaffected(): void {
		$a = $this->newGroup('a');
		$b = $this->newGroup('b');
		$this->manager->addSubGroup($this->manager->get($a), $this->manager->get($b));

		$uid = 'nest_user_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uid, $b);
		$this->userManagerReturnsIdentity();

		$direct = $this->manager->getUserGroupIds($this->mockUser($uid));
		$this->assertSame([$b], $direct);
	}

	public function testDiamondHierarchyDeduplicates(): void {
		// User in leaf L reachable via L -> M1 -> Top and L -> M2 -> Top.
		// Effective groups must include Top exactly once.
		$top = $this->newGroup('top');
		$m1 = $this->newGroup('m1');
		$m2 = $this->newGroup('m2');
		$leaf = $this->newGroup('leaf');
		$this->manager->addSubGroup($this->manager->get($top), $this->manager->get($m1));
		$this->manager->addSubGroup($this->manager->get($top), $this->manager->get($m2));
		$this->manager->addSubGroup($this->manager->get($m1), $this->manager->get($leaf));
		$this->manager->addSubGroup($this->manager->get($m2), $this->manager->get($leaf));

		$uid = 'nest_diamond_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uid, $leaf);
		$this->userManagerReturnsIdentity();

		$effective = $this->manager->getUserEffectiveGroupIds($this->mockUser($uid));
		$this->assertSame(1, count(array_keys($effective, $top, true)));
		$this->assertContains($top, $effective);
		$this->assertContains($m1, $effective);
		$this->assertContains($m2, $effective);
		$this->assertContains($leaf, $effective);
		$this->assertCount(4, $effective);
	}

	public function testAddSubGroupDispatchesSubGroupAndUserEvents(): void {
		$parent = $this->newGroup('parent');
		$child = $this->newGroup('child');

		$uidA = 'nest_evt_a_' . bin2hex(random_bytes(4));
		$uidB = 'nest_evt_b_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uidA, $child);
		$this->backend->addToGroup($uidB, $child);
		$this->userManagerReturnsIdentity();

		$subGroupEvents = 0;
		$userEvents = [];
		$this->dispatcher->expects($this->atLeastOnce())
			->method('dispatchTyped')
			->willReturnCallback(function ($event) use (&$subGroupEvents, &$userEvents, $parent): void {
				if ($event instanceof SubGroupAddedEvent && $event->getParent()->getGID() === $parent) {
					$subGroupEvents++;
				} elseif ($event instanceof UserAddedEvent && $event->getGroup()->getGID() === $parent) {
					$userEvents[] = $event->getUser()->getUID();
				}
			});

		$this->manager->addSubGroup(
			$this->manager->get($parent),
			$this->manager->get($child),
		);

		$this->assertSame(1, $subGroupEvents);
		$this->assertContains($uidA, $userEvents);
		$this->assertContains($uidB, $userEvents);
		$this->assertCount(2, $userEvents);
	}

	public function testRemoveSubGroupDispatchesUserRemovedEvents(): void {
		$parent = $this->newGroup('parent');
		$child = $this->newGroup('child');

		$uid = 'nest_rm_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uid, $child);
		$this->userManagerReturnsIdentity();

		$this->manager->addSubGroup(
			$this->manager->get($parent),
			$this->manager->get($child),
		);

		$removedUids = [];
		$subGroupRemovedEvents = 0;
		$this->dispatcher->expects($this->atLeastOnce())
			->method('dispatchTyped')
			->willReturnCallback(function ($event) use (&$removedUids, &$subGroupRemovedEvents, $parent): void {
				if ($event instanceof SubGroupRemovedEvent && $event->getParent()->getGID() === $parent) {
					$subGroupRemovedEvents++;
				} elseif ($event instanceof UserRemovedEvent && $event->getGroup()->getGID() === $parent) {
					$removedUids[] = $event->getUser()->getUID();
				}
			});

		$this->manager->removeSubGroup(
			$this->manager->get($parent),
			$this->manager->get($child),
		);

		$this->assertSame(1, $subGroupRemovedEvents);
		$this->assertContains($uid, $removedUids);
	}

	public function testCycleRejectedBetweenManagerEdges(): void {
		$a = $this->newGroup('a');
		$b = $this->newGroup('b');
		$this->manager->addSubGroup($this->manager->get($a), $this->manager->get($b));

		$this->expectException(CycleDetectedException::class);
		$this->manager->addSubGroup($this->manager->get($b), $this->manager->get($a));
	}

	public function testAddingExistingEdgeIsIdempotent(): void {
		$a = $this->newGroup('a');
		$b = $this->newGroup('b');
		$this->assertTrue(
			$this->manager->addSubGroup($this->manager->get($a), $this->manager->get($b))
		);
		$this->assertFalse(
			$this->manager->addSubGroup($this->manager->get($a), $this->manager->get($b))
		);
	}

	public function testGetGroupEffectiveDescendantIdsIncludesSelf(): void {
		$parent = $this->newGroup('p');
		$child = $this->newGroup('c');
		$this->manager->addSubGroup($this->manager->get($parent), $this->manager->get($child));

		$descendants = $this->manager->getGroupEffectiveDescendantIds($this->manager->get($parent));
		$this->assertContains($parent, $descendants);
		$this->assertContains($child, $descendants);
	}

	public function testCacheInvalidatedAfterRemoval(): void {
		$parent = $this->newGroup('parent');
		$child = $this->newGroup('child');
		$uid = 'nest_cache_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uid, $child);
		$this->userManagerReturnsIdentity();

		$this->manager->addSubGroup($this->manager->get($parent), $this->manager->get($child));

		// Warm the cache
		$effectiveBefore = $this->manager->getUserEffectiveGroupIds($this->mockUser($uid));
		$this->assertContains($parent, $effectiveBefore);

		$this->manager->removeSubGroup($this->manager->get($parent), $this->manager->get($child));

		// After removal $parent must no longer appear in the effective set.
		$effectiveAfter = $this->manager->getUserEffectiveGroupIds($this->mockUser($uid));
		$this->assertNotContains($parent, $effectiveAfter);
		$this->assertContains($child, $effectiveAfter);
	}

	/**
	 * Verify that nesting does not leak transitive membership through any
	 * existing public API.  Every method an app could call to ask "which
	 * groups is this user in?" or "is this user in group X?" must still
	 * answer with direct memberships only.  Only the new opt-in
	 * `getUserEffectiveGroupIds()` returns the transitive closure.
	 *
	 * This guards against the concern that enabling nesting could silently
	 * grant unexpected access or data to apps that were never updated.
	 */
	public function testExistingApisReturnDirectMembershipsOnly(): void {
		// Build a 3-level hierarchy: top -> mid -> leaf
		$top = $this->newGroup('top');
		$mid = $this->newGroup('mid');
		$leaf = $this->newGroup('leaf');
		$this->manager->addSubGroup($this->manager->get($top), $this->manager->get($mid));
		$this->manager->addSubGroup($this->manager->get($mid), $this->manager->get($leaf));

		$uid = 'nest_api_' . bin2hex(random_bytes(4));
		$this->backend->addToGroup($uid, $leaf);
		$this->userManagerReturnsIdentity();

		$user = $this->mockUser($uid);

		// -- getUserGroupIds: direct only --------------------------------
		$directIds = $this->manager->getUserGroupIds($user);
		$this->assertSame([$leaf], $directIds,
			'getUserGroupIds() must return only the direct group, not ancestors');

		// -- getUserGroups: same contract, returns IGroup objects ---------
		$directGroups = $this->manager->getUserGroups($user);
		$directGroupIds = array_map(fn ($g) => $g->getGID(), $directGroups);
		$this->assertSame([$leaf], array_values($directGroupIds),
			'getUserGroups() must return only the direct group, not ancestors');

		// -- isInGroup: direct only --------------------------------------
		$this->assertTrue($this->manager->isInGroup($uid, $leaf),
			'isInGroup() must report true for the directly-joined group');
		$this->assertFalse($this->manager->isInGroup($uid, $mid),
			'isInGroup() must report false for an ancestor group the user did not directly join');
		$this->assertFalse($this->manager->isInGroup($uid, $top),
			'isInGroup() must report false for a transitive ancestor');

		// -- displayNamesInGroup: direct members only --------------------
		$namesInTop = $this->manager->displayNamesInGroup($top);
		$this->assertArrayNotHasKey($uid, $namesInTop,
			'displayNamesInGroup() must not include users from nested subgroups');
		$namesInLeaf = $this->manager->displayNamesInGroup($leaf);
		$this->assertArrayHasKey($uid, $namesInLeaf,
			'displayNamesInGroup() must still include direct members');

		// -- getUserEffectiveGroupIds: the only API that expands ---------
		$effective = $this->manager->getUserEffectiveGroupIds($user);
		sort($effective);
		$expected = [$leaf, $mid, $top];
		sort($expected);
		$this->assertSame($expected, $effective,
			'getUserEffectiveGroupIds() must return the full transitive closure');
	}

	/**
	 * Verify that per-user UserAddedEvent synthesis is never dropped when
	 * the affected-user count exceeds the soft warning threshold.
	 *
	 * Dropping events would silently desynchronize listener-driven state —
	 * notably server-side encryption key distribution, share recipient
	 * updates, and mount cache invalidation — for users on the wrong side
	 * of the threshold. Encryption-on instances would be left with admins
	 * needing a manual re-key pass and no programmatic recovery, which is
	 * not acceptable as a default behavior. This test locks down that the
	 * threshold is advisory (a warning log) and never silently drops.
	 */
	public function testPerUserEventsAreNeverDroppedAboveWarnThreshold(): void {
		// Subclass the Manager to lower the warning threshold so we can
		// exercise the above-threshold path without creating 500+ users.
		$manager = new class($this->userManager, $this->dispatcher, $this->logger, $this->cacheFactory, $this->remoteAddress, ) extends Manager {
			public function setWarnThreshold(int $threshold): void {
				$this->perUserEventWarnThreshold = $threshold;
			}
		};
		$manager->addBackend($this->backend);
		$manager->setWarnThreshold(2);

		$parent = $this->newGroup('parent');
		$child = $this->newGroup('child');

		// Five users in the child: above the threshold of 2.
		$uids = [];
		for ($i = 0; $i < 5; $i++) {
			$uids[] = $uid = 'nest_burst_' . $i . '_' . bin2hex(random_bytes(4));
			$this->backend->addToGroup($uid, $child);
		}
		$this->userManagerReturnsIdentity();

		// Expect a warning to be logged (advisory) ...
		$this->logger->expects($this->atLeastOnce())
			->method('warning')
			->with(
				$this->stringContains('above warn threshold'),
				$this->callback(fn ($context) => ($context['count'] ?? 0) === 5),
			);

		// ... but every single user must still receive a UserAddedEvent.
		$dispatched = [];
		$this->dispatcher->method('dispatchTyped')
			->willReturnCallback(function ($event) use (&$dispatched, $parent): void {
				if ($event instanceof UserAddedEvent && $event->getGroup()->getGID() === $parent) {
					$dispatched[] = $event->getUser()->getUID();
				}
			});

		$manager->addSubGroup($manager->get($parent), $manager->get($child));

		sort($dispatched);
		$expected = $uids;
		sort($expected);
		$this->assertSame(
			$expected,
			$dispatched,
			'Every effective user must receive UserAddedEvent even past the warn threshold; '
			. 'silently dropping events would desynchronize encryption keys and share targets.',
		);
	}

	public function testDirectChildListingIsShallow(): void {
		$top = $this->newGroup('top');
		$mid = $this->newGroup('mid');
		$leaf = $this->newGroup('leaf');
		$this->manager->addSubGroup($this->manager->get($top), $this->manager->get($mid));
		$this->manager->addSubGroup($this->manager->get($mid), $this->manager->get($leaf));

		$direct = $this->manager->getDirectChildGroupIds($top);
		$this->assertSame([$mid], $direct);
	}
}
