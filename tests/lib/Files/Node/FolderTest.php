<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Node;

use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheEntry;
use OC\Files\Config\CachedMountInfo;
use OC\Files\FileInfo;
use OC\Files\Mount\Manager;
use OC\Files\Mount\MountPoint;
use OC\Files\Node\File;
use OC\Files\Node\Folder;
use OC\Files\Node\Node;
use OC\Files\Node\Root;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Jail;
use OCP\Constants;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Storage\IStorage;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FolderTest
 *
 * @group DB
 *
 * @package Test\Files\Node
 */
class FolderTest extends NodeTestCase {
	protected function createTestNode($root, $view, $path, array $data = [], $internalPath = '', $storage = null) {
		$view->expects($this->any())
			->method('getRoot')
			->willReturn('');
		if ($data || $internalPath || $storage) {
			return new Folder($root, $view, $path, $this->getFileInfo($data, $internalPath, $storage));
		} else {
			return new Folder($root, $view, $path);
		}
	}

	protected function getNodeClass() {
		return '\OC\Files\Node\Folder';
	}

	protected function getNonExistingNodeClass() {
		return '\OC\Files\Node\NonExistingFolder';
	}

	protected function getViewDeleteMethod() {
		return 'rmdir';
	}

	public function testGetDirectoryContent(): void {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->view->expects($this->any())
			->method('getDirectoryContent')
			->with('/bar/foo')
			->willReturn([
				new FileInfo('/bar/foo/asd', null, 'foo/asd', ['fileid' => 2, 'path' => '/bar/foo/asd', 'name' => 'asd', 'size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain'], null),
				new FileInfo('/bar/foo/qwerty', null, 'foo/qwerty', ['fileid' => 3, 'path' => '/bar/foo/qwerty', 'name' => 'qwerty', 'size' => 200, 'mtime' => 55, 'mimetype' => 'httpd/unix-directory'], null),
			]);
		$this->view->method('getFileInfo')
			->willReturn($this->createMock(FileInfo::class));
		$this->view->method('getRelativePath')
			->willReturn('/bar/foo');

		$node = new Folder($root, $this->view, '/bar/foo');
		$children = $node->getDirectoryListing();
		$this->assertEquals(2, count($children));
		$this->assertInstanceOf('\OC\Files\Node\File', $children[0]);
		$this->assertInstanceOf('\OC\Files\Node\Folder', $children[1]);
		$this->assertEquals('asd', $children[0]->getName());
		$this->assertEquals('qwerty', $children[1]->getName());
		$this->assertEquals(2, $children[0]->getId());
		$this->assertEquals(3, $children[1]->getId());
	}

	public function testGet(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$node = new File($root, $view, '/bar/foo/asd');
		$root->method('get')
			->with('/bar/foo/asd')
			->willReturn($node);

		$parentNode = new Folder($root, $view, '/bar/foo');
		self::assertEquals($node, $parentNode->get('asd'));
	}

	public function testNodeExists(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$child = new Folder($root, $view, '/bar/foo/asd');

		$root->method('get')
			->with('/bar/foo/asd')
			->willReturn($child);

		$node = new Folder($root, $view, '/bar/foo');
		$this->assertTrue($node->nodeExists('asd'));
	}

	public function testNodeExistsNotExists(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$root->method('get')
			->with('/bar/foo/asd')
			->willThrowException(new NotFoundException());

		$node = new Folder($root, $view, '/bar/foo');
		$this->assertFalse($node->nodeExists('asd'));
	}

	public function testNewFolder(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_ALL]));

		$view->method('mkdir')
			->with('/bar/foo/asd')
			->willReturn(true);

		$node = new Folder($root, $view, '/bar/foo');
		$child = new Folder($root, $view, '/bar/foo/asd', null, $node);
		$result = $node->newFolder('asd');
		$this->assertEquals($child, $result);
	}

	public function testNewFolderDeepParent(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/foobar')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_ALL]));

		$view->method('mkdir')
			->with('/foobar/asd/sdf')
			->willReturn(true);

		$node = new Folder($root, $view, '/foobar');
		$child = new Folder($root, $view, '/foobar/asd/sdf', null, null);
		$result = $node->newFolder('asd/sdf');
		$this->assertEquals($child, $result);
	}


	public function testNewFolderNotPermitted(): void {
		$this->expectException(NotPermittedException::class);

		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_READ]));

		$node = new Folder($root, $view, '/bar/foo');
		$node->newFolder('asd');
	}

	public function testNewFile(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_ALL]));

		$view->method('touch')
			->with('/bar/foo/asd')
			->willReturn(true);

		$node = new Folder($root, $view, '/bar/foo');
		$child = new File($root, $view, '/bar/foo/asd', null, $node);
		$result = $node->newFile('asd');
		$this->assertEquals($child, $result);
	}


	public function testNewFileNotPermitted(): void {
		$this->expectException(NotPermittedException::class);

		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_READ]));

		$node = new Folder($root, $view, '/bar/foo');
		$node->newFile('asd');
	}

	public function testGetFreeSpace(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);

		$view->method('free_space')
			->with('/bar/foo')
			->willReturn(100);

		$node = new Folder($root, $view, '/bar/foo');
		$this->assertEquals(100, $node->getFreeSpace());
	}

	public function testSearch(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);
		/** @var Storage\IStorage&MockObject $storage */
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);

		$storage->method('getCache')
			->willReturn($cache);

		$storage->expects($this->atLeastOnce())
			->method('getOwner')
			->with('qwerty')
			->willReturn(false);

		$mount = $this->createMock(IMountPoint::class);
		$mount->expects($this->atLeastOnce())
			->method('getStorage')
			->willReturn($storage);
		$mount->expects($this->atLeastOnce())
			->method('getInternalPath')
			->willReturn('foo');

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo', ['size' => 200, 'mtime' => 55, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo/qwerty', ['size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain']);

		$root->method('getMountsIn')
			->with('/bar/foo')
			->willReturn([]);

		$root->method('getMount')
			->with('/bar/foo')
			->willReturn($mount);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->search('qw');
		$cache->clear();
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
	}

	public function testSearchInRoot(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		/** @var \PHPUnit\Framework\MockObject\MockObject|Storage $storage */
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('test::2');
		$cache = new Cache($storage);

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('files');

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('files', ['size' => 200, 'mtime' => 55, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('files/foo', ['size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain']);

		$root->method('getMountsIn')
			->with('')
			->willReturn([]);

		$root->method('getMount')
			->with('')
			->willReturn($mount);

		$result = $root->search('foo');
		$cache->clear();
		$this->assertEquals(1, count($result));
		$this->assertEquals('/foo', $result[0]->getPath());
	}

	public function testSearchInStorageRoot(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('');

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo', ['size' => 200, 'mtime' => 55, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo/qwerty', ['size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain']);


		$root->method('getMountsIn')
			->with('/bar')
			->willReturn([]);

		$root->method('getMount')
			->with('/bar')
			->willReturn($mount);

		$node = new Folder($root, $view, '/bar');
		$result = $node->search('qw');
		$cache->clear();
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
	}

	public function testSearchSubStorages(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);
		$subStorage = $this->createMock(IStorage::class);
		$subStorage->method('getId')->willReturn('test::2');
		$subCache = new Cache($subStorage);
		$subMount = $this->getMockBuilder(MountPoint::class)->setConstructorArgs([Temporary::class, ''])->getMock();

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('foo');

		$subMount->method('getStorage')
			->willReturn($subStorage);

		$subMount->method('getMountPoint')
			->willReturn('/bar/foo/bar/');

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$subStorage->method('getCache')
			->willReturn($subCache);
		$subStorage->method('getOwner')
			->willReturn('owner');

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo', ['size' => 200, 'mtime' => 55, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo/qwerty', ['size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain']);

		$subCache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$subCache->insert('asd', ['size' => 200, 'mtime' => 55, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$subCache->insert('asd/qwerty', ['size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain']);


		$root->method('getMountsIn')
			->with('/bar/foo')
			->willReturn([$subMount]);

		$root->method('getMount')
			->with('/bar/foo')
			->willReturn($mount);


		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->search('qw');
		$cache->clear();
		$subCache->clear();
		$this->assertEquals(2, count($result));
	}

	public function testIsSubNode(): void {
		$rootFolderMock = $this->createMock(IRootFolder::class);
		$file = new Node($rootFolderMock, $this->view, '/foo/bar');
		$folder = new Folder($rootFolderMock, $this->view, '/foo');
		$this->assertTrue($folder->isSubNode($file));
		$this->assertFalse($folder->isSubNode($folder));

		$file = new Node($rootFolderMock, $this->view, '/foobar');
		$this->assertFalse($folder->isSubNode($file));
	}

	public function testGetById(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$storage = $this->createMock(Storage::class);
		$mount = new MountPoint($storage, '/bar');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => 'foo/qwerty', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$this->userMountCache->expects($this->any())
			->method('getMountsForFileId')
			->with(1)
			->willReturn([new CachedMountInfo(
				$this->user,
				1,
				0,
				'/bar/',
				'test',
				1,
				''
			)]);

		$cache->method('get')
			->with(1)
			->willReturn($fileInfo);

		$root->method('getMountsIn')
			->with('/bar/foo')
			->willReturn([]);

		$manager->method('getMountsByMountProvider')
			->willReturn([$mount]);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->getById(1);
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
	}

	public function testGetByIdMountRoot(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$storage = $this->createMock(Storage::class);
		$mount = new MountPoint($storage, '/bar');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => '', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$this->userMountCache->expects($this->any())
			->method('getMountsForFileId')
			->with(1)
			->willReturn([new CachedMountInfo(
				$this->user,
				1,
				0,
				'/bar/',
				'test',
				1,
				''
			)]);

		$cache->method('get')
			->with(1)
			->willReturn($fileInfo);

		$manager->method('getMountsByMountProvider')
			->willReturn([$mount]);

		$node = new Folder($root, $view, '/bar');
		$result = $node->getById(1);
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar', $result[0]->getPath());
	}

	public function testGetByIdOutsideFolder(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$storage = $this->createMock(Storage::class);
		$mount = new MountPoint($storage, '/bar');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => 'foobar', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$this->userMountCache->expects($this->any())
			->method('getMountsForFileId')
			->with(1)
			->willReturn([new CachedMountInfo(
				$this->user,
				1,
				0,
				'/bar/',
				'test',
				1,
				''
			)]);

		$cache->method('get')
			->with(1)
			->willReturn($fileInfo);

		$manager->method('getMountsByMountProvider')
			->willReturn([$mount]);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->getById(1);
		$this->assertEquals(0, count($result));
	}

	public function testGetByIdMultipleStorages(): void {
		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$storage = $this->createMock(Storage::class);
		$mount1 = new MountPoint($storage, '/bar');
		$mount2 = new MountPoint($storage, '/bar/foo/asd');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => 'foo/qwerty', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$this->userMountCache->method('getMountsForFileId')
			->with(1)
			->willReturn([
				new CachedMountInfo(
					$this->user,
					1,
					0,
					'/bar/',
					'test',
					1,
					''
				),
			]);

		$cache->method('get')
			->with(1)
			->willReturn($fileInfo);

		$manager->method('getMountsByMountProvider')
			->willReturn([$mount1, $mount2]);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->getById(1);
		$this->assertEquals(2, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
		$this->assertEquals('/bar/foo/asd/foo/qwerty', $result[1]->getPath());
	}

	public static function uniqueNameProvider(): array {
		return [
			// input, existing, expected
			['foo', [], 'foo'],
			['foo', ['foo'], 'foo (2)'],
			['foo', ['foo', 'foo (2)'], 'foo (3)'],
		];
	}

	/**
	 * @dataProvider uniqueNameProvider
	 */
	public function testGetUniqueName($name, $existingFiles, $expected): void {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();

		$view->expects($this->any())
			->method('file_exists')
			->willReturnCallback(function ($path) use ($existingFiles, $folderPath) {
				foreach ($existingFiles as $existing) {
					if ($folderPath . '/' . $existing === $path) {
						return true;
					}
				}
				return false;
			});

		$node = new Folder($root, $view, $folderPath);
		$this->assertEquals($expected, $node->getNonExistingName($name));
	}

	public function testRecent(): void {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		$view = $this->getRootViewMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\Node\Root $root */
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\FileInfo $folderInfo */
		$folderInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();

		$baseTime = time();
		$storage = new Temporary();
		$mount = new MountPoint($storage, '');

		$folderInfo->expects($this->any())
			->method('getMountPoint')
			->willReturn($mount);
		$root->method('getMount')
			->willReturn($mount);
		$root->method('getMountsIn')
			->willReturn([]);

		$cache = $storage->getCache();

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('bar', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('bar/foo', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('bar/asd', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$id1 = $cache->put('bar/foo/inside.txt', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => Constants::PERMISSION_ALL,
		]);
		$id2 = $cache->put('bar/foo/old.txt', [
			'storage_mtime' => $baseTime - 100,
			'mtime' => $baseTime - 100,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => Constants::PERMISSION_READ,
		]);
		$cache->put('bar/asd/outside.txt', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => 'text/plain',
			'size' => 3,
		]);
		$id3 = $cache->put('bar/foo/older.txt', [
			'storage_mtime' => $baseTime - 600,
			'mtime' => $baseTime - 600,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => Constants::PERMISSION_ALL,
		]);

		$node = new Folder($root, $view, $folderPath, $folderInfo);


		$nodes = $node->getRecent(5);
		$ids = array_map(function (Node $node) {
			return (int)$node->getId();
		}, $nodes);
		$this->assertEquals([$id1, $id2, $id3], $ids);
	}

	public function testRecentFolder(): void {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		$view = $this->getRootViewMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\Node\Root $root */
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\FileInfo $folderInfo */
		$folderInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();

		$baseTime = time();
		$storage = new Temporary();
		$mount = new MountPoint($storage, '');

		$folderInfo->expects($this->any())
			->method('getMountPoint')
			->willReturn($mount);

		$root->method('getMount')
			->willReturn($mount);
		$root->method('getMountsIn')
			->willReturn([]);

		$cache = $storage->getCache();

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('bar', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('bar/foo', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$id1 = $cache->put('bar/foo/folder', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => \OCP\Files\FileInfo::MIMETYPE_FOLDER,
			'size' => 3,
			'permissions' => 0,
		]);
		$id2 = $cache->put('bar/foo/folder/bar.txt', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => 'text/plain',
			'size' => 3,
			'parent' => $id1,
			'permissions' => Constants::PERMISSION_ALL,
		]);
		$id3 = $cache->put('bar/foo/folder/asd.txt', [
			'storage_mtime' => $baseTime - 100,
			'mtime' => $baseTime - 100,
			'mimetype' => 'text/plain',
			'size' => 3,
			'parent' => $id1,
			'permissions' => Constants::PERMISSION_ALL,
		]);

		$node = new Folder($root, $view, $folderPath, $folderInfo);


		$nodes = $node->getRecent(5);
		$ids = array_map(function (Node $node) {
			return (int)$node->getId();
		}, $nodes);
		$this->assertEquals([$id2, $id3], $ids);
		$this->assertEquals($baseTime, $nodes[0]->getMTime());
		$this->assertEquals($baseTime - 100, $nodes[1]->getMTime());
	}

	public function testRecentJail(): void {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		$view = $this->getRootViewMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\Node\Root $root */
		$root = $this->getMockBuilder(Root::class)
			->onlyMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\FileInfo $folderInfo */
		$folderInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();

		$baseTime = time();
		$storage = new Temporary();
		$jail = new Jail([
			'storage' => $storage,
			'root' => 'folder',
		]);
		$mount = new MountPoint($jail, '/bar/foo');

		$folderInfo->expects($this->any())
			->method('getMountPoint')
			->willReturn($mount);
		$root->method('getMount')
			->willReturn($mount);
		$root->method('getMountsIn')
			->willReturn([]);

		$cache = $storage->getCache();

		$cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('folder', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$id1 = $cache->put('folder/inside.txt', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => Constants::PERMISSION_ALL,
		]);

		$cache->put('outside.txt', [
			'storage_mtime' => $baseTime - 100,
			'mtime' => $baseTime - 100,
			'mimetype' => 'text/plain',
			'size' => 3,
		]);

		$node = new Folder($root, $view, $folderPath, $folderInfo);

		$nodes = $node->getRecent(5);
		$ids = array_map(function (Node $node) {
			return (int)$node->getId();
		}, $nodes);
		$this->assertEquals([$id1], $ids);
	}

	public static function offsetLimitProvider(): array {
		return [
			[0, 10, ['/bar/foo/foo1', '/bar/foo/foo2', '/bar/foo/foo3', '/bar/foo/foo4', '/bar/foo/sub1/foo5', '/bar/foo/sub1/foo6', '/bar/foo/sub2/foo7', '/bar/foo/sub2/foo8'], []],
			[0, 5, ['/bar/foo/foo1', '/bar/foo/foo2', '/bar/foo/foo3', '/bar/foo/foo4', '/bar/foo/sub1/foo5'], []],
			[0, 2, ['/bar/foo/foo1', '/bar/foo/foo2'], []],
			[3, 2, ['/bar/foo/foo4', '/bar/foo/sub1/foo5'], []],
			[3, 5, ['/bar/foo/foo4', '/bar/foo/sub1/foo5', '/bar/foo/sub1/foo6', '/bar/foo/sub2/foo7', '/bar/foo/sub2/foo8'], []],
			[5, 2, ['/bar/foo/sub1/foo6', '/bar/foo/sub2/foo7'], []],
			[6, 2, ['/bar/foo/sub2/foo7', '/bar/foo/sub2/foo8'], []],
			[7, 2, ['/bar/foo/sub2/foo8'], []],
			[10, 2, [], []],
			[0, 5, ['/bar/foo/sub2/foo7', '/bar/foo/foo1', '/bar/foo/sub1/foo5', '/bar/foo/foo2', '/bar/foo/foo3'], [new SearchOrder(ISearchOrder::DIRECTION_ASCENDING, 'mtime')]],
			[3, 2, ['/bar/foo/foo2', '/bar/foo/foo3'], [new SearchOrder(ISearchOrder::DIRECTION_ASCENDING, 'mtime')]],
			[0, 5, ['/bar/foo/sub1/foo5', '/bar/foo/sub1/foo6', '/bar/foo/sub2/foo7', '/bar/foo/foo1', '/bar/foo/foo2'], [
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'size'),
				new SearchOrder(ISearchOrder::DIRECTION_ASCENDING, 'mtime')
			]],
		];
	}

	/**
	 * @dataProvider offsetLimitProvider
	 * @param int $offset
	 * @param int $limit
	 * @param string[] $expectedPaths
	 * @param ISearchOrder[] $ordering
	 * @throws NotFoundException
	 * @throws \OCP\Files\InvalidPathException
	 */
	public function testSearchSubStoragesLimitOffset(int $offset, int $limit, array $expectedPaths, array $ordering): void {
		if (!$ordering) {
			$ordering = [new SearchOrder(ISearchOrder::DIRECTION_ASCENDING, 'fileid')];
		}

		$manager = $this->createMock(Manager::class);
		$view = $this->getRootViewMock();
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);
		$subStorage1 = $this->createMock(IStorage::class);
		$subStorage1->method('getId')->willReturn('test::2');
		$subCache1 = new Cache($subStorage1);
		$subMount1 = $this->getMockBuilder(MountPoint::class)->setConstructorArgs([Temporary::class, ''])->getMock();
		$subStorage2 = $this->createMock(IStorage::class);
		$subStorage2->method('getId')->willReturn('test::3');
		$subCache2 = new Cache($subStorage2);
		$subMount2 = $this->getMockBuilder(MountPoint::class)->setConstructorArgs([Temporary::class, ''])->getMock();

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('foo');

		$subMount1->method('getStorage')
			->willReturn($subStorage1);

		$subMount1->method('getMountPoint')
			->willReturn('/bar/foo/sub1/');

		$storage->method('getCache')
			->willReturn($cache);
		$storage->method('getOwner')
			->willReturn('owner');

		$subStorage1->method('getCache')
			->willReturn($subCache1);
		$subStorage1->method('getOwner')
			->willReturn('owner');

		$subMount2->method('getStorage')
			->willReturn($subStorage2);

		$subMount2->method('getMountPoint')
			->willReturn('/bar/foo/sub2/');

		$subStorage2->method('getCache')
			->willReturn($subCache2);
		$subStorage2->method('getOwner')
			->willReturn('owner');


		$cache->insert('', ['size' => 0, 'mtime' => 10, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo', ['size' => 0, 'mtime' => 10, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo/foo1', ['size' => 200, 'mtime' => 10, 'mimetype' => 'text/plain']);
		$cache->insert('foo/foo2', ['size' => 200, 'mtime' => 20, 'mimetype' => 'text/plain']);
		$cache->insert('foo/foo3', ['size' => 200, 'mtime' => 30, 'mimetype' => 'text/plain']);
		$cache->insert('foo/foo4', ['size' => 200, 'mtime' => 40, 'mimetype' => 'text/plain']);

		$subCache1->insert('', ['size' => 0, 'mtime' => 10, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$subCache1->insert('foo5', ['size' => 300, 'mtime' => 15, 'mimetype' => 'text/plain']);
		$subCache1->insert('foo6', ['size' => 300, 'mtime' => 50, 'mimetype' => 'text/plain']);

		$subCache2->insert('', ['size' => 0, 'mtime' => 10, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$subCache2->insert('foo7', ['size' => 200, 'mtime' => 5, 'mimetype' => 'text/plain']);
		$subCache2->insert('foo8', ['size' => 200, 'mtime' => 60, 'mimetype' => 'text/plain']);

		$root->method('getMountsIn')
			->with('/bar/foo')
			->willReturn([$subMount1, $subMount2]);

		$root->method('getMount')
			->with('/bar/foo')
			->willReturn($mount);

		$node = new Folder($root, $view, '/bar/foo');
		$comparison = new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%foo%');
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
			$comparison,
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', ICacheEntry::DIRECTORY_MIMETYPE)]),
		]);
		$query = new SearchQuery($operator, $limit, $offset, $ordering);
		$result = $node->search($query);
		$cache->clear();
		$subCache1->clear();
		$subCache2->clear();
		$ids = array_map(function (Node $info) {
			return $info->getPath();
		}, $result);
		$this->assertEquals($expectedPaths, $ids);
	}
}
