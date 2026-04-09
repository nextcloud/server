<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Listener\BeforeDirectFileDownloadListener;
use OCA\Files_Sharing\Listener\BeforeZipCreatedListener;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ApplicationTest extends TestCase {
	private Application $application;

	private IUserSession&MockObject $userSession;
	private IRootFolder&MockObject $rootFolder;

	protected function setUp(): void {
		parent::setUp();

		$this->application = new Application([]);

		$this->userSession = $this->createMock(IUserSession::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
	}

	public static function providesDataForCanGet(): array {
		return [
			'normal file (sender)' => [
				'/bar.txt', IStorage::class, false, null, true
			],
			'shared file (receiver) with attribute secure-view-enabled set false' => [
				'/share-bar.txt', SharedStorage::class, true, true, true
			],
			'shared file (receiver) with attribute secure-view-enabled set true' => [
				'/secure-share-bar.txt', SharedStorage::class, true, false, false
			],
		];
	}

	#[DataProvider(methodName: 'providesDataForCanGet')]
	public function testCheckDirectCanBeDownloaded(
		string $path,
		string $storageClass,
		bool $instanceOfSharedStorage,
		?bool $storageShareDownloadPermission,
		bool $canDownloadDirectly,
	): void {
		$fileStorage = $this->createMock($storageClass);
		$fileStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn($instanceOfSharedStorage);
		if ($storageShareDownloadPermission !== null) {
			$fileShareAttributes = $this->createMock(IAttributes::class);
			$fileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn($storageShareDownloadPermission);
			$fileShare = $this->createMock(IShare::class);
			$fileShare->method('getAttributes')->willReturn($fileShareAttributes);

			$fileStorage->method('getShare')->willReturn($fileShare);
		}

		$file = $this->createMock(File::class);
		$file->method('getStorage')->willReturn($fileStorage);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('get')->willReturn($file);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('test');
		$this->userSession->method('getUser')->willReturn($user);
		$this->userSession->method('isLoggedIn')->willReturn(true);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		// Simulate direct download of file
		$event = new BeforeDirectFileDownloadEvent($path);
		$listener = new BeforeDirectFileDownloadListener(
			$this->userSession,
			$this->rootFolder
		);
		$listener->handle($event);

		$this->assertEquals($canDownloadDirectly, $event->isSuccessful());
	}

	public static function providesDataForCanZip(): array {
		return [
			'can download zipped 2 non-shared files inside non-shared folder' => [
				'/folder', ['bar1.txt', 'bar2.txt'], 'nonSharedStorage', ['nonSharedStorage','nonSharedStorage'], true
			],
			'can download zipped non-shared folder' => [
				'/', ['folder'], 'nonSharedStorage', ['nonSharedStorage','nonSharedStorage'], true
			],
			'cannot download zipped 1 non-shared file and 1 secure-shared inside non-shared folder' => [
				'/folder', ['secured-bar1.txt', 'bar2.txt'], 'nonSharedStorage', ['nonSharedStorage','secureSharedStorage'], false,
			],
			'cannot download zipped secure-shared folder' => [
				'/', ['secured-folder'], 'secureSharedStorage', [], false,
			],
		];
	}

	#[DataProvider(methodName: 'providesDataForCanZip')]
	public function testCheckZipCanBeDownloaded(
		string $dir,
		array $files,
		string $folderStorage,
		array $directoryListing,
		bool $downloadSuccessful,
	): void {
		// Mock: Normal file/folder storage
		$nonSharedStorage = $this->createMock(IStorage::class);
		$nonSharedStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(false);

		// Mock: Secure-view file/folder shared storage
		$secureReceiverFileShareAttributes = $this->createMock(IAttributes::class);
		$secureReceiverFileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(false);
		$secureReceiverFileShare = $this->createMock(IShare::class);
		$secureReceiverFileShare->method('getAttributes')->willReturn($secureReceiverFileShareAttributes);
		$secureSharedStorage = $this->createMock(SharedStorage::class);
		$secureSharedStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$secureSharedStorage->method('getShare')->willReturn($secureReceiverFileShare);

		$folder = $this->createMock(Folder::class);
		if ($folderStorage === 'nonSharedStorage') {
			$folder->method('getStorage')->willReturn($nonSharedStorage);
		} elseif ($folderStorage === 'secureSharedStorage') {
			$folder->method('getStorage')->willReturn($secureSharedStorage);
		} else {
			throw new \Exception('Unknown storage ' . $folderStorage);
		}
		if (count($directoryListing) > 0) {
			$directoryListing = array_map(
				function (string $fileStorage) use ($nonSharedStorage, $secureSharedStorage) {
					$file = $this->createMock(File::class);
					if ($fileStorage === 'nonSharedStorage') {
						$file->method('getStorage')->willReturn($nonSharedStorage);
					} elseif ($fileStorage === 'secureSharedStorage') {
						$file->method('getStorage')->willReturn($secureSharedStorage);
					} else {
						throw new \Exception('Unknown storage ' . $fileStorage);
					}
					return $file;
				},
				$directoryListing
			);
			$folder->method('getDirectoryListing')->willReturn($directoryListing);
		}

		$rootFolder = $this->createMock(Folder::class);
		$rootFolder->method('getStorage')->willReturn($nonSharedStorage);
		$rootFolder->method('getDirectoryListing')->willReturn([$folder]);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('get')->willReturn($rootFolder);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('test');
		$this->userSession->method('getUser')->willReturn($user);
		$this->userSession->method('isLoggedIn')->willReturn(true);

		$this->rootFolder->method('getUserFolder')->with('test')->willReturn($userFolder);

		// Simulate zip download of folder folder
		$event = new BeforeZipCreatedEvent($dir, $files);
		$listener = new BeforeZipCreatedListener(
			$this->userSession,
			$this->rootFolder
		);
		$listener->handle($event);

		$this->assertEquals($downloadSuccessful, $event->isSuccessful());
		$this->assertEquals($downloadSuccessful, $event->getErrorMessage() === null);
	}

	public function testCheckFileUserNotFound(): void {
		$this->userSession->method('isLoggedIn')->willReturn(false);

		// Simulate zip download of folder folder
		$event = new BeforeZipCreatedEvent('/test', ['test.txt']);
		$listener = new BeforeZipCreatedListener(
			$this->userSession,
			$this->rootFolder
		);
		$listener->handle($event);

		// It should run as this would restrict e.g. share links otherwise
		$this->assertTrue($event->isSuccessful());
		$this->assertEquals(null, $event->getErrorMessage());
	}
}
