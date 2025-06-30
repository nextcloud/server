<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Config;

use OC\DB\Exceptions\DbalException;
use OC\DB\QueryBuilder\Literal;
use OC\Files\Cache\Cache;
use OC\Files\Config\UserMountCache;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Storage;
use OC\User\Manager;
use OCP\Cache\CappedMemoryCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\Event\UserMountAddedEvent;
use OCP\Files\Config\Event\UserMountRemovedEvent;
use OCP\Files\Config\Event\UserMountUpdatedEvent;
use OCP\Files\Config\ICachedMountInfo;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Util\User\Dummy;

/**
 * @group DB
 */
class UserMountCacheTest extends TestCase {
	private IDBConnection $connection;
	private IUserManager $userManager;
	private IEventDispatcher $eventDispatcher;
	private UserMountCache $cache;
	private array $fileIds = [];

	protected function setUp(): void {
		parent::setUp();

		$this->fileIds = [];

		$this->connection = Server::get(IDBConnection::class);

		$config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$config
			->expects($this->any())
			->method('getUserValue')
			->willReturnArgument(3);
		$config
			->expects($this->any())
			->method('getAppValue')
			->willReturnArgument(2);

		$this->userManager = new Manager($config, $this->createMock(ICacheFactory::class), $this->createMock(IEventDispatcher::class), $this->createMock(LoggerInterface::class));
		$userBackend = new Dummy();
		$userBackend->createUser('u1', '');
		$userBackend->createUser('u2', '');
		$userBackend->createUser('u3', '');
		$this->userManager->registerBackend($userBackend);

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->cache = new UserMountCache($this->connection,
			$this->userManager,
			$this->createMock(LoggerInterface::class),
			$this->createMock(IEventLogger::class),
			$this->eventDispatcher,
		);
	}

	protected function tearDown(): void {
		$builder = $this->connection->getQueryBuilder();

		$builder->delete('mounts')->execute();

		$builder = $this->connection->getQueryBuilder();

		foreach ($this->fileIds as $fileId) {
			$builder->delete('filecache')
				->where($builder->expr()->eq('fileid', new Literal($fileId)))
				->execute();
		}
	}

	private function getStorage($storageId, $rootInternalPath = '') {
		$rootId = $this->createCacheEntry($rootInternalPath, $storageId);

		$storageCache = $this->createMock(\OC\Files\Cache\Storage::class);
		$storageCache->expects($this->any())
			->method('getNumericId')
			->willReturn($storageId);

		$cache = $this->createMock(Cache::class);
		$cache->expects($this->any())
			->method('getId')
			->willReturn($rootId);
		$cache->method('getNumericStorageId')
			->willReturn($storageId);

		$storage = $this->createMock(Storage::class);
		$storage->expects($this->any())
			->method('getStorageCache')
			->willReturn($storageCache);
		$storage->expects($this->any())
			->method('getCache')
			->willReturn($cache);

		return [$storage, $rootId];
	}

	private function clearCache() {
		$this->invokePrivate($this->cache, 'mountsForUsers', [new CappedMemoryCache()]);
	}

	private function keyForMount(MountPoint $mount): string {
		return $mount->getStorageRootId() . '::' . $mount->getMountPoint();
	}

	public function testNewMounts(): void {
		$this->eventDispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (UserMountAddedEvent $event) => $event->mountPoint->getMountPoint() === '/asd/'));

		$user = $this->userManager->get('u1');

		[$storage] = $this->getStorage(10);
		$mount = new MountPoint($storage, '/asd/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[$this->keyForMount($mount)];
		$this->assertEquals('/asd/', $cachedMount->getMountPoint());
		$this->assertEquals($user->getUID(), $cachedMount->getUser()->getUID());
		$this->assertEquals($storage->getCache()->getId(''), $cachedMount->getRootId());
		$this->assertEquals($storage->getStorageCache()->getNumericId(), $cachedMount->getStorageId());
	}

	public function testSameMounts(): void {
		$this->eventDispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (UserMountAddedEvent $event) => $event->mountPoint->getMountPoint() === '/asd/'));

		$user = $this->userManager->get('u1');

		[$storage] = $this->getStorage(10);
		$mount = new MountPoint($storage, '/asd/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[$this->keyForMount($mount)];
		$this->assertEquals('/asd/', $cachedMount->getMountPoint());
		$this->assertEquals($user->getUID(), $cachedMount->getUser()->getUID());
		$this->assertEquals($storage->getCache()->getId(''), $cachedMount->getRootId());
		$this->assertEquals($storage->getStorageCache()->getNumericId(), $cachedMount->getStorageId());
	}

	public function testRemoveMounts(): void {
		$operation = 0;
		$this->eventDispatcher
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->with($this->callback(function (UserMountAddedEvent|UserMountRemovedEvent $event) use (&$operation) {
				return match(++$operation) {
					1 => $event instanceof UserMountAddedEvent && $event->mountPoint->getMountPoint() === '/asd/',
					2 => $event instanceof UserMountRemovedEvent && $event->mountPoint->getMountPoint() === '/asd/',
					default => false,
				};
			}));

		$user = $this->userManager->get('u1');

		[$storage] = $this->getStorage(10);
		$mount = new MountPoint($storage, '/asd/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$this->cache->registerMounts($user, []);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(0, $cachedMounts);
	}

	public function testChangeMounts(): void {
		$operation = 0;
		$this->eventDispatcher
			->expects($this->exactly(3))
			->method('dispatchTyped')
			->with($this->callback(function (UserMountAddedEvent|UserMountRemovedEvent $event) use (&$operation) {
				return match(++$operation) {
					1 => $event instanceof UserMountAddedEvent && $event->mountPoint->getMountPoint() === '/bar/',
					2 => $event instanceof UserMountAddedEvent && $event->mountPoint->getMountPoint() === '/foo/',
					3 => $event instanceof UserMountRemovedEvent && $event->mountPoint->getMountPoint() === '/bar/',
					default => false,
				};
			}));

		$user = $this->userManager->get('u1');

		[$storage] = $this->getStorage(10);
		$mount = new MountPoint($storage, '/bar/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$mount = new MountPoint($storage, '/foo/');

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[$this->keyForMount($mount)];
		$this->assertEquals('/foo/', $cachedMount->getMountPoint());
	}

	public function testChangeMountId(): void {
		$operation = 0;
		$this->eventDispatcher
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->with($this->callback(function (UserMountAddedEvent|UserMountUpdatedEvent $event) use (&$operation) {
				return match(++$operation) {
					1 => $event instanceof UserMountAddedEvent && $event->mountPoint->getMountPoint() === '/foo/',
					2 => $event instanceof UserMountUpdatedEvent && $event->oldMountPoint->getMountId() === null && $event->newMountPoint->getMountId() === 1,
					default => false,
				};
			}));

		$user = $this->userManager->get('u1');

		[$storage] = $this->getStorage(10);
		$mount = new MountPoint($storage, '/foo/', null, null, null, null);

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$mount = new MountPoint($storage, '/foo/', null, null, null, 1);

		$this->cache->registerMounts($user, [$mount]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user);

		$this->assertCount(1, $cachedMounts);
		$cachedMount = $cachedMounts[$this->keyForMount($mount)];
		$this->assertEquals(1, $cachedMount->getMountId());
	}

	public function testGetMountsForUser(): void {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');
		$user3 = $this->userManager->get('u3');

		[$storage1, $id1] = $this->getStorage(1);
		[$storage2, $id2] = $this->getStorage(2, 'foo/bar');
		$mount1 = new MountPoint($storage1, '/foo/');
		$mount2 = new MountPoint($storage2, '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);
		$this->cache->registerMounts($user3, [$mount2]);

		$this->clearCache();

		$user3->delete();

		$cachedMounts = $this->cache->getMountsForUser($user1);

		$this->assertCount(2, $cachedMounts);
		$this->assertEquals('/foo/', $cachedMounts[$this->keyForMount($mount1)]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[$this->keyForMount($mount1)]->getUser()->getUID());
		$this->assertEquals($id1, $cachedMounts[$this->keyForMount($mount1)]->getRootId());
		$this->assertEquals(1, $cachedMounts[$this->keyForMount($mount1)]->getStorageId());
		$this->assertEquals('', $cachedMounts[$this->keyForMount($mount1)]->getRootInternalPath());

		$this->assertEquals('/bar/', $cachedMounts[$this->keyForMount($mount2)]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[$this->keyForMount($mount2)]->getUser()->getUID());
		$this->assertEquals($id2, $cachedMounts[$this->keyForMount($mount2)]->getRootId());
		$this->assertEquals(2, $cachedMounts[$this->keyForMount($mount2)]->getStorageId());
		$this->assertEquals('foo/bar', $cachedMounts[$this->keyForMount($mount2)]->getRootInternalPath());

		$cachedMounts = $this->cache->getMountsForUser($user3);
		$this->assertEmpty($cachedMounts);
	}

	public function testGetMountsByStorageId(): void {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		[$storage1, $id1] = $this->getStorage(1);
		[$storage2, $id2] = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$mount2 = new MountPoint($storage2, '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForStorageId(2);
		$this->sortMounts($cachedMounts);

		$this->assertCount(2, $cachedMounts);

		$this->assertEquals('/bar/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[0]->getUser()->getUID());
		$this->assertEquals($id2, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user2->getUID(), $cachedMounts[1]->getUser()->getUID());
		$this->assertEquals($id2, $cachedMounts[1]->getRootId());
		$this->assertEquals(2, $cachedMounts[1]->getStorageId());
	}

	public function testGetMountsByRootId(): void {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		[$storage1, $id1] = $this->getStorage(1);
		[$storage2, $id2] = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$mount2 = new MountPoint($storage2, '/bar/');

		$this->cache->registerMounts($user1, [$mount1, $mount2]);
		$this->cache->registerMounts($user2, [$mount2]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForRootId($id2);
		$this->sortMounts($cachedMounts);

		$this->assertCount(2, $cachedMounts);

		$this->assertEquals('/bar/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[0]->getUser()->getUID());
		$this->assertEquals($id2, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());

		$this->assertEquals('/bar/', $cachedMounts[1]->getMountPoint());
		$this->assertEquals($user2->getUID(), $cachedMounts[1]->getUser()->getUID());
		$this->assertEquals($id2, $cachedMounts[1]->getRootId());
		$this->assertEquals(2, $cachedMounts[1]->getStorageId());
	}

	private function sortMounts(&$mounts) {
		usort($mounts, function (ICachedMountInfo $a, ICachedMountInfo $b) {
			return strcmp($a->getUser()->getUID(), $b->getUser()->getUID());
		});
	}

	private function createCacheEntry($internalPath, $storageId, $size = 0) {
		$internalPath = trim($internalPath, '/');
		try {
			$query = $this->connection->getQueryBuilder();
			$query->insert('filecache')
				->values([
					'storage' => $query->createNamedParameter($storageId),
					'path' => $query->createNamedParameter($internalPath),
					'path_hash' => $query->createNamedParameter(md5($internalPath)),
					'parent' => $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT),
					'name' => $query->createNamedParameter(basename($internalPath)),
					'mimetype' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'mimepart' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'size' => $query->createNamedParameter($size),
					'storage_mtime' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'encrypted' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'unencrypted_size' => $query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
					'etag' => $query->createNamedParameter(''),
					'permissions' => $query->createNamedParameter(31, IQueryBuilder::PARAM_INT),
				]);
			$query->executeStatement();
			$id = $query->getLastInsertId();
			$this->fileIds[] = $id;
		} catch (DbalException $e) {
			if ($e->getReason() === DbalException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$query = $this->connection->getQueryBuilder();
				$query->select('fileid')
					->from('filecache')
					->where($query->expr()->eq('storage', $query->createNamedParameter($storageId)))
					->andWhere($query->expr()->eq('path_hash', $query->createNamedParameter(md5($internalPath))));
				$id = (int)$query->execute()->fetchColumn();
			} else {
				throw $e;
			}
		}
		return $id;
	}

	public function testGetMountsForFileIdRootId(): void {
		$user1 = $this->userManager->get('u1');

		[$storage1, $rootId] = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($rootId);

		$this->assertCount(1, $cachedMounts);

		$this->assertEquals('/foo/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[0]->getUser()->getUID());
		$this->assertEquals($rootId, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());
	}

	public function testGetMountsForFileIdSubFolder(): void {
		$user1 = $this->userManager->get('u1');

		$fileId = $this->createCacheEntry('/foo/bar', 2);

		[$storage1, $rootId] = $this->getStorage(2);
		$mount1 = new MountPoint($storage1, '/foo/');

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($fileId);

		$this->assertCount(1, $cachedMounts);

		$this->assertEquals('/foo/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[0]->getUser()->getUID());
		$this->assertEquals($rootId, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());
		$this->assertEquals('foo/bar', $cachedMounts[0]->getInternalPath());
		$this->assertEquals('/foo/foo/bar', $cachedMounts[0]->getPath());
	}

	public function testGetMountsForFileIdSubFolderMount(): void {
		$user1 = $this->userManager->get('u1');

		[$storage1, $rootId] = $this->getStorage(2);
		$folderId = $this->createCacheEntry('/foo', 2);
		$fileId = $this->createCacheEntry('/foo/bar', 2);


		$mount1 = $this->getMockBuilder(MountPoint::class)
			->setConstructorArgs([$storage1, '/'])
			->onlyMethods(['getStorageRootId'])
			->getMock();

		$mount1->expects($this->any())
			->method('getStorageRootId')
			->willReturn($folderId);

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($fileId);

		$this->assertCount(1, $cachedMounts);

		$this->assertEquals('/', $cachedMounts[0]->getMountPoint());
		$this->assertEquals($user1->getUID(), $cachedMounts[0]->getUser()->getUID());
		$this->assertEquals($folderId, $cachedMounts[0]->getRootId());
		$this->assertEquals(2, $cachedMounts[0]->getStorageId());
		$this->assertEquals('foo', $cachedMounts[0]->getRootInternalPath());
		$this->assertEquals('bar', $cachedMounts[0]->getInternalPath());
		$this->assertEquals('/bar', $cachedMounts[0]->getPath());
	}

	public function testGetMountsForFileIdSubFolderMountOutside(): void {
		$user1 = $this->userManager->get('u1');

		[$storage1, $rootId] = $this->getStorage(2);
		$folderId = $this->createCacheEntry('/foo', 2);
		$fileId = $this->createCacheEntry('/bar/asd', 2);

		$mount1 = $this->getMockBuilder(MountPoint::class)
			->setConstructorArgs([$storage1, '/foo/'])
			->onlyMethods(['getStorageRootId'])
			->getMock();

		$mount1->expects($this->any())
			->method('getStorageRootId')
			->willReturn($folderId);

		$this->cache->registerMounts($user1, [$mount1]);

		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($fileId);

		$this->assertCount(0, $cachedMounts);
	}


	public function testGetMountsForFileIdDeletedUser(): void {
		$user1 = $this->userManager->get('u1');

		[$storage1, $rootId] = $this->getStorage(2);
		$rootId = $this->createCacheEntry('', 2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$this->cache->registerMounts($user1, [$mount1]);

		$user1->delete();
		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForFileId($rootId);
		$this->assertEmpty($cachedMounts);
	}

	public function testGetUsedSpaceForUsers(): void {
		$user1 = $this->userManager->get('u1');
		$user2 = $this->userManager->get('u2');

		/** @var Storage $storage1 */
		[$storage1, $rootId] = $this->getStorage(2);
		$folderId = $this->createCacheEntry('files', 2, 100);
		$fileId = $this->createCacheEntry('files/foo', 2, 7);
		$storage1->getCache()->put($folderId, ['size' => 100]);
		$storage1->getCache()->update($fileId, ['size' => 70]);

		$mount1 = $this->getMockBuilder(MountPoint::class)
			->setConstructorArgs([$storage1, '/u1/'])
			->onlyMethods(['getStorageRootId', 'getNumericStorageId'])
			->getMock();

		$mount1->expects($this->any())
			->method('getStorageRootId')
			->willReturn($rootId);

		$mount1->expects($this->any())
			->method('getNumericStorageId')
			->willReturn(2);

		$this->cache->registerMounts($user1, [$mount1]);

		$result = $this->cache->getUsedSpaceForUsers([$user1, $user2]);
		$this->assertEquals(['u1' => 100], $result);
	}


	public function testMigrateMountProvider(): void {
		$user1 = $this->userManager->get('u1');

		[$storage1, $rootId] = $this->getStorage(2);
		$rootId = $this->createCacheEntry('', 2);
		$mount1 = new MountPoint($storage1, '/foo/');
		$this->cache->registerMounts($user1, [$mount1]);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user1);
		$this->assertCount(1, $cachedMounts);
		$this->assertEquals('', $cachedMounts[$this->keyForMount($mount1)]->getMountProvider());

		$mount1 = new MountPoint($storage1, '/foo/', null, null, null, null, 'dummy');
		$this->cache->registerMounts($user1, [$mount1], ['dummy']);

		$this->clearCache();

		$cachedMounts = $this->cache->getMountsForUser($user1);
		$this->assertCount(1, $cachedMounts);
		$this->assertEquals('dummy', $cachedMounts[$this->keyForMount($mount1)]->getMountProvider());
	}
}
