<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Home;
use OC\Files\Storage\Local;
use OC\Files\Storage\Wrapper\Jail;
use OCA\AdminAudit\Actions\Files;
use OCA\AdminAudit\IAuditLogger;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\Storage\IStorage;
use OCA\Files_Sharing\SharedStorage;
use OCP\IConfig;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FilesTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private Files $files;
	private File&MockObject $source;
	private File&MockObject $target;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->files = new Files($this->logger);

		$this->source = $this->createMock(File::class);
		$this->source->method('getId')->willReturn(41);
		$this->source->method('getPath')->willReturn('/alice/files/a.txt');
		$this->source->method('getInternalPath')->willReturn('files/a.txt');
		$this->source->method('getStorage')->willReturn($this->createHomeStorage());
		$this->target = $this->createMock(File::class);
		$this->target->method('getId')->willReturn(42);
		$this->target->method('getPath')->willReturn('/alice/files/b.txt');
		$this->target->method('getInternalPath')->willReturn('files/b.txt');
		$this->target->method('getStorage')->willReturn($this->createHomeStorage());
	}

	private function expectInfo(string $message, string $operation, array $params): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation, 'params' => $params]);
	}

	public function testRead(): void {
		$this->expectInfo('File with id "42" accessed: "/alice/files/b.txt"', 'files.file.read', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->read(new BeforeNodeReadEvent($this->target));
	}

	public function testRename(): void {
		$this->expectInfo('File renamed with id "42" from "/alice/files/a.txt" to "/alice/files/b.txt"', 'files.file.renamed', ['newid' => 42, 'oldpath' => '/alice/files/a.txt', 'newpath' => '/alice/files/b.txt']);
		$this->files->afterRename(new NodeRenamedEvent($this->source, $this->target));
	}

	public function testCreate(): void {
		$this->expectInfo('File with id "42" created: "/alice/files/b.txt"', 'files.file.created', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->create(new NodeCreatedEvent($this->target));
	}

	public function testCopy(): void {
		$this->expectInfo('File id copied from: "41" to "42", path from "/alice/files/a.txt" to "/alice/files/b.txt"', 'files.file.copied', ['oldid' => 41, 'newid' => 42, 'oldpath' => '/alice/files/a.txt', 'newpath' => '/alice/files/b.txt']);
		$this->files->copy(new NodeCopiedEvent($this->source, $this->target));
	}

	public function testWrite(): void {
		$this->expectInfo('File with id "42" written to: "/alice/files/b.txt"', 'files.file.written', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->write(new NodeWrittenEvent($this->target));
	}

	public function testDelete(): void {
		$this->expectInfo('File with id "42" deleted: "/alice/files/b.txt"', 'files.file.deleted', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->delete(new BeforeNodeDeletedEvent($this->target));
	}

	private function createHomeStorage(): IHomeStorage&MockObject {
		$storage = $this->createMock(IHomeStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === IHomeStorage::class);
		return $storage;
	}

	private function createSharedStorage(string $sharePath, ?IStorage $sourceStorage = null, string $shareRoot = ''): SharedStorage&MockObject {
		$sourceStorage ??= $this->createHomeStorage();
		$shareNode = $this->createMock(Folder::class);
		$shareNode->method('getPath')->willReturn($sharePath);
		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($shareNode);
		$storage = $this->createMock(SharedStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(
			static fn (string $class): bool => in_array($class, [ISharedStorage::class, Jail::class], true)
		);
		$storage->method('getShare')->willReturn($share);
		$storage->method('getUnjailedPath')->willReturnCallback(static fn (string $path): string => trim($shareRoot . '/' . $path, '/'));
		$storage->method('getUnjailedStorage')->willReturn($sourceStorage);
		return $storage;
	}

	/**
	 * Storage of a group folder: a jail on the "files" folder of a local storage in __groupfolders/<id>
	 */
	private function createGroupFolderStorage(int $folderId): Jail&MockObject {
		$dataDirectory = rtrim(Server::get(IConfig::class)->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data'), '/');
		$local = $this->createMock(Local::class);
		$local->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === Local::class);
		$local->method('getSourcePath')->willReturnCallback(static fn (string $path): string => $dataDirectory . '/__groupfolders/' . $folderId . '/' . $path);
		$jail = $this->createMock(Jail::class);
		$jail->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === Jail::class);
		$jail->method('getUnjailedPath')->willReturnCallback(static fn (string $path): string => trim('files/' . $path, '/'));
		$jail->method('getUnjailedStorage')->willReturn($local);
		return $jail;
	}

	private function createFile(int $id, string $path, string $internalPath, IStorage $storage): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getPath')->willReturn($path);
		$file->method('getInternalPath')->willReturn($internalPath);
		$file->method('getStorage')->willReturn($storage);
		return $file;
	}

	private function createSharedFile(int $id, string $path, string $internalPath): File&MockObject {
		return $this->createFile($id, $path, $internalPath, $this->createSharedStorage('/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3'));
	}

	public function testWriteThroughShare(): void {
		$file = $this->createSharedFile(42, '/alice/files/myfolder3/file1.xlsx', 'file1.xlsx');

		$this->expectInfo('File with id "42" written to: "/alice/files/myfolder3/file1.xlsx"', 'files.file.written', [
			'id' => 42,
			'path' => '/alice/files/myfolder3/file1.xlsx',
			'ownerPath' => '/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3/file1.xlsx',
		]);
		$this->files->write(new NodeWrittenEvent($file));
	}

	public function testReadShareRoot(): void {
		$file = $this->createSharedFile(42, '/alice/files/myfolder3', '');

		$this->expectInfo('File with id "42" accessed: "/alice/files/myfolder3"', 'files.file.read', [
			'id' => 42,
			'path' => '/alice/files/myfolder3',
			'ownerPath' => '/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3',
		]);
		$this->files->read(new BeforeNodeReadEvent($file));
	}

	public function testDeleteThroughShareWithMissingShareNode(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willThrowException(new NotFoundException());
		$failedStorage = $this->createMock(FailedStorage::class);
		$failedStorage->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === FailedStorage::class);
		$storage = $this->createMock(SharedStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => in_array($class, [ISharedStorage::class, Jail::class], true));
		$storage->method('getShare')->willReturn($share);
		$storage->method('getUnjailedPath')->willReturnArgument(0);
		$storage->method('getUnjailedStorage')->willReturn($failedStorage);
		$file = $this->createFile(42, '/alice/files/myfolder3/file1.xlsx', 'file1.xlsx', $storage);

		$this->expectInfo('File with id "42" deleted: "/alice/files/myfolder3/file1.xlsx"', 'files.file.deleted', ['id' => 42, 'path' => '/alice/files/myfolder3/file1.xlsx']);
		$this->files->delete(new BeforeNodeDeletedEvent($file));
	}

	public function testRenameOutOfShare(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('getPath')->willReturn('/alice/files/myfolder3');
		$parent->method('getInternalPath')->willReturn('');
		$parent->method('getStorage')->willReturn($this->createSharedStorage('/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3'));
		$source = $this->createMock(File::class);
		$source->method('getPath')->willReturn('/alice/files/myfolder3/file1.xlsx');
		$source->method('getName')->willReturn('file1.xlsx');
		$source->method('getStorage')->willThrowException(new NotFoundException());
		$source->method('getParent')->willReturn($parent);

		$this->expectInfo('File renamed with id "42" from "/alice/files/myfolder3/file1.xlsx" to "/alice/files/b.txt"', 'files.file.renamed', [
			'newid' => 42,
			'oldpath' => '/alice/files/myfolder3/file1.xlsx',
			'newpath' => '/alice/files/b.txt',
			'oldOwnerPath' => '/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3/file1.xlsx',
		]);
		$this->files->afterRename(new NodeRenamedEvent($source, $this->target));
	}

	public function testCopyIntoShare(): void {
		$target = $this->createSharedFile(43, '/alice/files/myfolder3/a.txt', 'a.txt');

		$this->expectInfo('File id copied from: "41" to "43", path from "/alice/files/a.txt" to "/alice/files/myfolder3/a.txt"', 'files.file.copied', [
			'oldid' => 41,
			'newid' => 43,
			'oldpath' => '/alice/files/a.txt',
			'newpath' => '/alice/files/myfolder3/a.txt',
			'newOwnerPath' => '/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3/a.txt',
		]);
		$this->files->copy(new NodeCopiedEvent($this->source, $target));
	}

	public function testWriteInGroupFolder(): void {
		$file = $this->createFile(42, '/alice/files/GROUPFOLDER1/myfolder1/file1.xlsx', 'myfolder1/file1.xlsx', $this->createGroupFolderStorage(5));

		$this->expectInfo(
			'File with id "42" written to: "/alice/files/GROUPFOLDER1/myfolder1/file1.xlsx" (real path = "__groupfolders/5/files/myfolder1/file1.xlsx")',
			'files.file.written',
			[
				'id' => 42,
				'path' => '/alice/files/GROUPFOLDER1/myfolder1/file1.xlsx',
				'realPath' => '__groupfolders/5/files/myfolder1/file1.xlsx',
			],
		);
		$this->files->write(new NodeWrittenEvent($file));
	}

	public function testReadThroughShareFromGroupFolder(): void {
		$storage = $this->createSharedStorage('/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3', $this->createGroupFolderStorage(5), 'myfolder1/myfolder2/myfolder3');
		$file = $this->createFile(42, '/alice/files/myfolder3/file1.xlsx', 'file1.xlsx', $storage);

		$this->expectInfo(
			'File with id "42" accessed: "/alice/files/myfolder3/file1.xlsx" (real path = "__groupfolders/5/files/myfolder1/myfolder2/myfolder3/file1.xlsx")',
			'files.file.read',
			[
				'id' => 42,
				'path' => '/alice/files/myfolder3/file1.xlsx',
				'realPath' => '__groupfolders/5/files/myfolder1/myfolder2/myfolder3/file1.xlsx',
				'ownerPath' => '/bob/files/GROUPFOLDER1/myfolder1/myfolder2/myfolder3/file1.xlsx',
			],
		);
		$this->files->read(new BeforeNodeReadEvent($file));
	}

	public function testHomeFileHasNoRealPath(): void {
		$home = $this->createMock(Home::class);
		$home->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => in_array($class, [Local::class, IHomeStorage::class], true));
		$home->method('getSourcePath')->willReturnCallback(static fn (string $path): string => '/var/www/html/data/alice/' . $path);
		$file = $this->createFile(42, '/alice/files/b.txt', 'files/b.txt', $home);

		$this->expectInfo('File with id "42" deleted: "/alice/files/b.txt"', 'files.file.deleted', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->delete(new BeforeNodeDeletedEvent($file));
	}

	public function testRealPathOnObjectStorage(): void {
		$objectStorage = $this->createMock(IStorage::class);
		$objectStorage->method('instanceOfStorage')->willReturn(false);
		$objectStorage->method('getId')->willReturn('object::store:object::groupfolder:5.amazon::nextcloud');
		$jail = $this->createMock(Jail::class);
		$jail->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === Jail::class);
		$jail->method('getUnjailedPath')->willReturnCallback(static fn (string $path): string => trim('files/' . $path, '/'));
		$jail->method('getUnjailedStorage')->willReturn($objectStorage);
		$file = $this->createFile(42, '/alice/files/GROUPFOLDER1/a.txt', 'a.txt', $jail);

		$this->expectInfo(
			'File with id "42" written to: "/alice/files/GROUPFOLDER1/a.txt" (real path = "object::store:object::groupfolder:5.amazon::nextcloud/files/a.txt")',
			'files.file.written',
			[
				'id' => 42,
				'path' => '/alice/files/GROUPFOLDER1/a.txt',
				'realPath' => 'object::store:object::groupfolder:5.amazon::nextcloud/files/a.txt',
			],
		);
		$this->files->write(new NodeWrittenEvent($file));
	}

	public function testRealPathOutsideDataDirectory(): void {
		$local = $this->createMock(Local::class);
		$local->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === Local::class);
		$local->method('getSourcePath')->willReturnCallback(static fn (string $path): string => '/mnt/external/' . $path);
		$file = $this->createFile(42, '/alice/files/external/b.txt', 'b.txt', $local);

		$this->expectInfo(
			'File with id "42" created: "/alice/files/external/b.txt" (real path = "/mnt/external/b.txt")',
			'files.file.created',
			['id' => 42, 'path' => '/alice/files/external/b.txt', 'realPath' => '/mnt/external/b.txt'],
		);
		$this->files->create(new NodeCreatedEvent($file));
	}

	public function testRenameWithinGroupFolder(): void {
		$storage = $this->createGroupFolderStorage(5);
		$source = $this->createFile(42, '/alice/files/GROUPFOLDER1/a.txt', 'a.txt', $storage);
		$target = $this->createFile(42, '/alice/files/GROUPFOLDER1/b.txt', 'b.txt', $storage);

		$this->expectInfo(
			'File renamed with id "42" from "/alice/files/GROUPFOLDER1/a.txt" to "/alice/files/GROUPFOLDER1/b.txt" (real path from "__groupfolders/5/files/a.txt" to "__groupfolders/5/files/b.txt")',
			'files.file.renamed',
			[
				'newid' => 42,
				'oldpath' => '/alice/files/GROUPFOLDER1/a.txt',
				'newpath' => '/alice/files/GROUPFOLDER1/b.txt',
				'oldRealPath' => '__groupfolders/5/files/a.txt',
				'newRealPath' => '__groupfolders/5/files/b.txt',
			],
		);
		$this->files->afterRename(new NodeRenamedEvent($source, $target));
	}

	public function testCopyIntoGroupFolder(): void {
		$target = $this->createFile(43, '/alice/files/GROUPFOLDER1/a.txt', 'a.txt', $this->createGroupFolderStorage(5));

		$this->expectInfo(
			'File id copied from: "41" to "43", path from "/alice/files/a.txt" to "/alice/files/GROUPFOLDER1/a.txt" (real path to "__groupfolders/5/files/a.txt")',
			'files.file.copied',
			[
				'oldid' => 41,
				'newid' => 43,
				'oldpath' => '/alice/files/a.txt',
				'newpath' => '/alice/files/GROUPFOLDER1/a.txt',
				'newRealPath' => '__groupfolders/5/files/a.txt',
			],
		);
		$this->files->copy(new NodeCopiedEvent($this->source, $target));
	}

	public function testCopyOutOfGroupFolder(): void {
		$source = $this->createFile(41, '/alice/files/GROUPFOLDER1/a.txt', 'a.txt', $this->createGroupFolderStorage(5));

		$this->expectInfo(
			'File id copied from: "41" to "42", path from "/alice/files/GROUPFOLDER1/a.txt" to "/alice/files/b.txt" (real path from "__groupfolders/5/files/a.txt")',
			'files.file.copied',
			[
				'oldid' => 41,
				'newid' => 42,
				'oldpath' => '/alice/files/GROUPFOLDER1/a.txt',
				'newpath' => '/alice/files/b.txt',
				'oldRealPath' => '__groupfolders/5/files/a.txt',
			],
		);
		$this->files->copy(new NodeCopiedEvent($source, $this->target));
	}
}
