<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair\Owncloud;

use OC\Repair\Owncloud\CleanPreviewsBackgroundJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CleanPreviewsBackgroundJobTest extends TestCase {

	private CleanPreviewsBackgroundJob $job;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->job = $this->createInstanceWithMocks(CleanPreviewsBackgroundJob::class);
		$this->mocks[IUserManager::class]->expects($this->any())->method('userExists')->willReturn(true);
	}

	public function testCleanupPreviewsUnfinished(): void {
		$userFolder = $this->createMock(IUserFolder::class);
		$userRoot = $this->createMock(Folder::class);
		$thumbnailFolder = $this->createMock(Folder::class);

		$this->mocks[IRootFolder::class]->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willReturn($userFolder);

		$userFolder->method('getParent')->willReturn($userRoot);

		$userRoot->method('get')
			->with($this->equalTo('thumbnails'))
			->willReturn($thumbnailFolder);

		$previewFolder1 = $this->createMock(Folder::class);

		$previewFolder1->expects($this->once())
			->method('delete');

		$thumbnailFolder->method('getDirectoryListing')
			->willReturn([$previewFolder1]);
		$thumbnailFolder->expects($this->never())
			->method('delete');

		$this->mocks[ITimeFactory::class]->method('getTime')->willReturnOnConsecutiveCalls(100, 200);

		$this->mocks[IJobList::class]->expects($this->once())
			->method('add')
			->with(
				$this->equalTo(CleanPreviewsBackgroundJob::class),
				$this->equalTo(['uid' => 'myuid'])
			);

		$loggerCalls = [];
		$this->mocks[LoggerInterface::class]->expects($this->exactly(2))
			->method('info')
			->willReturnCallback(function () use (&$loggerCalls): void {
				$loggerCalls[] = func_get_args();
			});

		$this->job->run(['uid' => 'myuid']);
		self::assertEquals([
			['Started preview cleanup for myuid', []],
			['New preview cleanup scheduled for myuid', []],
		], $loggerCalls);
	}

	public function testCleanupPreviewsFinished(): void {
		$userFolder = $this->createMock(IUserFolder::class);
		$userRoot = $this->createMock(Folder::class);
		$thumbnailFolder = $this->createMock(Folder::class);

		$this->mocks[IRootFolder::class]->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willReturn($userFolder);

		$userFolder->method('getParent')->willReturn($userRoot);

		$userRoot->method('get')
			->with($this->equalTo('thumbnails'))
			->willReturn($thumbnailFolder);

		$previewFolder1 = $this->createMock(Folder::class);

		$previewFolder1->expects($this->once())
			->method('delete');

		$thumbnailFolder->method('getDirectoryListing')
			->willReturn([$previewFolder1]);

		$this->mocks[ITimeFactory::class]->method('getTime')->willReturnOnConsecutiveCalls(100, 101);

		$this->mocks[IJobList::class]->expects($this->never())
			->method('add');

		$loggerCalls = [];
		$this->mocks[LoggerInterface::class]->expects($this->exactly(2))
			->method('info')
			->willReturnCallback(function () use (&$loggerCalls): void {
				$loggerCalls[] = func_get_args();
			});

		$thumbnailFolder->expects($this->once())
			->method('delete');

		$this->job->run(['uid' => 'myuid']);
		self::assertEquals([
			['Started preview cleanup for myuid', []],
			['Preview cleanup done for myuid', []],
		], $loggerCalls);
	}

	public function testNoUserFolder(): void {
		$this->mocks[IRootFolder::class]->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willThrowException(new NotFoundException());

		$loggerCalls = [];
		$this->mocks[LoggerInterface::class]->expects($this->exactly(2))
			->method('info')
			->willReturnCallback(function () use (&$loggerCalls): void {
				$loggerCalls[] = func_get_args();
			});

		$this->job->run(['uid' => 'myuid']);
		self::assertEquals([
			['Started preview cleanup for myuid', []],
			['Preview cleanup done for myuid', []],
		], $loggerCalls);
	}

	public function testNoThumbnailFolder(): void {
		$userFolder = $this->createMock(IUserFolder::class);
		$userRoot = $this->createMock(Folder::class);

		$this->mocks[IRootFolder::class]->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willReturn($userFolder);

		$userFolder->method('getParent')->willReturn($userRoot);

		$userRoot->method('get')
			->with($this->equalTo('thumbnails'))
			->willThrowException(new NotFoundException());

		$loggerCalls = [];
		$this->mocks[LoggerInterface::class]->expects($this->exactly(2))
			->method('info')
			->willReturnCallback(function () use (&$loggerCalls): void {
				$loggerCalls[] = func_get_args();
			});

		$this->job->run(['uid' => 'myuid']);
		self::assertEquals([
			['Started preview cleanup for myuid', []],
			['Preview cleanup done for myuid', []],
		], $loggerCalls);
	}

	public function testNotPermittedToDelete(): void {
		$userFolder = $this->createMock(IUserFolder::class);
		$userRoot = $this->createMock(Folder::class);
		$thumbnailFolder = $this->createMock(Folder::class);

		$this->mocks[IRootFolder::class]->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willReturn($userFolder);

		$userFolder->method('getParent')->willReturn($userRoot);

		$userRoot->method('get')
			->with($this->equalTo('thumbnails'))
			->willReturn($thumbnailFolder);

		$previewFolder1 = $this->createMock(Folder::class);

		$previewFolder1->expects($this->once())
			->method('delete')
			->willThrowException(new NotPermittedException());

		$thumbnailFolder->method('getDirectoryListing')
			->willReturn([$previewFolder1]);

		$this->mocks[ITimeFactory::class]->method('getTime')->willReturnOnConsecutiveCalls(100, 101);

		$this->mocks[IJobList::class]->expects($this->never())
			->method('add');

		$thumbnailFolder->expects($this->once())
			->method('delete')
			->willThrowException(new NotPermittedException());

		$loggerCalls = [];
		$this->mocks[LoggerInterface::class]->expects($this->exactly(2))
			->method('info')
			->willReturnCallback(function () use (&$loggerCalls): void {
				$loggerCalls[] = func_get_args();
			});

		$this->job->run(['uid' => 'myuid']);
		self::assertEquals([
			['Started preview cleanup for myuid', []],
			['Preview cleanup done for myuid', []],
		], $loggerCalls);
	}
}
