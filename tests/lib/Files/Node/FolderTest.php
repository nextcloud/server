<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheEntry;
use OC\Files\Config\CachedMountInfo;
use OC\Files\FileInfo;
use OC\Files\Mount\Manager;
use OC\Files\Mount\MountPoint;
use OC\Files\Node\Folder;
use OC\Files\Node\Node;
use OC\Files\Node\Root;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Jail;
use OC\Files\View;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Storage;

/**
 * Class FolderTest
 *
 * @group DB
 *
 * @package Test\Files\Node
 */
class FolderTest extends NodeTest {
	protected function createTestNode($root, $view, $path) {
		return new Folder($root, $view, $path);
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

	public function testGetDirectoryContent() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$view->expects($this->any())
			->method('getDirectoryContent')
			->with('/bar/foo')
			->willReturn([
				new FileInfo('/bar/foo/asd', null, 'foo/asd', ['fileid' => 2, 'path' => '/bar/foo/asd', 'name' => 'asd', 'size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain'], null),
				new FileInfo('/bar/foo/qwerty', null, 'foo/qwerty', ['fileid' => 3, 'path' => '/bar/foo/qwerty', 'name' => 'qwerty', 'size' => 200, 'mtime' => 55, 'mimetype' => 'httpd/unix-directory'], null),
			]);

		$node = new Folder($root, $view, '/bar/foo');
		$children = $node->getDirectoryListing();
		$this->assertEquals(2, count($children));
		$this->assertInstanceOf('\OC\Files\Node\File', $children[0]);
		$this->assertInstanceOf('\OC\Files\Node\Folder', $children[1]);
		$this->assertEquals('asd', $children[0]->getName());
		$this->assertEquals('qwerty', $children[1]->getName());
		$this->assertEquals(2, $children[0]->getId());
		$this->assertEquals(3, $children[1]->getId());
	}

	public function testGet() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$root->method('get')
			->with('/bar/foo/asd');

		$node = new Folder($root, $view, '/bar/foo');
		$node->get('asd');
	}

	public function testNodeExists() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
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

	public function testNodeExistsNotExists() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$root->method('get')
			->with('/bar/foo/asd')
			->will($this->throwException(new NotFoundException()));

		$node = new Folder($root, $view, '/bar/foo');
		$this->assertFalse($node->nodeExists('asd'));
	}

	public function testNewFolder() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => \OCP\Constants::PERMISSION_ALL]));

		$view->method('mkdir')
			->with('/bar/foo/asd')
			->willReturn(true);

		$node = new Folder($root, $view, '/bar/foo');
		$child = new Folder($root, $view, '/bar/foo/asd');
		$result = $node->newFolder('asd');
		$this->assertEquals($child, $result);
	}


	public function testNewFolderNotPermitted() {
		$this->expectException(\OCP\Files\NotPermittedException::class);

		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => \OCP\Constants::PERMISSION_READ]));

		$node = new Folder($root, $view, '/bar/foo');
		$node->newFolder('asd');
	}

	public function testNewFile() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => \OCP\Constants::PERMISSION_ALL]));

		$view->method('touch')
			->with('/bar/foo/asd')
			->willReturn(true);

		$node = new Folder($root, $view, '/bar/foo');
		$child = new \OC\Files\Node\File($root, $view, '/bar/foo/asd');
		$result = $node->newFile('asd');
		$this->assertEquals($child, $result);
	}


	public function testNewFileNotPermitted() {
		$this->expectException(\OCP\Files\NotPermittedException::class);

		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);

		$view->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => \OCP\Constants::PERMISSION_READ]));

		$node = new Folder($root, $view, '/bar/foo');
		$node->newFile('asd');
	}

	public function testGetFreeSpace() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);

		$view->method('free_space')
			->with('/bar/foo')
			->willReturn(100);

		$node = new Folder($root, $view, '/bar/foo');
		$this->assertEquals(100, $node->getFreeSpace());
	}

	public function testSearch() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);
		/** @var Storage\IStorage $storage */
		$storage = $this->createMock(Storage\IStorage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);

		$storage->method('getCache')
			->willReturn($cache);

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('foo');

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

	public function testSearchInRoot() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		/** @var \PHPUnit\Framework\MockObject\MockObject|Storage $storage */
		$storage = $this->createMock(Storage::class);
		$storage->method('getId')->willReturn('test::2');
		$cache = new Cache($storage);

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('files');

		$storage->method('getCache')
			->willReturn($cache);

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

	public function testSearchInStorageRoot() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->method('getUser')
			->willReturn($this->user);
		$storage = $this->createMock(Storage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);

		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getInternalPath')
			->willReturn('');

		$storage->method('getCache')
			->willReturn($cache);

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

	public function testSearchSubStorages() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$storage = $this->createMock(Storage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);
		$subStorage = $this->createMock(Storage::class);
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

		$subStorage->method('getCache')
			->willReturn($subCache);

		$cache->insert('foo', ['size' => 200, 'mtime' => 55, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->insert('foo/qwerty', ['size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain']);

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

	public function testIsSubNode() {
		$file = new Node(null, null, '/foo/bar');
		$folder = new Folder(null, null, '/foo');
		$this->assertTrue($folder->isSubNode($file));
		$this->assertFalse($folder->isSubNode($folder));

		$file = new Node(null, null, '/foobar');
		$this->assertFalse($folder->isSubNode($file));
	}

	public function testGetById() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$storage = $this->createMock(\OC\Files\Storage\Storage::class);
		$mount = new MountPoint($storage, '/bar');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => 'foo/qwerty', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);

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

		$root->method('getMount')
			->with('/bar/foo')
			->willReturn($mount);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->getById(1);
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
	}

	public function testGetByIdMountRoot() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$storage = $this->createMock(\OC\Files\Storage\Storage::class);
		$mount = new MountPoint($storage, '/bar');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => '', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);

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

		$root->method('getMount')
			->with('/bar')
			->willReturn($mount);

		$node = new Folder($root, $view, '/bar');
		$result = $node->getById(1);
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar', $result[0]->getPath());
	}

	public function testGetByIdOutsideFolder() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$storage = $this->createMock(\OC\Files\Storage\Storage::class);
		$mount = new MountPoint($storage, '/bar');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => 'foobar', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);

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

		$root->method('getMount')
			->with('/bar/foo')
			->willReturn($mount);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->getById(1);
		$this->assertEquals(0, count($result));
	}

	public function testGetByIdMultipleStorages() {
		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$storage = $this->createMock(\OC\Files\Storage\Storage::class);
		$mount1 = new MountPoint($storage, '/bar');
		$mount2 = new MountPoint($storage, '/bar/foo/asd');
		$storage->method('getId')->willReturn('');
		$cache = $this->getMockBuilder(Cache::class)->setConstructorArgs([$storage])->getMock();

		$fileInfo = new CacheEntry(['path' => 'foo/qwerty', 'mimetype' => 'text/plain'], null);

		$storage->method('getCache')
			->willReturn($cache);

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

		$storage->method('getCache')
			->willReturn($cache);

		$cache->method('get')
			->with(1)
			->willReturn($fileInfo);

		$root->method('getMountsIn')
			->with('/bar/foo')
			->willReturn([$mount2]);

		$root->method('getMount')
			->with('/bar/foo')
			->willReturn($mount1);

		$node = new Folder($root, $view, '/bar/foo');
		$result = $node->getById(1);
		$this->assertEquals(2, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
		$this->assertEquals('/bar/foo/asd/foo/qwerty', $result[1]->getPath());
	}

	public function uniqueNameProvider() {
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
	public function testGetUniqueName($name, $existingFiles, $expected) {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
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

	public function testRecent() {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\Node\Root $root */
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\FileInfo $folderInfo */
		$folderInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();

		$baseTime = 1000;
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

		$id1 = $cache->put('bar/foo/inside.txt', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => \OCP\Constants::PERMISSION_ALL,
		]);
		$id2 = $cache->put('bar/foo/old.txt', [
			'storage_mtime' => $baseTime - 100,
			'mtime' => $baseTime - 100,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => \OCP\Constants::PERMISSION_READ,
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
			'permissions' => \OCP\Constants::PERMISSION_ALL,
		]);

		$node = new Folder($root, $view, $folderPath, $folderInfo);


		$nodes = $node->getRecent(5);
		$ids = array_map(function (Node $node) {
			return (int)$node->getId();
		}, $nodes);
		$this->assertEquals([$id1, $id2, $id3], $ids);
	}

	public function testRecentFolder() {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\Node\Root $root */
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\FileInfo $folderInfo */
		$folderInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();

		$baseTime = 1000;
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
			'permissions' => \OCP\Constants::PERMISSION_ALL,
		]);
		$id3 = $cache->put('bar/foo/folder/asd.txt', [
			'storage_mtime' => $baseTime - 100,
			'mtime' => $baseTime - 100,
			'mimetype' => 'text/plain',
			'size' => 3,
			'parent' => $id1,
			'permissions' => \OCP\Constants::PERMISSION_ALL,
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

	public function testRecentJail() {
		$manager = $this->createMock(Manager::class);
		$folderPath = '/bar/foo';
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\Node\Root $root */
		$root = $this->getMockBuilder(Root::class)
			->setMethods(['getUser', 'getMountsIn', 'getMount'])
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		/** @var \PHPUnit\Framework\MockObject\MockObject|\OC\Files\FileInfo $folderInfo */
		$folderInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();

		$baseTime = 1000;
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

		$id1 = $cache->put('folder/inside.txt', [
			'storage_mtime' => $baseTime,
			'mtime' => $baseTime,
			'mimetype' => 'text/plain',
			'size' => 3,
			'permissions' => \OCP\Constants::PERMISSION_ALL,
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

	public function offsetLimitProvider() {
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
	public function testSearchSubStoragesLimitOffset(int $offset, int $limit, array $expectedPaths, array $ordering) {
		if (!$ordering) {
			$ordering = [new SearchOrder(ISearchOrder::DIRECTION_ASCENDING, 'fileid')];
		}

		$manager = $this->createMock(Manager::class);
		/**
		 * @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject $view
		 */
		$view = $this->createMock(View::class);
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$manager, $view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher])
			->getMock();
		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$storage = $this->createMock(Storage::class);
		$storage->method('getId')->willReturn('test::1');
		$cache = new Cache($storage);
		$subStorage1 = $this->createMock(Storage::class);
		$subStorage1->method('getId')->willReturn('test::2');
		$subCache1 = new Cache($subStorage1);
		$subMount1 = $this->getMockBuilder(MountPoint::class)->setConstructorArgs([Temporary::class, ''])->getMock();
		$subStorage2 = $this->createMock(Storage::class);
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

		$subStorage1->method('getCache')
			->willReturn($subCache1);

		$subMount2->method('getStorage')
			->willReturn($subStorage2);

		$subMount2->method('getMountPoint')
			->willReturn('/bar/foo/sub2/');

		$subStorage2->method('getCache')
			->willReturn($subCache2);

		$cache->insert('foo/foo1', ['size' => 200, 'mtime' => 10, 'mimetype' => 'text/plain']);
		$cache->insert('foo/foo2', ['size' => 200, 'mtime' => 20, 'mimetype' => 'text/plain']);
		$cache->insert('foo/foo3', ['size' => 200, 'mtime' => 30, 'mimetype' => 'text/plain']);
		$cache->insert('foo/foo4', ['size' => 200, 'mtime' => 40, 'mimetype' => 'text/plain']);

		$subCache1->insert('foo5', ['size' => 300, 'mtime' => 15, 'mimetype' => 'text/plain']);
		$subCache1->insert('foo6', ['size' => 300, 'mtime' => 50, 'mimetype' => 'text/plain']);

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
		$query = new SearchQuery($comparison, $limit, $offset, $ordering);
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
