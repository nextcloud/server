<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Tests\Listener;

use OCA\Files_Versions\Listener\FileEventsListener;
use OCA\Files_Versions\Versions\VersionManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception as DbException;
use OCP\Files\File;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FileEventsListenerTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private VersionManager&MockObject $versionManager;
	private IMimeTypeLoader&MockObject $mimeTypeLoader;
	private IUserSession&MockObject $userSession;
	private LoggerInterface&MockObject $logger;
	private FileEventsListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		// VersionManager is the real collaborator registered in the DI container and
		// implements both IVersionManager (the constructor type) and
		// INeedSyncVersionBackend, which the listener checks for with instanceof.
		$this->versionManager = $this->createMock(VersionManager::class);
		$this->mimeTypeLoader = $this->createMock(IMimeTypeLoader::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new FileEventsListener(
			$this->rootFolder,
			$this->versionManager,
			$this->mimeTypeLoader,
			$this->userSession,
			$this->logger,
		);
	}

	private function createUnresolvableFile(): File&MockObject {
		$this->userSession->method('getUser')->willReturn(null);

		$node = $this->createMock(File::class);
		$node->method('getOwner')->willReturn(null);
		$node->method('getPath')->willReturn('/test.txt');
		$node->method('getId')->willReturn(42);
		$node->method('getSize')->willReturn(100);
		$node->method('getMTime')->willReturn(1234567890);

		return $node;
	}

	private function mockFile(int $id, int $mtime, int $size = 100, string $mimetype = 'text/markdown'): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getMTime')->willReturn($mtime);
		$file->method('getSize')->willReturn($size);
		$file->method('getMimetype')->willReturn($mimetype);
		return $file;
	}

	private function getPrivateProperty(string $property): mixed {
		$ref = new \ReflectionProperty(FileEventsListener::class, $property);
		$ref->setAccessible(true);
		return $ref->getValue($this->listener);
	}

	/**
	 * Seed the private write-hook state that is normally populated by write_hook(),
	 * which we cannot call here as it relies on the static Storage::store().
	 */
	private function seedWriteHookInfo(int $fileId, File $previousNode, bool $versionCreated): void {
		self::invokePrivate($this->listener, 'writeHookInfo', [[
			$fileId => [
				'previousNode' => $previousNode,
				'versionCreated' => $versionCreated,
			],
		]]);
	}

	public function testGetPathForNodeReturnsNullWhenUnresolvable(): void {
		$node = $this->createUnresolvableFile();

		$this->logger->expects($this->once())
			->method('debug')
			->with('Failed to compute path for node', $this->anything());

		$method = new \ReflectionMethod(FileEventsListener::class, 'getPathForNode');
		$method->setAccessible(true);

		$this->assertNull($method->invoke($this->listener, $node));
	}

	public function testWriteHookSkipsWhenPathUnresolvable(): void {
		$node = $this->createUnresolvableFile();

		$this->listener->write_hook($node);

		$this->assertSame([], $this->getPrivateProperty('writeHookInfo'));
	}

	public function testPreRemoveHookSkipsWhenPathUnresolvable(): void {
		$node = $this->createUnresolvableFile();

		$this->listener->pre_remove_hook($node);

		$this->assertSame([], $this->getPrivateProperty('versionsDeleted'));
	}

	public static function provideMissingEntityCases(): array {
		return [
			// A copy gives the target a new file id but no version entity, and no
			// version is stored in the FS, so versionCreated is false. This is the
			// regression that previously crashed the COPY request.
			'copy to a new location' => [false],
			// Versions disabled, then re-enabled and the same file re-uploaded
			// unchanged: a version was created in the FS but the previous entity
			// does not exist.
			'version created but entity missing' => [true],
		];
	}

	/**
	 * When there is no version entity to update, the listener must create one for
	 * the current content instead of letting the DoesNotExistException bubble up
	 * and fail the whole write/copy operation.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideMissingEntityCases')]
	public function testPostWriteHookCreatesVersionEntityWhenMissing(bool $versionCreated): void {
		$node = $this->mockFile(42, 2000);
		// Same mtime as the node so the "version created and mtime changed" fast
		// path is skipped and we reach updateVersionEntity().
		$previousNode = $this->mockFile(42, 2000);

		$this->mimeTypeLoader->method('getId')->willReturn(5);
		$this->versionManager->method('getRevision')->with($previousNode)->willReturn(2000);
		$this->versionManager->method('updateVersionEntity')
			->willThrowException(new DoesNotExistException('no version entity'));

		// The fix: the missing entity is created rather than crashing.
		$this->versionManager->expects($this->once())
			->method('createVersionEntity')
			->with($node);

		$this->seedWriteHookInfo(42, $previousNode, $versionCreated);

		$this->listener->post_write_hook($node);
	}

	/**
	 * The happy path must keep updating the existing entity and must not create a
	 * spurious new one.
	 */
	public function testPostWriteHookUpdatesExistingVersionEntity(): void {
		$node = $this->mockFile(42, 2000);
		$previousNode = $this->mockFile(42, 1000);

		$this->mimeTypeLoader->method('getId')->willReturn(5);
		$this->versionManager->method('getRevision')->with($previousNode)->willReturn(1000);
		$this->versionManager->expects($this->once())->method('updateVersionEntity');
		$this->versionManager->expects($this->never())->method('createVersionEntity');

		$this->seedWriteHookInfo(42, $previousNode, false);

		$this->listener->post_write_hook($node);
	}

	/**
	 * Real database errors must still surface and must not be turned into a
	 * silent entity creation.
	 */
	public function testPostWriteHookRethrowsDatabaseErrors(): void {
		$node = $this->mockFile(42, 2000);
		$previousNode = $this->mockFile(42, 2000);

		$this->mimeTypeLoader->method('getId')->willReturn(5);
		$this->versionManager->method('getRevision')->willReturn(2000);
		$this->versionManager->method('updateVersionEntity')
			->willThrowException(new DbException('database is gone'));
		$this->versionManager->expects($this->never())->method('createVersionEntity');

		$this->seedWriteHookInfo(42, $previousNode, false);

		$this->expectException(DbException::class);
		$this->listener->post_write_hook($node);
	}
}
