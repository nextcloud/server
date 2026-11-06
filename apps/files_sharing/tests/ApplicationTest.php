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
use OCA\Files_Sharing\SharedMount;
use OCA\Files_Sharing\ViewOnly;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Mount\IMountPoint;
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
				'/bar.txt', null, true
			],
			'shared file (receiver) with attribute secure-view-enabled set false' => [
				'/share-bar.txt', true, true
			],
			'shared file (receiver) with attribute secure-view-enabled set true' => [
				'/secure-share-bar.txt', false, false
			],
		];
	}

	#[DataProvider(methodName: 'providesDataForCanGet')]
	public function testCheckDirectCanBeDownloaded(
		string $path,
		?bool $shareDownloadPermission,
		bool $canDownloadDirectly,
	): void {
		if ($shareDownloadPermission === null) {
			$mount = $this->createMock(IMountPoint::class);
		} else {
			$fileShareAttributes = $this->createMock(IAttributes::class);
			$fileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn($shareDownloadPermission);
			$fileShare = $this->createMock(IShare::class);
			$fileShare->method('getAttributes')->willReturn($fileShareAttributes);

			$mount = $this->createMock(SharedMount::class);
			$mount->method('getGroupedShares')->willReturn([$fileShare]);
		}

		$file = $this->createMock(File::class);
		$file->method('getMountPoint')->willReturn($mount);

		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('get')->willReturn($file);
		$userFolder->method('getPath')->willReturn($path);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('test');
		$this->userSession->method('getUser')->willReturn($user);
		$this->userSession->method('isLoggedIn')->willReturn(true);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		// Simulate direct download of file
		$event = new BeforeDirectFileDownloadEvent($path);
		$listener = new BeforeDirectFileDownloadListener(
			$this->userSession,
			$this->rootFolder,
			new ViewOnly($userFolder),
		);
		$listener->handle($event);

		$this->assertEquals($canDownloadDirectly, $event->isSuccessful());
	}

	public static function providesDataForCanZip(): array {
		return [
			'can download zipped 2 non-shared files inside non-shared folder' => [
				'/folder', ['bar1.txt', 'bar2.txt'], 'nonSharedMount', ['nonSharedMount','nonSharedMount'], true
			],
			'can download zipped non-shared folder' => [
				'/', ['folder'], 'nonSharedMount', ['nonSharedMount','nonSharedMount'], true
			],
			'cannot download zipped 1 non-shared file and 1 secure-shared inside non-shared folder' => [
				'/folder', ['secured-bar1.txt', 'bar2.txt'], 'nonSharedMount', ['nonSharedMount','secureSharedMount'], false,
			],
			'cannot download zipped secure-shared folder' => [
				'/', ['secured-folder'], 'secureSharedMount', [], false,
			],
		];
	}

	#[DataProvider(methodName: 'providesDataForCanZip')]
	public function testCheckZipCanBeDownloaded(
		string $dir,
		array $files,
		string $folderMount,
		array $directoryListing,
		bool $downloadSuccessful,
	): void {
		// Mock: Normal file/folder mount
		$nonSharedMount = $this->createMock(IMountPoint::class);

		// Mock: Secure-view file/folder shared storage
		$secureReceiverFileShareAttributes = $this->createMock(IAttributes::class);
		$secureReceiverFileShareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(false);
		$secureReceiverFileShare = $this->createMock(IShare::class);
		$secureReceiverFileShare->method('getAttributes')->willReturn($secureReceiverFileShareAttributes);
		$secureSharedMount = $this->createMock(SharedMount::class);
		$secureSharedMount->method('getGroupedShares')->willReturn([$secureReceiverFileShare]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn($dir);
		if ($folderMount === 'nonSharedMount') {
			$folder->method('getMountPoint')->willReturn($nonSharedMount);
		} elseif ($folderMount === 'secureSharedMount') {
			$folder->method('getMountPoint')->willReturn($secureSharedMount);
		} else {
			throw new \Exception('Unknown mount ' . $folderMount);
		}
		if (count($directoryListing) > 0) {
			$directoryListing = array_map(
				function (string $fileMount) use ($nonSharedMount, $secureSharedMount) {
					$file = $this->createMock(File::class);
					if ($fileMount === 'nonSharedMount') {
						$file->method('getMountPoint')->willReturn($nonSharedMount);
					} elseif ($fileMount === 'secureSharedMount') {
						$file->method('getMountPoint')->willReturn($secureSharedMount);
					} else {
						throw new \Exception('Unknown mount ' . $fileMount);
					}
					return $file;
				},
				$directoryListing
			);
			$folder->method('getDirectoryListing')->willReturn($directoryListing);
			$callCount = 0;
			$folder->method('get')->willReturnCallback(function (string $path) use (&$callCount, $directoryListing) {
				if (isset($directoryListing[$callCount])) {
					return $directoryListing[$callCount++];
				}

				throw new \Exception('Unknown path ' . $path);
			});
		}

		// If the folder contains any secure-shared files, make it appear as a secure-shared folder
		// so that ViewOnly::isDownloadable() will return false
		$containsSecureSharedFiles = in_array('secureSharedMount', $directoryListing);
		if ($containsSecureSharedFiles && $folderMount === 'nonSharedMount') {
			$folder->method('getMountPoint')->willReturn($secureSharedMount);
		}

		$rootFolder = $this->createMock(Folder::class);
		$rootFolder->method('getMountPoint')->willReturn($nonSharedMount);
		$rootFolder->method('getDirectoryListing')->willReturn([$folder]);

		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('get')->willReturn($folder);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('test');
		$this->userSession->method('getUser')->willReturn($user);
		$this->userSession->method('isLoggedIn')->willReturn(true);

		$this->rootFolder->method('getUserFolder')->with('test')->willReturn($userFolder);

		// Simulate zip download of folder
		$event = new BeforeZipCreatedEvent($folder, $files);

		$listener = new BeforeZipCreatedListener(
			$this->userSession,
			$this->rootFolder,
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
			$this->rootFolder,
		);
		$listener->handle($event);

		// It should run as this would restrict e.g. share links otherwise
		$this->assertTrue($event->isSuccessful());
		$this->assertEquals(null, $event->getErrorMessage());
	}
}
