<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\BackgroundJob\RegisterRegenerateBirthdayCalendars;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class RegisterRegenerateBirthdayCalendarsTest extends TestCase {
	/** @var ITimeFactory | \PHPUnit\Framework\MockObject\MockObject */
	private $time;

	/** @var IUserManager | \PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var IJobList | \PHPUnit\Framework\MockObject\MockObject */
	private $jobList;

	/** @var RegisterRegenerateBirthdayCalendars */
	private $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->backgroundJob = new RegisterRegenerateBirthdayCalendars(
			$this->time,
			$this->userManager,
			$this->jobList
		);
	}

	public function testRun(): void {
		$this->userManager->expects($this->once())
			->method('callForSeenUsers')
			->willReturnCallback(function ($closure): void {
				$user1 = $this->createMock(IUser::class);
				$user1->method('getUID')->willReturn('uid1');
				$user2 = $this->createMock(IUser::class);
				$user2->method('getUID')->willReturn('uid2');
				$user3 = $this->createMock(IUser::class);
				$user3->method('getUID')->willReturn('uid3');

				$closure($user1);
				$closure($user2);
				$closure($user3);
			});

		$this->jobList->expects($this->exactly(3))
			->method('add')
			->withConsecutive(
				[GenerateBirthdayCalendarBackgroundJob::class, [
					'userId' => 'uid1',
					'purgeBeforeGenerating' => true
				]],
				[GenerateBirthdayCalendarBackgroundJob::class, [
					'userId' => 'uid2',
					'purgeBeforeGenerating' => true
				]],
				[GenerateBirthdayCalendarBackgroundJob::class, [
					'userId' => 'uid3',
					'purgeBeforeGenerating' => true
				]],
			);

		$this->backgroundJob->run([]);
	}
}
