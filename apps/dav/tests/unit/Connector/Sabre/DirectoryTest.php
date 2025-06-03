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
use OC\Files\Node\Node;
use OC\Files\Storage\Wrapper\Quota;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\Files_Sharing\External\Storage;
use OCP\Constants;
use OCP\Files\ForbiddenException;
use OCP\Files\InvalidPathException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\StorageNotAvailableException;
use PHPUnit\Framework\MockObject\MockObject;
use Test\Traits\UserTrait;

class TestViewDirectory extends View {
	public function __construct(
		private $updatables,
		private $deletables,
		private $canRename = true,
	) {
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

	public function rename($source, $target, array $options = []) {
		return $this->canRename;
	}

	public function getRelativePath($path): ?string {
		return $path;
	}
}


/**
 * @group DB
 */
class DirectoryTest extends \Test\TestCase {
	use UserTrait;

	private View&MockObject $view;
	private FileInfo&MockObject $info;

	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->createMock(View::class);
		$this->info = $this->createMock(FileInfo::class);
		$this->info->method('isReadable')
			->willReturn(true);
		$this->info->method('getType')
			->willReturn(Node::TYPE_FOLDER);
		$this->info->method('getName')
			->willReturn('folder');
		$this->info->method('getPath')
			->willReturn('/admin/files/folder');
		$this->info->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);
	}

	private function getDir(string $path = '/'): Directory {
		$this->view->expects($this->once())
			->method('getRelativePath')
			->willReturn($path);

		$this->info->expects($this->once())
			->method('getPath')
			->willReturn($path);

		return new Directory($this->view, $this->info);
	}


	public function testDeleteRootFolderFails(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->info->expects($this->any())
			->method('isDeletable')
			->willReturn(true);
		$this->view->expects($this->never())
			->method('rmdir');
		$dir = $this->getDir();
		$dir->delete();
	}


	public function testDeleteForbidden(): void {
		$this->expectException(Forbidden::class);

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


	public function testDeleteFolderWhenAllowed(): void {
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


	public function testDeleteFolderFailsWhenNotAllowed(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->info->expects($this->once())
			->method('isDeletable')
			->willReturn(false);

		$dir = $this->getDir('sub');
		$dir->delete();
	}


	public function testDeleteFolderThrowsWhenDeletionFailed(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

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

	public function testGetChildren(): void {
		$info1 = $this->createMock(FileInfo::class);
		$info2 = $this->createMock(FileInfo::class);
		$info1->method('getName')
			->willReturn('first');
		$info1->method('getPath')
			->willReturn('folder/first');
		$info1->method('getEtag')
			->willReturn('abc');
		$info2->method('getName')
			->willReturn('second');
		$info2->method('getPath')
			->willReturn('folder/second');
		$info2->method('getEtag')
			->willReturn('def');

		$this->view->expects($this->once())
			->method('getDirectoryContent')
			->willReturn([$info1, $info2]);

		$this->view->expects($this->any())
			->method('getRelativePath')
			->willReturnCallback(function ($path) {
				return str_replace('/admin/files/', '', $path);
			});

		$this->view->expects($this->any())
			->method('getAbsolutePath')
			->willReturnCallback(function ($path) {
				return Filesystem::normalizePath('/admin/files' . $path);
			});

		$this->overwriteService(View::class, $this->view);

		$dir = new Directory($this->view, $this->info);
		$nodes = $dir->getChildren();

		$this->assertCount(2, $nodes);

		// calling a second time just returns the cached values,
		// does not call getDirectoryContents again
		$dir->getChildren();
	}


	public function testGetChildrenNoPermission(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$info = $this->createMock(FileInfo::class);
		$info->expects($this->any())
			->method('isReadable')
			->willReturn(false);

		$dir = new Directory($this->view, $info);
		$dir->getChildren();
	}


	public function testGetChildNoPermission(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->info->expects($this->any())
			->method('isReadable')
			->willReturn(false);

		$dir = new Directory($this->view, $this->info);
		$dir->getChild('test');
	}


	public function testGetChildThrowStorageNotAvailableException(): void {
		$this->expectException(\Sabre\DAV\Exception\ServiceUnavailable::class);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->willThrowException(new StorageNotAvailableException());

		$dir = new Directory($this->view, $this->info);
		$dir->getChild('.');
	}


	public function testGetChildThrowInvalidPath(): void {
		$this->expectException(InvalidPath::class);

		$this->view->expects($this->once())
			->method('verifyPath')
			->willThrowException(new InvalidPathException());
		$this->view->expects($this->never())
			->method('getFileInfo');

		$dir = new Directory($this->view, $this->info);
		$dir->getChild('.');
	}

	public function testGetQuotaInfoUnlimited(): void {
		$this->createUser('user', 'password');
		self::loginAsUser('user');
		$mountPoint = $this->createMock(IMountPoint::class);
		$storage = $this->createMock(Quota::class);
		$mountPoint->method('getStorage')
			->willReturn($storage);

		$storage->expects($this->any())
			->method('instanceOfStorage')
			->willReturnMap([
				['\OCA\Files_Sharing\SharedStorage', false],
				['\OC\Files\Storage\Wrapper\Quota', false],
				[Storage::class, false],
			]);

		$storage->expects($this->once())
			->method('getOwner')
			->willReturn('user');

		$storage->expects($this->never())
			->method('getQuota');

		$storage->expects($this->once())
			->method('free_space')
			->willReturn(800);

		$this->info->expects($this->any())
			->method('getPath')
			->willReturn('/admin/files/foo');

		$this->info->expects($this->once())
			->method('getSize')
			->willReturn(200);

		$this->info->expects($this->once())
			->method('getMountPoint')
			->willReturn($mountPoint);

		$this->view->expects($this->any())
			->method('getRelativePath')
			->willReturn('/foo');

		$this->info->expects($this->once())
			->method('getInternalPath')
			->willReturn('/foo');

		$mountPoint->method('getMountPoint')
			->willReturn('/user/files/mymountpoint');

		$dir = new Directory($this->view, $this->info);
		$this->assertEquals([200, -3], $dir->getQuotaInfo()); //200 used, unlimited
	}

	public function testGetQuotaInfoSpecific(): void {
		$this->createUser('user', 'password');
		self::loginAsUser('user');
		$mountPoint = $this->createMock(IMountPoint::class);
		$storage = $this->createMock(Quota::class);
		$mountPoint->method('getStorage')
			->willReturn($storage);

		$storage->expects($this->any())
			->method('instanceOfStorage')
			->willReturnMap([
				['\OCA\Files_Sharing\SharedStorage', false],
				['\OC\Files\Storage\Wrapper\Quota', true],
				[Storage::class, false],
			]);

		$storage->expects($this->once())
			->method('getOwner')
			->willReturn('user');

		$storage->expects($this->once())
			->method('getQuota')
			->willReturn(1000);

		$storage->expects($this->once())
			->method('free_space')
			->willReturn(800);

		$this->info->expects($this->once())
			->method('getSize')
			->willReturn(200);

		$this->info->expects($this->once())
			->method('getMountPoint')
			->willReturn($mountPoint);

		$this->info->expects($this->once())
			->method('getInternalPath')
			->willReturn('/foo');

		$mountPoint->method('getMountPoint')
			->willReturn('/user/files/mymountpoint');

		$this->view->expects($this->any())
			->method('getRelativePath')
			->willReturn('/foo');

		$dir = new Directory($this->view, $this->info);
		$this->assertEquals([200, 800], $dir->getQuotaInfo()); //200 used, 800 free
	}

	/**
	 * @dataProvider moveFailedProvider
	 */
	public function testMoveFailed(string $source, string $destination, array $updatables, array $deletables): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->moveTest($source, $destination, $updatables, $deletables);
	}

	/**
	 * @dataProvider moveSuccessProvider
	 */
	public function testMoveSuccess(string $source, string $destination, array $updatables, array $deletables): void {
		$this->moveTest($source, $destination, $updatables, $deletables);
		$this->addToAssertionCount(1);
	}

	/**
	 * @dataProvider moveFailedInvalidCharsProvider
	 */
	public function testMoveFailedInvalidChars(string $source, string $destination, array $updatables, array $deletables): void {
		$this->expectException(InvalidPath::class);

		$this->moveTest($source, $destination, $updatables, $deletables);
	}

	public static function moveFailedInvalidCharsProvider(): array {
		return [
			['a/valid', "a/i\nvalid", ['a' => true, 'a/valid' => true, 'a/c*' => false], []],
		];
	}

	public static function moveFailedProvider(): array {
		return [
			['a/b', 'a/c', ['a' => false, 'a/b' => false, 'a/c' => false], []],
			['a/b', 'b/b', ['a' => false, 'a/b' => false, 'b' => false, 'b/b' => false], []],
			['a/b', 'b/b', ['a' => false, 'a/b' => true, 'b' => false, 'b/b' => false], []],
			['a/b', 'b/b', ['a' => true, 'a/b' => true, 'b' => false, 'b/b' => false], []],
			['a/b', 'b/b', ['a' => true, 'a/b' => true, 'b' => true, 'b/b' => false], ['a/b' => false]],
			['a/b', 'a/c', ['a' => false, 'a/b' => true, 'a/c' => false], []],
		];
	}

	public static function moveSuccessProvider(): array {
		return [
			['a/b', 'b/b', ['a' => true, 'a/b' => true, 'b' => true, 'b/b' => false], ['a/b' => true]],
			// older files with special chars can still be renamed to valid names
			['a/b*', 'b/b', ['a' => true, 'a/b*' => true, 'b' => true, 'b/b' => false], ['a/b*' => true]],
		];
	}

	private function moveTest(string $source, string $destination, array $updatables, array $deletables): void {
		$view = new TestViewDirectory($updatables, $deletables);

		$sourceInfo = new FileInfo($source, null, null, [
			'type' => FileInfo::TYPE_FOLDER,
		], null);
		$targetInfo = new FileInfo(dirname($destination), null, null, [
			'type' => FileInfo::TYPE_FOLDER,
		], null);

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


	public function testFailingMove(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('Could not copy directory b, target exists');

		$source = 'a/b';
		$destination = 'c/b';
		$updatables = ['a' => true, 'a/b' => true, 'b' => true, 'c/b' => false];
		$deletables = ['a/b' => true];

		$view = new TestViewDirectory($updatables, $deletables);

		$sourceInfo = new FileInfo($source, null, null, ['type' => FileInfo::TYPE_FOLDER], null);
		$targetInfo = new FileInfo(dirname($destination), null, null, ['type' => FileInfo::TYPE_FOLDER], null);

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
