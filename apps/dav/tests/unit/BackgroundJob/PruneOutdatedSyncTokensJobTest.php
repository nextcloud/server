<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use InvalidArgumentException;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\BackgroundJob\PruneOutdatedSyncTokensJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PruneOutdatedSyncTokensJobTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private CalDavBackend&MockObject $calDavBackend;
	private CardDavBackend&MockObject $cardDavBackend;
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private PruneOutdatedSyncTokensJob $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->cardDavBackend = $this->createMock(CardDavBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->backgroundJob = new PruneOutdatedSyncTokensJob($this->timeFactory, $this->calDavBackend, $this->cardDavBackend, $this->config, $this->logger);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataForTestRun')]
	public function testRun(string $configToKeep, string $configRetentionDays, int $actualLimit, int $retentionDays, int $deletedCalendarSyncTokens, int $deletedAddressBookSyncTokens): void {
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->with(Application::APP_ID, self::anything(), self::anything())
			->willReturnCallback(function ($app, $key) use ($configToKeep, $configRetentionDays) {
				switch ($key) {
					case 'totalNumberOfSyncTokensToKeep':
						return $configToKeep;
					case 'syncTokensRetentionDays':
						return $configRetentionDays;
					default:
						throw new InvalidArgumentException();
				}
			});
		$this->calDavBackend->expects($this->once())
			->method('pruneOutdatedSyncTokens')
			->with($actualLimit)
			->willReturn($deletedCalendarSyncTokens);
		$this->cardDavBackend->expects($this->once())
			->method('pruneOutdatedSyncTokens')
			->with($actualLimit, $retentionDays)
			->willReturn($deletedAddressBookSyncTokens);
		$this->logger->expects($this->once())
			->method('info')
			->with('Pruned {calendarSyncTokensNumber} calendar sync tokens and {addressBooksSyncTokensNumber} address book sync tokens', [
				'calendarSyncTokensNumber' => $deletedCalendarSyncTokens,
				'addressBooksSyncTokensNumber' => $deletedAddressBookSyncTokens
			]);

		$this->backgroundJob->run(null);
	}

	public static function dataForTestRun(): array {
		return [
			['100', '2', 100, 7 * 24 * 3600, 2, 3],
			['100', '14', 100, 14 * 24 * 3600, 2, 3],
			['0', '60', 1, 60 * 24 * 3600, 0, 0]
		];
	}
}
