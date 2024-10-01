<?php
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
use OCP\IConfig;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ExpireTrashTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var Expiration|MockObject */
	private $expiration;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var ITimeFactory|MockObject */
	private $time;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->expiration = $this->createMock(Expiration::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')
			->willReturn(999999999);

		$this->jobList->expects($this->once())
			->method('setLastRun');
		$this->jobList->expects($this->once())
			->method('setExecutionTime');
	}

	public function testConstructAndRun(): void {
		$this->config->method('getAppValue')
			->with('files_trashbin', 'background_job_expire_trash', 'yes')
			->willReturn('yes');

		$job = new ExpireTrash($this->config, $this->userManager, $this->expiration, $this->time);
		$job->start($this->jobList);
	}

	public function testBackgroundJobDeactivated(): void {
		$this->config->method('getAppValue')
			->with('files_trashbin', 'background_job_expire_trash', 'yes')
			->willReturn('no');
		$this->expiration->expects($this->never())
			->method('getMaxAgeAsTimestamp');

		$job = new ExpireTrash($this->config, $this->userManager, $this->expiration, $this->time);
		$job->start($this->jobList);
	}
}
