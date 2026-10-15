<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Jobs;

use OCA\Theming\Jobs\RestoreBackgroundImageColor;
use OCA\Theming\Service\BackgroundService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class RestoreBackgroundImageColorTest extends TestCase {

	protected ITimeFactory&MockObject $time;
	protected IConfig&MockObject $config;
	protected IAppData&MockObject $appData;
	protected IJobList&MockObject $jobList;
	protected IDBConnection $dbc;
	protected LoggerInterface&MockObject $logger;
	protected BackgroundService&MockObject $service;
	protected RestoreBackgroundImageColor $job;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->dbc = Server::get(IDBConnection::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->service = $this->createMock(BackgroundService::class);
		$this->job = new RestoreBackgroundImageColor(
			$this->time,
			$this->config,
			$this->appData,
			$this->jobList,
			$this->dbc,
			$this->logger,
			$this->service,
		);
	}

	public function testRunPreparation(): void {
		$this->jobList->expects($this->once())
			->method('add')
			->with(RestoreBackgroundImageColor::class, ['stage' => RestoreBackgroundImageColor::STAGE_EXECUTE]);
		self::invokePrivate($this->job, 'runPreparation');
	}
}
