<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Files\FileInfo;
use OC\Files\Filesystem;
use OC\Files\Mount\Manager;
use OC\Files\Storage\Common;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\ObjectTree;
use OCP\Files\Mount\IMountManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\Locked;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\INode;
use Test\TestCase;

/**
 * Class ObjectTreeTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre
 */
class ObjectTreeTest extends TestCase {
	public function copyDataProvider(): array {
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
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws Locked
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testCopy(string $sourcePath, string $targetPath, string $targetParent) {
		$view = $this->createMock(View::class);
		$view->expects($this->once())
			->method('verifyPath')
			->with($targetParent)
			->willReturn(true);
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

		/** @var $objectTree ObjectTree */
		$mountManager = Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->copy($sourcePath, $targetPath);
	}

	/**
	 * @dataProvider copyDataProvider
	 * @throws Forbidden
	 * @throws ServiceUnavailable
	 * @throws FileLocked
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 * @throws InvalidPath
	 * @throws Locked
	 * @throws NotFound
	 */
	public function testCopyFailNotCreatable(string $sourcePath, string $targetPath, string $targetParent) {
		$this->expectException(Forbidden::class);

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

		/** @var $objectTree ObjectTree */
		$mountManager = Filesystem::getMountManager();
		$objectTree->init($rootDir, $view, $mountManager);
		$objectTree->copy($sourcePath, $targetPath);
	}

	/**
	 * @dataProvider nodeForPathProvider
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws Locked
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 */
	public function testGetNodeForPath(
		string $inputFileName,
		string $fileInfoQueryPath,
		string $outputFileName,
		string $type,
		bool $enableChunkingHeader
	) {
		if ($enableChunkingHeader) {
			$_SERVER['HTTP_OC_CHUNKED'] = true;
		}

		$rootNode = $this->createMock(Directory::class);
		$mountManager = $this->createMock(Manager::class);
		$view = $this->createMock(View::class);
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->once())
			->method('getType')
			->willReturn($type);
		$fileInfo->expects($this->once())
			->method('getName')
			->willReturn($outputFileName);
		$fileInfo->method('getStorage')
			->willReturn($this->createMock(Common::class));

		$view->expects($this->once())
			->method('getFileInfo')
			->with($fileInfoQueryPath)
			->willReturn($fileInfo);

		$tree = new ObjectTree($rootNode);
		$tree->init($rootNode, $view, $mountManager);

		$node = $tree->getNodeForPath($inputFileName);

		$this->assertNotNull($node);
		$this->assertEquals($outputFileName, $node->getName());

		if ($type === 'file') {
			$this->assertInstanceOf(File::class, $node);
		} else {
			$this->assertInstanceOf(Directory::class, $node);
		}

		unset($_SERVER['HTTP_OC_CHUNKED']);
	}

	public function nodeForPathProvider(): array {
		return [
			// regular file
			[
				'regularfile.txt',
				'regularfile.txt',
				'regularfile.txt',
				'file',
				false
			],
			// regular directory
			[
				'regulardir',
				'regulardir',
				'regulardir',
				'dir',
				false
			],
			// regular file with chunking
			[
				'regularfile.txt',
				'regularfile.txt',
				'regularfile.txt',
				'file',
				true
			],
			// regular directory with chunking
			[
				'regulardir',
				'regulardir',
				'regulardir',
				'dir',
				true
			],
			// file with chunky file name
			[
				'regularfile.txt-chunking-123566789-10-1',
				'regularfile.txt',
				'regularfile.txt',
				'file',
				true
			],
			// regular file in subdir
			[
				'subdir/regularfile.txt',
				'subdir/regularfile.txt',
				'regularfile.txt',
				'file',
				false
			],
			// regular directory in subdir
			[
				'subdir/regulardir',
				'subdir/regulardir',
				'regulardir',
				'dir',
				false
			],
			// file with chunky file name in subdir
			[
				'subdir/regularfile.txt-chunking-123566789-10-1',
				'subdir/regularfile.txt',
				'regularfile.txt',
				'file',
				true
			],
		];
	}


	/**
	 * @throws Locked
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 * @throws Forbidden
	 */
	public function testGetNodeForPathInvalidPath() {
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

		$tree = new ObjectTree($rootNode);
		$tree->init($rootNode, $view, $mountManager);

		$tree->getNodeForPath($path);
	}

	/**
	 * @throws Locked
	 * @throws InvalidPath
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 * @throws Forbidden
	 */
	public function testGetNodeForPathRoot() {
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

		$tree = new ObjectTree($rootNode);
		$tree->init($rootNode, $view, $mountManager);

		$this->assertInstanceOf(INode::class, $tree->getNodeForPath($path));
	}
}
