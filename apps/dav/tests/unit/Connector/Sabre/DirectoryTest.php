<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OCA\DAV\Tests\Unit\Connector\Sabre;

use Exception;
use OC\Files\FileInfo;
use OC\Files\Storage\Wrapper\Quota;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\ForbiddenException;
use OCP\Files\InvalidPathException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\StorageNotAvailableException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\Locked;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Test\TestCase;

class TestViewDirectory extends View {
	private $updatables;
	private $deletables;
	private $canRename;

	public function __construct($updatables, $deletables, $canRename = true) {
		$this->updatables = $updatables;
		$this->deletables = $deletables;
		$this->canRename = $canRename;
	}

	public function isUpdatable($path) {
		return $this->updatables[$path];
	}

	public function isCreatable($path) {
		return $this->updatables[$path];
	}

	public function isDeletable($path) {
		return $this->deletables[$path];
	}

	public function rename($path1, $path2) {
		return $this->canRename;
	}

	public function getRelativePath($path): ?string {
		return $path;
	}
}


/**
 * @group DB
 */
class DirectoryTest extends TestCase {

	/** @var View | MockObject */
	private $view;
	/** @var FileInfo | MockObject */
	private $info;

	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->createMock(View::class);
		$this->info = $this->createMock(FileInfo::class);
		$this->info->expects($this->any())
			->method('isReadable')
			->willReturn(true);
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	private function getDir(string $path = '/'): Directory {
		$this->view->expects($this->once())
			->method('getRelativePath')
			->willReturn($path);

		$this->info->expects($this->once())
			->method('getPath')
			->willReturn($path);

		return new Directory($this->view, $this->info);
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 */
	public function testDeleteRootFolderFails() {
		$this->expectException(Forbidden::class);

		$this->info->expects($this->any())
			->method('isDeletable')
			->willReturn(true);
		$this->view->expects($this->never())
			->method('rmdir');
		$dir = $this->getDir();
		$dir->delete();
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 */
	public function testDeleteForbidden() {
		$this->expectException(\OCA\DAV\Connector\Sabre\Exception\Forbidden::class);

		// deletion allowed
		$this->info->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		// but fails
		$this->view->expects($this->once())
			->method('rmdir')
			->with('sub')
			->willThrowException(new ForbiddenException('', true));

		$dir = $this->getDir('sub');
		$dir->delete();
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 */
	public function testDeleteFolderWhenAllowed() {
		// deletion allowed
		$this->info->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		// but fails
		$this->view->expects($this->once())
			->method('rmdir')
			->with('sub')
			->willReturn(true);

		$dir = $this->getDir('sub');
		$dir->delete();
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 */
	public function testDeleteFolderFailsWhenNotAllowed() {
		$this->expectException(Forbidden::class);

		$this->info->expects($this->once())
			->method('isDeletable')
			->willReturn(false);

		$dir = $this->getDir('sub');
		$dir->delete();
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 */
	public function testDeleteFolderThrowsWhenDeletionFailed() {
		$this->expectException(Forbidden::class);

		// deletion allowed
		$this->info->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		// but fails
		$this->view->expects($this->once())
			->method('rmdir')
			->with('sub')
			->willReturn(false);

		$dir = $this->getDir('sub');
		$dir->delete();
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws Locked
	 * @throws NotFound
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testGetChildren() {
		$info1 = $this->createMock(FileInfo::class);
		$info2 = $this->createMock(FileInfo::class);
		$info1->expects($this->any())
			->method('getName')
			->willReturn('first');
		$info1->expects($this->any())
			->method('getEtag')
			->willReturn('abc');
		$info2->expects($this->any())
			->method('getName')
			->willReturn('second');
		$info2->expects($this->any())
			->method('getEtag')
			->willReturn('def');

		$this->view->expects($this->once())
			->method('getDirectoryContent')
			->with('')
			->willReturn([$info1, $info2]);

		$this->view->expects($this->any())
			->method('getRelativePath')
			->willReturn('');

		$dir = new Directory($this->view, $this->info);
		$nodes = $dir->getChildren();

		$this->assertCount(2, $nodes);

		// calling a second time just returns the cached values,
		// does not call getDirectoryContents again
		$dir->getChildren();
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws Locked
	 * @throws NotFound
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testGetChildrenNoPermission() {
		$this->expectException(Forbidden::class);

		$info = $this->createMock(FileInfo::class);
		$info->expects($this->any())
			->method('isReadable')
			->willReturn(false);

		$dir = new Directory($this->view, $info);
		$dir->getChildren();
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws InvalidPath
	 * @throws NotFound
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws Forbidden
	 */
	public function testGetChildNoPermission() {
		$this->expectException(NotFound::class);

		$this->info->expects($this->any())
			->method('isReadable')
			->willReturn(false);

		$dir = new Directory($this->view, $this->info);
		$dir->getChild('test');
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws InvalidPath
	 * @throws NotFound
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable|Forbidden
	 */
	public function testGetChildThrowStorageNotAvailableException() {
		$this->expectException(ServiceUnavailable::class);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->willThrowException(new StorageNotAvailableException());

		$dir = new Directory($this->view, $this->info);
		$dir->getChild('.');
	}


	/**
	 * @throws ContainerExceptionInterface
	 * @throws InvalidPath
	 * @throws NotFound
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable|Forbidden
	 */
	public function testGetChildThrowInvalidPath() {
		$this->expectException(InvalidPath::class);

		$this->view->expects($this->once())
			->method('verifyPath')
			->willThrowException(new InvalidPathException());
		$this->view->expects($this->never())
			->method('getFileInfo');

		$dir = new Directory($this->view, $this->info);
		$dir->getChild('.');
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetQuotaInfoUnlimited() {
		$mountPoint = $this->createMock(IMountPoint::class);
		$storage = $this->createMock(Quota::class);
		$mountPoint->method('getStorage')
			->willReturn($storage);

		$storage->expects($this->any())
			->method('instanceOfStorage')
			->willReturnMap([
				SharedStorage::class => false,
				Quota::class => false,
			]);

		$storage->expects($this->never())
			->method('getQuota');

		$storage->expects($this->once())
			->method('free_space')
			->willReturn(800);

		$this->info->expects($this->once())
			->method('getSize')
			->willReturn(200);

		$this->info->expects($this->once())
			->method('getMountPoint')
			->willReturn($mountPoint);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->willReturn($this->info);

		$mountPoint->method('getMountPoint')
			->willReturn('/user/files/mymountpoint');

		$dir = new Directory($this->view, $this->info);
		$this->assertEquals([200, -3], $dir->getQuotaInfo()); //200 used, unlimited
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetQuotaInfoSpecific() {
		$mountPoint = $this->createMock(IMountPoint::class);
		//$storage = $this->createMock(Quota::class);
		$storage = $this->getMockBuilder(Quota::class)
			->disableOriginalConstructor()
			->getMock();
		$mountPoint->method('getStorage')
			->willReturn($storage);

		$storage->expects($this->exactly(2))
			->method('instanceOfStorage')
			->willReturnMap([
				[SharedStorage::class, false],
				[Quota::class, true],
			]);

		$storage->expects($this->once())
			->method('getQuota')
			->willReturn(1000);

		$storage->expects($this->once())
			->method('free_space')
			->willReturn(800);

		$this->info->expects($this->once())
			->method('getSize')
			->willReturn(200);

		$this->info->expects($this->any())
			->method('getPath')
			->willReturn('/sub');

		$this->info->expects($this->once())
			->method('getMountPoint')
			->willReturn($mountPoint);

		$mountPoint->method('getMountPoint')
			->willReturn('/user/files/mymountpoint');

		$this->view->expects($this->once())
			->method('getFileInfo')
			->willReturn($this->info);

		$dir = new Directory($this->view, $this->info);
		$this->assertEquals([200, 800], $dir->getQuotaInfo()); //200 used, 800 free
	}

	/**
	 * @dataProvider moveFailedProvider
	 * @throws BadRequest
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testMoveFailed(string $source, string $destination, array $updatables, array $deletables) {
		$this->expectException(Forbidden::class);

		$this->moveTest($source, $destination, $updatables, $deletables);
	}

	/**
	 * @dataProvider moveSuccessProvider
	 * @throws BadRequest
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testMoveSuccess(string $source, string $destination, array $updatables, array $deletables) {
		$this->moveTest($source, $destination, $updatables, $deletables);
		$this->addToAssertionCount(1);
	}

	/**
	 * @dataProvider moveFailedInvalidCharsProvider
	 * @throws BadRequest
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 */
	public function testMoveFailedInvalidChars(string $source, string $destination, array $updatables, array $deletables) {
		$this->expectException(InvalidPath::class);

		$this->moveTest($source, $destination, $updatables, $deletables);
	}

	public function moveFailedInvalidCharsProvider(): array {
		return [
			['a/b', 'a/*', ['a' => true, 'a/b' => true, 'a/c*' => false], []],
		];
	}

	public function moveFailedProvider(): array {
		return [
			['a/b', 'a/c', ['a' => false, 'a/b' => false, 'a/c' => false], []],
			['a/b', 'b/b', ['a' => false, 'a/b' => false, 'b' => false, 'b/b' => false], []],
			['a/b', 'b/b', ['a' => false, 'a/b' => true, 'b' => false, 'b/b' => false], []],
			['a/b', 'b/b', ['a' => true, 'a/b' => true, 'b' => false, 'b/b' => false], []],
			['a/b', 'b/b', ['a' => true, 'a/b' => true, 'b' => true, 'b/b' => false], ['a/b' => false]],
			['a/b', 'a/c', ['a' => false, 'a/b' => true, 'a/c' => false], []],
		];
	}

	public function moveSuccessProvider(): array {
		return [
			['a/b', 'b/b', ['a' => true, 'a/b' => true, 'b' => true, 'b/b' => false], ['a/b' => true]],
			// older files with special chars can still be renamed to valid names
			['a/b*', 'b/b', ['a' => true, 'a/b*' => true, 'b' => true, 'b/b' => false], ['a/b*' => true]],
		];
	}

	/**
	 * @throws BadRequest
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 * @throws Exception
	 */
	private function moveTest(string $source, string $destination, array $updatables, array $deletables) {
		$view = new TestViewDirectory($updatables, $deletables);

		$sourceInfo = new FileInfo($source, null, null, [], null);
		$targetInfo = new FileInfo(dirname($destination), null, null, [], null);

		$sourceNode = new Directory($view, $sourceInfo);
		$targetNode = $this->getMockBuilder(Directory::class)
			->onlyMethods(['childExists'])
			->setConstructorArgs([$view, $targetInfo])
			->getMock();
		$targetNode->expects($this->any())->method('childExists')
			->with(basename($destination))
			->willReturn(false);
		$this->assertTrue($targetNode->moveInto(basename($destination), $source, $sourceNode));
	}


	/**
	 * @throws BadRequest
	 * @throws ContainerExceptionInterface
	 * @throws FileLocked
	 * @throws Forbidden
	 * @throws NotFoundExceptionInterface
	 * @throws ServiceUnavailable
	 * @throws \OCA\DAV\Connector\Sabre\Exception\Forbidden
	 * @throws Exception
	 */
	public function testFailingMove() {
		$this->expectException(Forbidden::class);
		$this->expectExceptionMessage('Could not copy directory b, target exists');

		$source = 'a/b';
		$destination = 'c/b';
		$updatables = ['a' => true, 'a/b' => true, 'b' => true, 'c/b' => false];
		$deletables = ['a/b' => true];

		$view = new TestViewDirectory($updatables, $deletables);

		$sourceInfo = new FileInfo($source, null, null, [], null);
		$targetInfo = new FileInfo(dirname($destination), null, null, [], null);

		$sourceNode = new Directory($view, $sourceInfo);
		$targetNode = $this->getMockBuilder(Directory::class)
			->onlyMethods(['childExists'])
			->setConstructorArgs([$view, $targetInfo])
			->getMock();
		$targetNode->expects($this->once())->method('childExists')
			->with(basename($destination))
			->willReturn(true);

		$targetNode->moveInto(basename($destination), $source, $sourceNode);
	}
}
