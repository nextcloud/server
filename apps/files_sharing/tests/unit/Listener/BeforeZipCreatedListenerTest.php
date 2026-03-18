<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\unit\Listener;

use OCA\Files_Sharing\Listener\BeforeZipCreatedListener;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BeforeZipCreatedListenerTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private IRootFolder&MockObject $rootFolder;
	private Folder&MockObject $userFolder;
	private BeforeZipCreatedListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userFolder = $this->createMock(Folder::class);
		$this->listener = new BeforeZipCreatedListener($this->userSession, $this->rootFolder);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$this->userSession->method('getUser')->willReturn($user);
		$this->rootFolder->method('getUserFolder')->with('user')->willReturn($this->userFolder);
	}

	public static function dataHandle(): array {
		$rootFromFolder = '/folder';
		// files are relative to $folderPath
		// filesFilter are relative to $folderPath but without leading /
		// expectedNodeList are ...
		return [
			'partial archive disabled, no filtering, 1 blocked file => should fail event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['blocked.txt' => false],
				'filesFilter' => [],
				'allowPartialArchive' => false,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource or one of its sub-items has been denied.',
				'expectedNodeList' => [],
			],
			'partial archive disabled, no filtering, 1 blocked 1 non-blocked file => should fail event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ["blocked.txt" => false, "allowed.txt" => true],
				'filesFilter' => [],
				'allowPartialArchive' => false,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource or one of its sub-items has been denied.',
				'expectedNodeList' => [],
			],
			'partial archive enabled, no filtering, 1 blocked file => should not fail event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['blocked.txt' => false],
				'filesFilter' => [],
				'allowPartialArchive' => true,
				'expectedSuccess' => true,
				'expectedMessage' => null,
				'expectedNodeList' => ['blocked.txt' => [null, 'Download is disabled for this resource']],
			],
			'partial archive enabled, no filtering, 1 blocked 1 non-blocked file => should not fail event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['blocked.txt' => false, 'allowed.txt' => true],
				'filesFilter' => [],
				'allowPartialArchive' => true,
				'expectedSuccess' => true,
				'expectedMessage' => null,
				'expectedNodeList' => ['blocked.txt' => [null, 'Download is disabled for this resource'], 'allowed.txt' => null],
			],
			'partial archive disabled, with filtering, 1 blocked 2 non-blocked files => should fail event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['blocked.txt' => false, 'allowed.txt' => true, 'notinfilter.txt' => true],
				'filesFilter' => ['blocked.txt', 'allowed.txt'],
				'allowPartialArchive' => false,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource or one of its sub-items has been denied.',
				'expectedNodeList' => [],
			],
			'partial archive enabled, with filtering, 1 blocked 2 non-blocked files => should not fail event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['blocked.txt' => false, 'allowed.txt' => true, 'notinfilter.txt' => true],
				'filesFilter' => ['blocked.txt', 'allowed.txt'],
				'allowPartialArchive' => true,
				'expectedSuccess' => true,
				'expectedMessage' => null,
				'expectedNodeList' => ['blocked.txt' => [null, 'Download is disabled for this resource'], 'allowed.txt' => null],
			],
			'partial archive disabled, with filtering on non-blocked file, 1 blocked 1 non-blocked file => should succeed event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['allowed.txt' => true, 'notinfilter.txt' => false],
				'filesFilter' => ['allowed.txt'],
				'allowPartialArchive' => false,
				'expectedSuccess' => true,
				'expectedMessage' => null,
				'expectedNodeList' => ['allowed.txt' => null],
			],
			'partial archive enabled, with filtering on non-blocked file, 1 downloadable 1 non-blocked file => should succeed event' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => true,
				'files' => ['allowed.txt' => true, 'notinfilter.txt' => false],
				'filesFilter' => ['allowed.txt'],
				'allowPartialArchive' => true,
				'expectedSuccess' => true,
				'expectedMessage' => null,
				'expectedNodeList' => ['allowed.txt' => null],
			],
			'partial archive disabled, root (containing) folder not downloadable, with filtering' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => false,
				'files' => ['allowed.txt' => true, 'notinfilter.txt' => false],
				'filesFilter' => ['allowed.txt'],
				'allowPartialArchive' => false,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource or one of its sub-items has been denied.',
				'expectedNodeList' => [],
			],
			'partial archive enabled, root (containing) folder not downloadable, with filtering' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => false,
				'files' => ['allowed.txt' => true, 'notinfilter.txt' => false],
				'filesFilter' => ['allowed.txt'],
				'allowPartialArchive' => true,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource and its children has been denied.',
				'expectedNodeList' => [],
			],
			'partial archive disabled, root (containing) folder not downloadable, no filtering' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => false,
				'files' => ['allowed.txt' => true, 'notinfilter.txt' => false],
				'filesFilter' => [],
				'allowPartialArchive' => false,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource or one of its sub-items has been denied.',
				'expectedNodeList' => [],
			],
			'partial archive enabled, root (containing) folder not downloadable, no filtering' => [
				'folderPath' => $rootFromFolder,
				'rootDownloadable' => false,
				'files' => ['allowed.txt' => true, 'notinfilter.txt' => false],
				'filesFilter' => [],
				'allowPartialArchive' => true,
				'expectedSuccess' => false,
				'expectedMessage' => 'Access to this resource and its children has been denied.',
				'expectedNodeList' => [],
			],
		];
	}

	#[DataProvider(methodName: 'dataHandle')]
	public function testHandle(
		string $folderPath,
		bool $rootDownloadable,
		array $files,
		array $filesFilter,
		bool $allowPartialArchive,
		bool $expectedSuccess,
		?string $expectedMessage,
		array $expectedNodeList,
	): void {
		$fileNodes = [];
		$fileNodesByName = [];
		foreach ($files as $relativePath => $downloadable) {
			$pathWithFolder = "{$folderPath}/{$relativePath}";
			$file = $this->createSharedFile($downloadable, $pathWithFolder);
			$fileNodesByName[$pathWithFolder] = $file;
			$fileNodes[] = $file;
		}
		$folderPathFromUserRoot = "/user/files{$folderPath}";
		$folder = $this->createSharedFolder($rootDownloadable, $folderPathFromUserRoot, $fileNodes);
		$this->userFolder->method('get')->willReturnCallback(function (string $path) use ($folderPath, $fileNodesByName, $folder) {
			return match (true) {
				$path === $folderPath => $folder,
				isset($fileNodesByName[$path]) => $fileNodesByName[$path],
				default => throw new \RuntimeException("Mock node not set for {$path}"),
			};
		});

		$event = new BeforeZipCreatedEvent($folder, $filesFilter, $allowPartialArchive);
		$this->listener->handle($event);

		$this->assertEquals($expectedSuccess, $event->isSuccessful());
		$this->assertSame($expectedMessage, $event->getErrorMessage());

		$event->setNodesIterable($fileNodes);
		$actualNodes = iterator_to_array($event->getNodes());
		$this->assertCount(count($expectedNodeList), $actualNodes);
		foreach ($expectedNodeList as $relativePath => $expectedValue) {
			$path = "{$folderPath}/{$relativePath}";
			if ($expectedValue === null) {
				// cannot reference the node in the data provider, add it here
				$node = $fileNodesByName[$path] ?? null;
				$this->assertNotNull($node, 'Node mock must be present for the test to be correct.');
				$expectedValue = [$node, null];
			}

			$this->assertEquals($expectedValue, $actualNodes[$path]);
		}
	}

	private function createSharedFile(bool $downloadable, string $path): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getStorage')->willReturn($this->createSharedStorage($downloadable));
		$file->method('getPath')->willReturn($path);
		$file->method('getName')->willReturn(basename($path));

		return $file;
	}

	/**
	 * @param list<Node> $children
	 */
	private function createSharedFolder(bool $downloadable, string $path, array $children = []): Folder&MockObject {
		$folder = $this->createMock(Folder::class);
		$folder->method('getStorage')->willReturn($this->createSharedStorage($downloadable));
		$folder->method('getDirectoryListing')->willReturn($children);
		$folder->method('getPath')->willReturn($path);
		$folder->method('getName')->willReturn(basename($path));

		return $folder;
	}

	private function createSharedStorage(bool $downloadable): SharedStorage&MockObject {
		$attributes = $this->createMock(IAttributes::class);
		$attributes->method('getAttribute')->with('permissions', 'download')->willReturn($downloadable);

		$share = $this->createMock(IShare::class);
		$share->method('getAttributes')->willReturn($attributes);

		$storage = $this->getMockBuilder(SharedStorage::class)
			->disableOriginalConstructor()
			->onlyMethods(['instanceOfStorage', 'getShare'])
			->getMock();
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$storage->method('getShare')->willReturn($share);

		return $storage;
	}
}
