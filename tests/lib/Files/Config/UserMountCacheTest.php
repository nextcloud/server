<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Config;

use OC\DB\QueryBuilder\Literal;
use OC\Files\Mount\MountPoint;
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
class UserMountCacheTest extends TestCase {
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

	private $fileIds = [];

	public function setUp() {
		$this->fileIds = [];
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->userManager = new Manager(null);
		$userBackend = new Dummy();
		$userBackend->createUser('u1', '');
		$userBackend->createUser('u2', '');
		$this->userManager->registerBackend($userBackend);
		$this->cache = new \OC\Files\Config\UserMountCache($this->connection, $this->userManager, $this->createMock(Log::class));
	}

	public function tearDown() {
		$builder = $this->connection->getQueryBuilder();

		$builder->delete('mounts')->execute();

		$builder = $this->connection->getQueryBuilder();

		foreach ($this->fileIds as $fileId) {
			$builder->delete('filecache')
				->where($builder->expr()->eq('fileid', new Literal($fileId)))
				->execute();
		}
	}

	private function getStorage($storageId) {
		$rootId = $this->createCacheEntry('', $storageId);

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

		return [$storage, $rootId];
	}

	private function clearCache() {
		$this->invokePrivate($this->cache, 'mountsForUsers', [[]]);
	}

	public function testNewMounts() {
		$user = $this->userManager->get('u1');

		list($storage) = $this->getStorage(10);
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

		list($storage) = $this->getStorage(10);
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

		list($storage) = $this->getStorage(10);
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

		list($storage) = $this->getStorage(10);
		$mount = new MountPoint($storage, '/bar/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$mount = new MountPoint($storage, '/foo/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[0];
		$this->assertEquals('/foo/', $cachedMount->getMountPoint());
	}

	public function testChangeMountId() {
		$user = $this->userManager->get('u1');

		list($storage) = $this->getStorage(10);
		$mount = new MountPoint($storage, '/foo/', null, null, null, null);

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$mount = new MountPoint($storage, '/foo/', null, null, null, 1);

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[0];
		$this->assertEquals(1, $cachedMount->getMountId());
	}

	public function testGetMountsForUser() {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		list($storage1, $id1) = $this->getStorage(1);
		list($storage2, $id2) = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$mount2 = new MountPoint($storage2, '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user1);

		$this->assertCount(2, $cachedMounts);
		$this->assertEquals('/foo/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals($id1, $cachedMounts[0]->getRootId());
		$this->assertEquals(1, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[1]->getUser());
		$this->assertEquals($id2, $cachedMounts[1]->getRootId());
		$this->assertEquals(2, $cachedMounts[1]->getStorageId());
	}

	public function testGetMountsByStorageId() {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		list($storage1, $id1) = $this->getStorage(1);
		list($storage2, $id2) = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$mount2 = new MountPoint($storage2, '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForStorageId(2);
		$this->sortMounts($cachedMounts);

		$this->assertCount(2, $cachedMounts);

		$this->assertEquals('/bar/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals($id2, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user2, $cachedMounts[1]->getUser());
		$this->assertEquals($id2, $cachedMounts[1]->getRootId());
		$this->assertEquals(2, $cachedMounts[1]->getStorageId());
	}

	public function testGetMountsByRootId() {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		list($storage1, $id1) = $this->getStorage(1);
		list($storage2, $id2) = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$mount2 = new MountPoint($storage2, '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForRootId($id2);
		$this->sortMounts($cachedMounts);

		$this->assertCount(2, $cachedMounts);

		$this->assertEquals('/bar/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals($id2, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user2, $cachedMounts[1]->getUser());
		$this->assertEquals($id2, $cachedMounts[1]->getRootId());
		$this->assertEquals(2, $cachedMounts[1]->getStorageId());
	}

	private function sortMounts(&$mounts) {
		usort($mounts, function (ICachedMountInfo $a, ICachedMountInfo $b) {
			return strcmp($a->getUser()->getUID(), $b->getUser()->getUID());
		});
	}

	private function createCacheEntry($internalPath, $storageId) {
		$internalPath = trim($internalPath, '/');
		$inserted = $this->connection->insertIfNotExist('*PREFIX*filecache', [
			'storage' => $storageId,
			'path' => $internalPath,
			'path_hash' => md5($internalPath),
			'parent' => -1,
			'name' => basename($internalPath),
			'mimetype' => 0,
			'mimepart' => 0,
			'size' => 0,
			'storage_mtime' => 0,
			'encrypted' => 0,
			'unencrypted_size' => 0,
			'etag' => '',
			'permissions' => 31
		], ['storage', 'path_hash']);
		if ($inserted) {
			$id = (int)$this->connection->lastInsertId('*PREFIX*filecache');
			$this->fileIds[] = $id;
		} else {
			$sql = 'SELECT fileid FROM *PREFIX*filecache WHERE `storage` = ? AND `path_hash` =?';
			$query = $this->connection->prepare($sql);
			$query->execute([$storageId, md5($internalPath)]);
			return (int)$query->fetchColumn();
		}
		return $id;
	}

	public function testGetMountsForFileIdRootId() {
		$user1 = $this->userManager->get('u1');

		list($storage1, $rootId) = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($rootId);

		$this->assertCount(1, $cachedMounts);

		$this->assertEquals('/foo/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals($rootId, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());
	}

	public function testGetMountsForFileIdSubFolder() {
		$user1 = $this->userManager->get('u1');

		$fileId = $this->createCacheEntry('/foo/bar', 2);

		list($storage1, $rootId) = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($fileId);

		$this->assertCount(1, $cachedMounts);

		$this->assertEquals('/foo/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals($rootId, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());
	}

	public function testGetMountsForFileIdSubFolderMount() {
		$user1 = $this->userManager->get('u1');

		list($storage1, $rootId) = $this->getStorage(2);
		$folderId = $this->createCacheEntry('/foo', 2);
		$fileId = $this->createCacheEntry('/foo/bar', 2);


		$mount1 = $this->getMockBuilder('\OC\Files\Mount\MountPoint')
			->setConstructorArgs([$storage1, '/'])
			->setMethods(['getStorageRootId'])
			->getMock();

		$mount1->expects($this->any())
			->method('getStorageRootId')
			->will($this->returnValue($folderId));

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($fileId);

		$this->assertCount(1, $cachedMounts);

		$this->assertEquals('/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1, $cachedMounts[0]->getUser());
		$this->assertEquals($folderId, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());
		$this->assertEquals('foo', $cachedMounts[0]->getRootInternalPath());
	}

	public function testGetMountsForFileIdSubFolderMountOutside() {
		$user1 = $this->userManager->get('u1');

		list($storage1, $rootId) = $this->getStorage(2);
		$folderId = $this->createCacheEntry('/foo', 2);
		$fileId = $this->createCacheEntry('/bar/asd', 2);

		$mount1 = $this->getMockBuilder('\OC\Files\Mount\MountPoint')
			->setConstructorArgs([$storage1, '/foo/'])
			->setMethods(['getStorageRootId'])
			->getMock();

		$mount1->expects($this->any())
			->method('getStorageRootId')
			->will($this->returnValue($folderId));

		$this->cache->registerMounts($user1, [$mount1]);

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($fileId);

		$this->assertCount(0, $cachedMounts);
	}
}
