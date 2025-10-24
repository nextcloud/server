<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Memcache\NullCache;
use OC\Share20\Share;
use OCA\Files_Sharing\MountProvider;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IStorageFactory;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IAttributes as IShareAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * @group DB
 */
class MountProviderTest extends \Test\TestCase {
	/** @var MountProvider */
	private $provider;

	/** @var IConfig|MockObject */
	private $config;

	/** @var IUser|MockObject */
	private $user;

	/** @var IStorageFactory|MockObject */
	private $loader;

	/** @var IManager|MockObject */
	private $shareManager;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->user = $this->getMockBuilder(IUser::class)->getMock();
		$this->loader = $this->getMockBuilder('OCP\Files\Storage\IStorageFactory')->getMock();
		$this->shareManager = $this->getMockBuilder(IManager::class)->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createLocal')
			->willReturn(new NullCache());
		$mountManager = $this->createMock(IMountManager::class);

		$this->provider = new MountProvider($this->config, $this->shareManager, $this->logger, $eventDispatcher, $cacheFactory, $mountManager);
	}

	private function makeMockShareAttributes($attrs) {
		if ($attrs === null) {
			return null;
		}

		$shareAttributes = $this->createMock(IShareAttributes::class);
		$shareAttributes->method('toArray')->willReturn($attrs);
		$shareAttributes->method('getAttribute')->will(
			$this->returnCallback(function ($scope, $key) use ($attrs) {
				$result = null;
				foreach ($attrs as $attr) {
					if ($attr['key'] === $key && $attr['scope'] === $scope) {
						$result = $attr['value'];
					}
				}
				return $result;
			})
		);
		return $shareAttributes;
	}

	private function makeMockShare($id, $nodeId, $owner = 'user2', $target = null, $permissions = 31, $attributes = null) {
		$share = $this->createMock(IShare::class);
		$share->expects($this->any())
			->method('getPermissions')
			->willReturn($permissions);
		$share->expects($this->any())
			->method('getAttributes')
			->will($this->returnValue($this->makeMockShareAttributes($attributes)));
		$share->expects($this->any())
			->method('getShareOwner')
			->willReturn($owner);
		$share->expects($this->any())
			->method('getTarget')
			->willReturn($target);
		$share->expects($this->any())
			->method('getId')
			->willReturn($id);
		$share->expects($this->any())
			->method('getNodeId')
			->willReturn($nodeId);
		$share->expects($this->any())
			->method('getShareTime')
			->willReturn(
				// compute share time based on id, simulating share order
				new \DateTime('@' . (1469193980 + 1000 * $id))
			);
		return $share;
	}

	/**
	 * Tests excluding shares from the current view. This includes:
	 * - shares that were opted out of (permissions === 0)
	 * - shares with a group in which the owner is already in
	 */
	public function testExcludeShares(): void {
		$rootFolder = $this->createMock(IRootFolder::class);
		$userManager = $this->createMock(IUserManager::class);
		$attr1 = [];
		$attr2 = [['scope' => 'permission', 'key' => 'download', 'value' => true]];
		$userShares = [
			$this->makeMockShare(1, 100, 'user2', '/share2', 0, $attr1),
			$this->makeMockShare(2, 100, 'user2', '/share2', 31, $attr2),
		];
		$groupShares = [
			$this->makeMockShare(3, 100, 'user2', '/share2', 0, $attr1),
			$this->makeMockShare(4, 101, 'user2', '/share4', 31, $attr2),
			$this->makeMockShare(5, 100, 'user1', '/share4', 31, $attr2),
		];
		$roomShares = [
			$this->makeMockShare(6, 102, 'user2', '/share6', 0),
			$this->makeMockShare(7, 102, 'user1', '/share6', 31),
			$this->makeMockShare(8, 102, 'user2', '/share6', 31),
			$this->makeMockShare(9, 102, 'user2', '/share6', 31),
		];
		$deckShares = [
			$this->makeMockShare(10, 103, 'user2', '/share7', 0),
			$this->makeMockShare(11, 103, 'user1', '/share7', 31),
			$this->makeMockShare(12, 103, 'user2', '/share7', 31),
			$this->makeMockShare(13, 103, 'user2', '/share7', 31),
		];
		// tests regarding circles and sciencemesh are made in the apps themselves.
		$circleShares = [];
		$sciencemeshShares = [];
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('user1');
		$this->shareManager->expects($this->exactly(6))
			->method('getSharedWith')
			->withConsecutive(
				['user1', IShare::TYPE_USER],
				['user1', IShare::TYPE_GROUP, null, -1],
				['user1', IShare::TYPE_CIRCLE, null, -1],
				['user1', IShare::TYPE_ROOM, null, -1],
				['user1', IShare::TYPE_DECK, null, -1],
				['user1', IShare::TYPE_SCIENCEMESH, null, -1],
			)->willReturnOnConsecutiveCalls(
				$userShares,
				$groupShares,
				$circleShares,
				$roomShares,
				$deckShares,
				$sciencemeshShares
			);
		$this->shareManager->expects($this->any())
			->method('newShare')
			->willReturnCallback(function () use ($rootFolder, $userManager) {
				return new Share($rootFolder, $userManager);
			});
		$mounts = $this->provider->getMountsForUser($this->user, $this->loader);
		$this->assertCount(4, $mounts);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[0]);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[1]);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[2]);
		$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mounts[3]);
		$mountedShare1 = $mounts[0]->getShare();
		$this->assertEquals('2', $mountedShare1->getId());
		$this->assertEquals('user2', $mountedShare1->getShareOwner());
		$this->assertEquals(100, $mountedShare1->getNodeId());
		$this->assertEquals('/share2', $mountedShare1->getTarget());
		$this->assertEquals(31, $mountedShare1->getPermissions());
		$this->assertEquals(true, $mountedShare1->getAttributes()->getAttribute('permission', 'download'));
		$mountedShare2 = $mounts[1]->getShare();
		$this->assertEquals('4', $mountedShare2->getId());
		$this->assertEquals('user2', $mountedShare2->getShareOwner());
		$this->assertEquals(101, $mountedShare2->getNodeId());
		$this->assertEquals('/share4', $mountedShare2->getTarget());
		$this->assertEquals(31, $mountedShare2->getPermissions());
		$this->assertEquals(true, $mountedShare2->getAttributes()->getAttribute('permission', 'download'));
		$mountedShare3 = $mounts[2]->getShare();
		$this->assertEquals('8', $mountedShare3->getId());
		$this->assertEquals('user2', $mountedShare3->getShareOwner());
		$this->assertEquals(102, $mountedShare3->getNodeId());
		$this->assertEquals('/share6', $mountedShare3->getTarget());
		$this->assertEquals(31, $mountedShare3->getPermissions());
		$mountedShare4 = $mounts[3]->getShare();
		$this->assertEquals('12', $mountedShare4->getId());
		$this->assertEquals('user2', $mountedShare4->getShareOwner());
		$this->assertEquals(103, $mountedShare4->getNodeId());
		$this->assertEquals('/share7', $mountedShare4->getTarget());
		$this->assertEquals(31, $mountedShare4->getPermissions());
	}

	public function mergeSharesDataProvider() {
		// note: the user in the specs here is the shareOwner not recipient
		// the recipient is always "user1"
		return [
			// #0: share as outsider with "group1" and "user1" with same permissions
			[
				[
					[1, 100, 'user2', '/share2', 31, null],
				],
				[
					[2, 100, 'user2', '/share2', 31, null],
				],
				[
					// combined, user share has higher priority
					['1', 100, 'user2', '/share2', 31, []],
				],
			],
			// #1: share as outsider with "group1" and "user1" with different permissions
			[
				[
					[1, 100, 'user2', '/share', 31, [['scope' => 'permission', 'key' => 'download', 'value' => true], ['scope' => 'app', 'key' => 'attribute1', 'value' => true]]],
				],
				[
					[2, 100, 'user2', '/share', 15, [['scope' => 'permission', 'key' => 'download', 'value' => false], ['scope' => 'app', 'key' => 'attribute2', 'value' => false]]],
				],
				[
					// use highest permissions
					['1', 100, 'user2', '/share', 31, [['scope' => 'permission', 'key' => 'download', 'value' => true], ['scope' => 'app', 'key' => 'attribute1', 'value' => true], ['scope' => 'app', 'key' => 'attribute2', 'value' => false]]],
				],
			],
			// #2: share as outsider with "group1" and "group2" with same permissions
			[
				[
				],
				[
					[1, 100, 'user2', '/share', 31, null],
					[2, 100, 'user2', '/share', 31, []],
				],
				[
					// combined, first group share has higher priority
					['1', 100, 'user2', '/share', 31, []],
				],
			],
			// #3: share as outsider with "group1" and "group2" with different permissions
			[
				[
				],
				[
					[1, 100, 'user2', '/share', 31, [['scope' => 'permission', 'key' => 'download', 'value' => false]]],
					[2, 100, 'user2', '/share', 15, [['scope' => 'permission', 'key' => 'download', 'value' => true]]],
				],
				[
					// use higher permissions (most permissive)
					['1', 100, 'user2', '/share', 31, [['scope' => 'permission', 'key' => 'download', 'value' => true]]],
				],
			],
			// #4: share as insider with "group1"
			[
				[
				],
				[
					[1, 100, 'user1', '/share', 31, []],
				],
				[
					// no received share since "user1" is the sharer/owner
				],
			],
			// #5: share as insider with "group1" and "group2" with different permissions
			[
				[
				],
				[
					[1, 100, 'user1', '/share', 31, [['scope' => 'permission', 'key' => 'download', 'value' => true]]],
					[2, 100, 'user1', '/share', 15, [['scope' => 'permission', 'key' => 'download', 'value' => false]]],
				],
				[
					// no received share since "user1" is the sharer/owner
				],
			],
			// #6: share as outside with "group1", recipient opted out
			[
				[
				],
				[
					[1, 100, 'user2', '/share', 0, []],
				],
				[
					// no received share since "user1" opted out
				],
			],
			// #7: share as outsider with "group1" and "user1" where recipient renamed in between
			[
				[
					[1, 100, 'user2', '/share2-renamed', 31, []],
				],
				[
					[2, 100, 'user2', '/share2', 31, []],
				],
				[
					// use target of least recent share
					['1', 100, 'user2', '/share2-renamed', 31, []],
				],
			],
			// #8: share as outsider with "group1" and "user1" where recipient renamed in between
			[
				[
					[2, 100, 'user2', '/share2', 31, []],
				],
				[
					[1, 100, 'user2', '/share2-renamed', 31, []],
				],
				[
					// use target of least recent share
					['1', 100, 'user2', '/share2-renamed', 31, []],
				],
			],
			// #9: share as outsider with "nullgroup" and "user1" where recipient renamed in between
			[
				[
					[2, 100, 'user2', '/share2', 31, []],
				],
				[
					[1, 100, 'nullgroup', '/share2-renamed', 31, []],
				],
				[
					// use target of least recent share
					['1', 100, 'nullgroup', '/share2-renamed', 31, []],
				],
				true
			],
		];
	}

	/**
	 * Tests merging shares.
	 *
	 * Happens when sharing the same entry to a user through multiple ways,
	 * like several groups and also direct shares at the same time.
	 *
	 * @dataProvider mergeSharesDataProvider
	 *
	 * @param array $userShares array of user share specs
	 * @param array $groupShares array of group share specs
	 * @param array $expectedShares array of expected supershare specs
	 */
	public function testMergeShares($userShares, $groupShares, $expectedShares, $moveFails = false): void {
		$rootFolder = $this->createMock(IRootFolder::class);
		$userManager = $this->createMock(IUserManager::class);

		$userShares = array_map(function ($shareSpec) {
			return $this->makeMockShare($shareSpec[0], $shareSpec[1], $shareSpec[2], $shareSpec[3], $shareSpec[4], $shareSpec[5]);
		}, $userShares);
		$groupShares = array_map(function ($shareSpec) {
			return $this->makeMockShare($shareSpec[0], $shareSpec[1], $shareSpec[2], $shareSpec[3], $shareSpec[4], $shareSpec[5]);
		}, $groupShares);

		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('user1');

		// tests regarding circles are made in the app itself.
		$circleShares = [];
		$roomShares = [];
		$deckShares = [];
		$sciencemeshShares = [];
		$this->shareManager->expects($this->exactly(6))
			->method('getSharedWith')
			->withConsecutive(
				['user1', IShare::TYPE_USER],
				['user1', IShare::TYPE_GROUP, null, -1],
				['user1', IShare::TYPE_CIRCLE, null, -1],
				['user1', IShare::TYPE_ROOM, null, -1],
				['user1', IShare::TYPE_DECK, null, -1],
				['user1', IShare::TYPE_SCIENCEMESH, null, -1],
			)->willReturnOnConsecutiveCalls(
				$userShares,
				$groupShares,
				$circleShares,
				$roomShares,
				$deckShares,
				$sciencemeshShares
			);
		$this->shareManager->expects($this->any())
			->method('newShare')
			->willReturnCallback(function () use ($rootFolder, $userManager) {
				return new Share($rootFolder, $userManager);
			});

		if ($moveFails) {
			$this->shareManager->expects($this->any())
				->method('moveShare')
				->will($this->throwException(new \InvalidArgumentException()));
		}

		$mounts = $this->provider->getMountsForUser($this->user, $this->loader);

		$this->assertCount(count($expectedShares), $mounts);

		foreach ($mounts as $index => $mount) {
			$expectedShare = $expectedShares[$index];
			$this->assertInstanceOf('OCA\Files_Sharing\SharedMount', $mount);

			// supershare
			$share = $mount->getShare();

			$this->assertEquals($expectedShare[0], $share->getId());
			$this->assertEquals($expectedShare[1], $share->getNodeId());
			$this->assertEquals($expectedShare[2], $share->getShareOwner());
			$this->assertEquals($expectedShare[3], $share->getTarget());
			$this->assertEquals($expectedShare[4], $share->getPermissions());
			if ($expectedShare[5] === null) {
				$this->assertNull($share->getAttributes());
			} else {
				$this->assertEquals($expectedShare[5], $share->getAttributes()->toArray());
			}
		}
	}
}
