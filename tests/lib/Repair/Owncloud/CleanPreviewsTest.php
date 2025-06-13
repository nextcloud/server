<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Repair\Owncloud;

use OC\Repair\Owncloud\CleanPreviews;
use OC\Repair\Owncloud\CleanPreviewsBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CleanPreviewsTest extends TestCase {

	private IJobList&MockObject $jobList;
	private IUserManager&MockObject $userManager;
	private IConfig&MockObject $config;

	/** @var CleanPreviews */
	private $repair;

	public function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IConfig::class);

		$this->repair = new CleanPreviews(
			$this->jobList,
			$this->userManager,
			$this->config
		);
	}

	public function testGetName(): void {
		$this->assertSame('Add preview cleanup background jobs', $this->repair->getName());
	}

	public function testRun(): void {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')
			->willReturn('user1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')
			->willReturn('user2');

		$this->userManager->expects($this->once())
			->method('callForSeenUsers')
			->willReturnCallback(function (\Closure $function) use (&$user1, $user2): void {
				$function($user1);
				$function($user2);
			});

		$jobListCalls = [];
		$this->jobList->expects($this->exactly(2))
			->method('add')
			->willReturnCallback(function () use (&$jobListCalls): void {
				$jobListCalls[] = func_get_args();
			});

		$this->config->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('core'),
				$this->equalTo('previewsCleanedUp'),
				$this->equalTo(false)
			)->willReturn(false);
		$this->config->expects($this->once())
			->method('setAppValue')
			->with(
				$this->equalTo('core'),
				$this->equalTo('previewsCleanedUp'),
				$this->equalTo(1)
			);

		$this->repair->run($this->createMock(IOutput::class));
		$this->assertEqualsCanonicalizing([
			[CleanPreviewsBackgroundJob::class, ['uid' => 'user1']],
			[CleanPreviewsBackgroundJob::class, ['uid' => 'user2']],
		], $jobListCalls);
	}


	public function testRunAlreadyDone(): void {
		$this->userManager->expects($this->never())
			->method($this->anything());

		$this->jobList->expects($this->never())
			->method($this->anything());

		$this->config->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('core'),
				$this->equalTo('previewsCleanedUp'),
				$this->equalTo(false)
			)->willReturn('1');
		$this->config->expects($this->never())
			->method('setAppValue');

		$this->repair->run($this->createMock(IOutput::class));
	}
}
