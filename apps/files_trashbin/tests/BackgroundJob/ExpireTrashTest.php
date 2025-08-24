<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Trashbin\Tests\BackgroundJob;

use OCA\Files_Trashbin\BackgroundJob\ExpireTrash;
use OCA\Files_Trashbin\Expiration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ExpireTrashTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IUserManager&MockObject $userManager;
	private Expiration&MockObject $expiration;
	private IJobList&MockObject $jobList;
	private LoggerInterface&MockObject $logger;
	private ITimeFactory&MockObject $time;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->expiration = $this->createMock(Expiration::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')
			->willReturn(999999999);

		$this->jobList->expects($this->once())
			->method('setLastRun');
		$this->jobList->expects($this->once())
			->method('setExecutionTime');
	}

	public function testConstructAndRun(): void {
		$this->appConfig->method('getValueString')
			->with('files_trashbin', 'background_job_expire_trash', 'yes')
			->willReturn('yes');
		$this->appConfig->method('getValueInt')
			->with('files_trashbin', 'background_job_expire_trash_offset', 0)
			->willReturn(0);

		$job = new ExpireTrash($this->appConfig, $this->userManager, $this->expiration, $this->logger, $this->time);
		$job->start($this->jobList);
	}

	public function testBackgroundJobDeactivated(): void {
		$this->appConfig->method('getValueString')
			->with('files_trashbin', 'background_job_expire_trash', 'yes')
			->willReturn('no');
		$this->expiration->expects($this->never())
			->method('getMaxAgeAsTimestamp');

		$job = new ExpireTrash($this->appConfig, $this->userManager, $this->expiration, $this->logger, $this->time);
		$job->start($this->jobList);
	}
}
