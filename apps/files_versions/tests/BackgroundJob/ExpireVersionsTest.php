<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Tests\BackgroundJob;

use OCA\Files_Versions\BackgroundJob\ExpireVersions;
use OCA\Files_Versions\Expiration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ExpireVersionsTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var Expiration|MockObject */
	private $expiration;

	/** @var IJobList|MockObject */
	private $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->expiration = $this->createMock(Expiration::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->jobList->expects($this->once())
			->method('setLastRun');
		$this->jobList->expects($this->once())
			->method('setExecutionTime');
	}

	public function testBackgroundJobDeactivated(): void {
		$this->config->method('getAppValue')
			->with('files_versions', 'background_job_expire_versions', 'yes')
			->willReturn('no');
		$this->expiration->expects($this->never())
			->method('getMaxAgeAsTimestamp');

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')
			->with()
			->willReturn(999999999);

		$job = new ExpireVersions($this->config, $this->userManager, $this->expiration, $timeFactory);
		$job->start($this->jobList);
	}
}
