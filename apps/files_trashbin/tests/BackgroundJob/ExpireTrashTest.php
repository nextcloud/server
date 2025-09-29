<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Trashbin\Tests\BackgroundJob;

use OC\Files\SetupManager;
use OCA\Files_Trashbin\AppInfo\Application;
use OCA\Files_Trashbin\BackgroundJob\ExpireTrash;
use OCA\Files_Trashbin\Expiration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
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
	private SetupManager&MockObject $setupManager;
	private ILockingProvider&MockObject $lockingProvider;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->expiration = $this->createMock(Expiration::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->setupManager = $this->createMock(SetupManager::class);
		$this->lockingProvider = $this->createMock(ILockingProvider::class);

		$this->time = $this->createMock(ITimeFactory::class);
		$this->time->method('getTime')
			->willReturn(999999999);

		$this->jobList->expects($this->once())
			->method('setLastRun');
		$this->jobList->expects($this->once())
			->method('setExecutionTime');
	}

	public function testConstructAndRun(): void {
		$this->appConfig->method('getValueBool')
			->with(Application::APP_ID, ExpireTrash::TOGGLE_CONFIG_KEY_NAME, true)
			->willReturn(true);
		$this->appConfig->method('getValueInt')
			->with(Application::APP_ID, ExpireTrash::OFFSET_CONFIG_KEY_NAME, 0)
			->willReturn(0);

		$job = new ExpireTrash(
			$this->appConfig,
			$this->userManager,
			$this->expiration,
			$this->logger,
			$this->setupManager,
			$this->lockingProvider,
			$this->time,
		);
		$job->start($this->jobList);
	}

	public function testBackgroundJobDeactivated(): void {
		$this->appConfig->method('getValueBool')
			->with(Application::APP_ID, ExpireTrash::TOGGLE_CONFIG_KEY_NAME, true)
			->willReturn(false);
		$this->expiration->expects($this->never())
			->method('getMaxAgeAsTimestamp');

		$job = new ExpireTrash(
			$this->appConfig,
			$this->userManager,
			$this->expiration,
			$this->logger,
			$this->setupManager,
			$this->lockingProvider,
			$this->time,
		);
		$job->start($this->jobList);
	}
}
