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
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CleanPreviewsBackgroundJobTest extends TestCase {
	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	/** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var IJobList|\PHPUnit_Framework_MockObject_MockObject */
	private $jobList;

	/** @var ITimeFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $timeFactory;

	/** @var CleanPreviewsBackgroundJob */
	private $job;

	/** @var  IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	public function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->userManager->expects($this->any())->method('userExists')->willReturn(true);

		$this->job = new CleanPreviewsBackgroundJob(
			$this->rootFolder,
			$this->logger,
			$this->jobList,
			$this->timeFactory,
			$this->userManager
		);
	}

	public function testCleanupPreviewsUnfinished() {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);
		$thumbnailFolder = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
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

		$this->timeFactory->method('getTime')
			->will($this->onConsecutiveCalls(100, 200));

		$this->jobList->expects($this->once())
			->method('add')
			->with(
				$this->equalTo(CleanPreviewsBackgroundJob::class),
				$this->equalTo(['uid' => 'myuid'])
			);

		$this->logger->expects($this->exactly(2))
			->method('info')
			->withConsecutive(
				[$this->equalTo('Started preview cleanup for myuid')],
				[$this->equalTo('New preview cleanup scheduled for myuid')],
			);

		$this->job->run(['uid' => 'myuid']);
	}

	public function testCleanupPreviewsFinished() {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);
		$thumbnailFolder = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
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

		$this->timeFactory->method('getTime')
			->will($this->onConsecutiveCalls(100, 101));

		$this->jobList->expects($this->never())
			->method('add');

		$this->logger->expects($this->exactly(2))
			->method('info')
			->withConsecutive(
				[$this->equalTo('Started preview cleanup for myuid')],
				[$this->equalTo('Preview cleanup done for myuid')],
			);

		$thumbnailFolder->expects($this->once())
			->method('delete');

		$this->job->run(['uid' => 'myuid']);
	}


	public function testNoUserFolder() {
		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willThrowException(new NotFoundException());

		$this->logger->expects($this->exactly(2))
			->method('info')
			->withConsecutive(
				[$this->equalTo('Started preview cleanup for myuid')],
				[$this->equalTo('Preview cleanup done for myuid')],
			);

		$this->job->run(['uid' => 'myuid']);
	}

	public function testNoThumbnailFolder() {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
			->with($this->equalTo('myuid'))
			->willReturn($userFolder);

		$userFolder->method('getParent')->willReturn($userRoot);

		$userRoot->method('get')
			->with($this->equalTo('thumbnails'))
			->willThrowException(new NotFoundException());

		$this->logger->expects($this->exactly(2))
			->method('info')
			->withConsecutive(
				[$this->equalTo('Started preview cleanup for myuid')],
				[$this->equalTo('Preview cleanup done for myuid')],
			);

		$this->job->run(['uid' => 'myuid']);
	}

	public function testNotPermittedToDelete() {
		$userFolder = $this->createMock(Folder::class);
		$userRoot = $this->createMock(Folder::class);
		$thumbnailFolder = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')
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

		$this->timeFactory->method('getTime')
			->will($this->onConsecutiveCalls(100, 101));

		$this->jobList->expects($this->never())
			->method('add');

		$this->logger->expects($this->exactly(2))
			->method('info')
			->withConsecutive(
				[$this->equalTo('Started preview cleanup for myuid')],
				[$this->equalTo('Preview cleanup done for myuid')],
			);

		$thumbnailFolder->expects($this->once())
			->method('delete')
			->willThrowException(new NotPermittedException());

		$this->job->run(['uid' => 'myuid']);
	}
}
