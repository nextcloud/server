<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Config;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OC\Log;
use OC\User\Manager;
use OCP\Files\Config\ICachedMountInfo;
use OCP\IDBConnection;
use OCP\IUserManager;
use Test\TestCase;
use Test\Util\User\Dummy;

/**
 * @group DB
 */
class UserMountCache extends TestCase {
	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var \OC\Files\Config\UserMountCache
	 */
	private $cache;

	public function setUp() {
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->userManager = new Manager(null);
		$userBackend = new Dummy();
		$userBackend->createUser('u1', '');
		$userBackend->createUser('u2', '');
		$this->userManager->registerBackend($userBackend);
		$this->cache = new \OC\Files\Config\UserMountCache($this->connection, $this->userManager, $this->getMock('\OC\Log'));
	}

	public function tearDown() {
		$builder = $this->connection->getQueryBuilder();

		$builder->delete('mounts')->execute();
	}

	private function getStorage($storageId, $rootId) {
		$storageCache = $this->getMockBuilder('\OC\Files\Cache\Storage')
			->disableOriginalConstructor()
			->getMock();
		$storageCache->expects($this->any())
			->method('getNumericId')
			->will($this->returnValue($storageId));

		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()
			->getMock();
		$cache->expects($this->any())
			->method('getId')
			->will($this->returnValue($rootId));

		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();
		$storage->expects($this->any())
			->method('getStorageCache')
			->will($this->returnValue($storageCache));
		$storage->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		return $storage;
	}

	private function clearCache() {
		$this->invokePrivate($this->cache, 'mountsForUsers', [[]]);
	}

	public function testNewMounts() {
		$user = $this->userManager->get('u1');

		$storage = $this->getStorage(10, 20);
		$mount = new MountPoint($storage, '/asd/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[0];
		$this->assertEquals('/asd/', $cachedMount->getMountPoint());
		$this->assertEquals($user, $cachedMount->getUser());
		$this->assertEquals($storage->getCache()->getId(''), $cachedMount->getRootId());
		$this->assertEquals($storage->getStorageCache()->getNumericId(), $cachedMount->getStorageId());
	}

	public function testSameMounts() {
		$user = $this->userManager->get('u1');

		$storage = $this->getStorage(10, 20);
		$mount = new MountPoint($storage, '/asd/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[0];
		$this->assertEquals('/asd/', $cachedMount->getMountPoint());
		$this->assertEquals($user, $cachedMount->getUser());
		$this->assertEquals($storage->getCache()->getId(''), $cachedMount->getRootId());
		$this->assertEquals($storage->getStorageCache()->getNumericId(), $cachedMount->getStorageId());
	}

	public function testRemoveMounts() {
		$user = $this->userManager->get('u1');

		$storage = $this->getStorage(10, 20);
		$mount = new MountPoint($storage, '/asd/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$this->cache->registerMounts($user, []);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(0, $cachedMounts);
	}

	public function testChangeMounts() {
		$user = $this->userManager->get('u1');

		$storage = $this->getStorage(10, 20);
		$mount = new MountPoint($storage, '/foo/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[0];
		$this->assertEquals('/foo/', $cachedMount->getMountPoint());
	}

	public function testGetMountsForUser() {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		$mount1 = new MountPoint($this->getStorage(1, 2), '/foo/');
		$mount2 = new MountPoint($this->getStorage(3, 4), '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user1);

		$this->assertCount(2, $cachedMounts);
		$this->assertEquals('/foo/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals(2, $cachedMounts[0]->getRootId());
		$this->assertEquals(1, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[1]->getUser());
		$this->assertEquals(4, $cachedMounts[1]->getRootId());
		$this->assertEquals(3, $cachedMounts[1]->getStorageId());
	}

	public function testGetMountsByStorageId() {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		$mount1 = new MountPoint($this->getStorage(1, 2), '/foo/');
		$mount2 = new MountPoint($this->getStorage(3, 4), '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForStorageId(3);
		usort($cachedMounts, function (ICachedMountInfo $a, ICachedMountInfo $b) {
			return strcmp($a->getUser()->getUID(), $b->getUser()->getUID());
		});

		$this->assertCount(2, $cachedMounts);

		$this->assertEquals('/bar/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals(4, $cachedMounts[0]->getRootId());
		$this->assertEquals(3, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user2, $cachedMounts[1]->getUser());
		$this->assertEquals(4, $cachedMounts[1]->getRootId());
		$this->assertEquals(3, $cachedMounts[1]->getStorageId());
	}

	public function testGetMountsByRootId() {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		$mount1 = new MountPoint($this->getStorage(1, 2), '/foo/');
		$mount2 = new MountPoint($this->getStorage(3, 4), '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForRootId(4);
		usort($cachedMounts, function (ICachedMountInfo $a, ICachedMountInfo $b) {
			return strcmp($a->getUser()->getUID(), $b->getUser()->getUID());
		});

		$this->assertCount(2, $cachedMounts);

		$this->assertEquals('/bar/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals(4, $cachedMounts[0]->getRootId());
		$this->assertEquals(3, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user2, $cachedMounts[1]->getUser());
		$this->assertEquals(4, $cachedMounts[1]->getRootId());
		$this->assertEquals(3, $cachedMounts[1]->getStorageId());
	}
}
