<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Files\FileInfo;
use OC\Files\Filesystem;
use OC\Files\Mount\Manager;
use OC\Files\Storage\Common;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\ObjectTree;
use OCP\Files\Mount\IMountManager;

/**
 * Class ObjectTreeTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre
 */
class ObjectTreeTest extends \Test\TestCase {
	public static function copyDataProvider(): array {
		return [
			// copy into same dir
			['a', 'b', ''],
			// copy into same dir
			['a/a', 'a/b', 'a'],
			// copy into another dir
			['a', 'sub/a', 'sub'],
		];
	}

	/**
	 * @dataProvider copyDataProvider
	 */
	public function testCopy(string $sourcePath, string $targetPath, string $targetParent): void {
		$view = $this->createMock(View::class);
		$view->expects($this->once())
			->method('verifyPath')
			->with($targetParent);
		$view->expects($this->once())
			->method('file_exists')
			->with($targetPath)
			->willReturn(false);
		$view->expects($this->once())
			->method('copy')
			->with($sourcePath, $targetPath)
			->willReturn(true);

		$info = $this->createMock(FileInfo::class);
		$info->expects($this->once())
			->method('isCreatable')
			->willReturn(true);

		$view->expects($this->once())
			->method('getFileInfo')
			->with($targetParent === '' ? '.' : $targetParent)
			->willReturn($info);

		$rootDir = new Directory($view, $info);
		$objectTree = $this->getMockBuilder(ObjectTree::class)
			->onlyMethods(['nodeExists', 'getNodeForPath'])
			->setConstructorArgs([$rootDir, $view])
			->getMock();

		$objectTree->expects($this->once())
			->method('getNodeForPath')
			->with($this->identicalTo($sourcePath))
			->willReturn(false);

		/** @var ObjectTree $objectTree */
		$mountManager = Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->copy($sourcePath, $targetPath);
	}

	/**
	 * @dataProvider copyDataProvider
	 */
	public function testCopyFailNotCreatable($sourcePath, $targetPath, $targetParent): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$view = $this->createMock(View::class);
		$view->expects($this->never())
			->method('verifyPath');
		$view->expects($this->once())
			->method('file_exists')
			->with($targetPath)
			->willReturn(false);
		$view->expects($this->never())
			->method('copy');

		$info = $this->createMock(FileInfo::class);
		$info->expects($this->once())
			->method('isCreatable')
			->willReturn(false);

		$view->expects($this->once())
			->method('getFileInfo')
			->with($targetParent === '' ? '.' : $targetParent)
			->willReturn($info);

		$rootDir = new Directory($view, $info);
		$objectTree = $this->getMockBuilder(ObjectTree::class)
			->onlyMethods(['nodeExists', 'getNodeForPath'])
			->setConstructorArgs([$rootDir, $view])
			->getMock();

		$objectTree->expects($this->never())
			->method('getNodeForPath');

		/** @var ObjectTree $objectTree */
		$mountManager = Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->copy($sourcePath, $targetPath);
	}

	/**
	 * @dataProvider nodeForPathProvider
	 */
	public function testGetNodeForPath(
		string $inputFileName,
		string $fileInfoQueryPath,
		string $outputFileName,
		string $type,
	): void {
		$rootNode = $this->createMock(Directory::class);
		$mountManager = $this->createMock(Manager::class);
		$view = $this->createMock(View::class);
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('getType')
			->willReturn($type);
		$fileInfo->method('getName')
			->willReturn($outputFileName);
		$fileInfo->method('getStorage')
			->willReturn($this->createMock(Common::class));

		$view->method('getFileInfo')
			->with($fileInfoQueryPath)
			->willReturn($fileInfo);

		$tree = new ObjectTree();
		$tree->init($rootNode, $view, $mountManager);

		$node = $tree->getNodeForPath($inputFileName);

		$this->assertNotNull($node);
		$this->assertEquals($outputFileName, $node->getName());

		if ($type === 'file') {
			$this->assertInstanceOf(File::class, $node);
		} else {
			$this->assertInstanceOf(Directory::class, $node);
		}
	}

	public static function nodeForPathProvider(): array {
		return [
			// regular file
			[
				'regularfile.txt',
				'regularfile.txt',
				'regularfile.txt',
				'file',
			],
			// regular directory
			[
				'regulardir',
				'regulardir',
				'regulardir',
				'dir',
			],
			// regular file in subdir
			[
				'subdir/regularfile.txt',
				'subdir/regularfile.txt',
				'regularfile.txt',
				'file',
			],
			// regular directory in subdir
			[
				'subdir/regulardir',
				'subdir/regulardir',
				'regulardir',
				'dir',
			],
		];
	}


	public function testGetNodeForPathInvalidPath(): void {
		$this->expectException(InvalidPath::class);

		$path = '/foo\bar';


		$storage = new Temporary([]);

		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['resolvePath'])
			->getMock();
		$view->expects($this->once())
			->method('resolvePath')
			->willReturnCallback(function ($path) use ($storage) {
				return [$storage, ltrim($path, '/')];
			});

		$rootNode = $this->createMock(Directory::class);
		$mountManager = $this->createMock(IMountManager::class);

		$tree = new ObjectTree();
		$tree->init($rootNode, $view, $mountManager);

		$tree->getNodeForPath($path);
	}

	public function testGetNodeForPathRoot(): void {
		$path = '/';


		$storage = new Temporary([]);

		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['resolvePath'])
			->getMock();
		$view->expects($this->any())
			->method('resolvePath')
			->willReturnCallback(function ($path) use ($storage) {
				return [$storage, ltrim($path, '/')];
			});

		$rootNode = $this->createMock(Directory::class);
		$mountManager = $this->createMock(IMountManager::class);

		$tree = new ObjectTree();
		$tree->init($rootNode, $view, $mountManager);

		$this->assertInstanceOf('\Sabre\DAV\INode', $tree->getNodeForPath($path));
	}
}
