<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair\Owncloud;

use OC\Repair\Owncloud\CleanPreviews;
use OC\Repair\Owncloud\CleanPreviewsBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use Test\TestCase;

class CleanPreviewsTest extends TestCase {
	private CleanPreviews $repair;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->repair = $this->createInstanceWithMocks(CleanPreviews::class);
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

		$this->mocks[IUserManager::class]->expects($this->once())
			->method('callForSeenUsers')
			->willReturnCallback(function (\Closure $function) use (&$user1, $user2): void {
				$function($user1);
				$function($user2);
			});

		$jobListCalls = [];
		$this->mocks[IJobList::class]->expects($this->exactly(2))
			->method('add')
			->willReturnCallback(function () use (&$jobListCalls): void {
				$jobListCalls[] = func_get_args();
			});

		$this->mocks[IAppConfig::class]->expects($this->once())
			->method('getValueBool')
			->with(
				$this->equalTo('core'),
				$this->equalTo('previewsCleanedUp'),
			)->willReturn(false);
		$this->mocks[IAppConfig::class]->expects($this->once())
			->method('setValueBool')
			->with(
				$this->equalTo('core'),
				$this->equalTo('previewsCleanedUp'),
				$this->equalTo(true)
			);

		$this->repair->run($this->createMock(IOutput::class));
		$this->assertEqualsCanonicalizing([
			[CleanPreviewsBackgroundJob::class, ['uid' => 'user1']],
			[CleanPreviewsBackgroundJob::class, ['uid' => 'user2']],
		], $jobListCalls);
	}

	public function testRunAlreadyDone(): void {
		$this->mocks[IUserManager::class]->expects($this->never())
			->method($this->anything());

		$this->mocks[IJobList::class]->expects($this->never())
			->method($this->anything());

		$this->mocks[IAppConfig::class]->expects($this->once())
			->method('getValueBool')
			->with(
				$this->equalTo('core'),
				$this->equalTo('previewsCleanedUp'),
			)->willReturn(true);
		$this->mocks[IAppConfig::class]->expects($this->never())
			->method('setValueBool');

		$this->repair->run($this->createMock(IOutput::class));
	}
}
