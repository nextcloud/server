<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\CalDAV\WebcalCaching\RefreshWebcalService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

use Test\TestCase;

class RefreshWebcalJobTest extends TestCase {

	/** @var RefreshWebcalService | MockObject */
	private $refreshWebcalService;

	/** @var IConfig | MockObject */
	private $config;

	private LoggerInterface $logger;

	/** @var ITimeFactory | MockObject */
	private $timeFactory;

	/** @var IJobList | MockObject */
	private $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->refreshWebcalService = $this->createMock(RefreshWebcalService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->jobList = $this->createMock(IJobList::class);
	}

	/**
	 *
	 * @param int $lastRun
	 * @param int $time
	 * @param bool $process
	 *
	 * @dataProvider runDataProvider
	 */
	public function testRun(int $lastRun, int $time, bool $process): void {
		$backgroundJob = new RefreshWebcalJob($this->refreshWebcalService, $this->config, $this->logger, $this->timeFactory);
		$backgroundJob->setId(42);

		$backgroundJob->setArgument([
			'principaluri' => 'principals/users/testuser',
			'uri' => 'sub123',
		]);
		$backgroundJob->setLastRun($lastRun);

		$this->refreshWebcalService->expects($this->once())
			->method('getSubscription')
			->with('principals/users/testuser', 'sub123')
			->willReturn([
				'id' => '99',
				'uri' => 'sub456',
				'{http://apple.com/ns/ical/}refreshrate' => 'P1D',
				'{http://calendarserver.org/ns/}subscribed-strip-todos' => '1',
				'{http://calendarserver.org/ns/}subscribed-strip-alarms' => '1',
				'{http://calendarserver.org/ns/}subscribed-strip-attachments' => '1',
				'source' => 'webcal://foo.bar/bla'
			]);

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'calendarSubscriptionRefreshRate', 'P1D')
			->willReturn('P1W');

		$this->timeFactory->method('getTime')
			->willReturn($time);

		if ($process) {
			$this->refreshWebcalService->expects($this->once())
				->method('refreshSubscription')
				->with('principals/users/testuser', 'sub123');
		} else {
			$this->refreshWebcalService->expects($this->never())
				->method('refreshSubscription')
				->with('principals/users/testuser', 'sub123');
		}

		$backgroundJob->start($this->jobList);
	}

	/**
	 * @return array
	 */
	public function runDataProvider():array {
		return [
			[0, 100000, true],
			[100000, 100000, false]
		];
	}
}
