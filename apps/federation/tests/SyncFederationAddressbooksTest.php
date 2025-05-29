<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests;

use OC\OCS\DiscoveryService;
use OCA\DAV\CardDAV\SyncService;
use OCA\Federation\DbHandler;
use OCA\Federation\SyncFederationAddressBooks;
use OCA\Federation\TrustedServers;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SyncFederationAddressbooksTest extends \Test\TestCase {
	private array $callBacks = [];
	private DiscoveryService&MockObject $discoveryService;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->discoveryService = $this->createMock(DiscoveryService::class);
		$this->discoveryService->expects($this->any())->method('discover')->willReturn([]);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	public function testSync(): void {
		/** @var DbHandler&MockObject $dbHandler */
		$dbHandler = $this->createMock(DbHandler::class);
		$dbHandler->method('getAllServer')
			->willReturn([
				[
					'url' => 'https://cloud.drop.box',
					'url_hash' => 'sha1',
					'shared_secret' => 'iloveowncloud',
					'sync_token' => '0'
				]
			]);
		$dbHandler->expects($this->once())->method('setServerStatus')->
			with('https://cloud.drop.box', 1, '1');
		$syncService = $this->createMock(SyncService::class);
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willReturn('1');

		/** @var SyncService $syncService */
		$s = new SyncFederationAddressBooks($dbHandler, $syncService, $this->discoveryService, $this->logger);
		$s->syncThemAll(function ($url, $ex): void {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertCount(1, $this->callBacks);
	}

	public function testException(): void {
		/** @var DbHandler&MockObject $dbHandler */
		$dbHandler = $this->createMock(DbHandler::class);
		$dbHandler->method('getAllServer')->
		willReturn([
			[
				'url' => 'https://cloud.drop.box',
				'url_hash' => 'sha1',
				'shared_secret' => 'iloveowncloud',
				'sync_token' => '0'
			]
		]);
		$syncService = $this->createMock(SyncService::class);
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willThrowException(new \Exception('something did not work out'));

		/** @var SyncService $syncService */
		$s = new SyncFederationAddressBooks($dbHandler, $syncService, $this->discoveryService, $this->logger);
		$s->syncThemAll(function ($url, $ex): void {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertCount(2, $this->callBacks);
	}

	public function testSuccessfulSyncWithoutChangesAfterFailure(): void {
		/** @var DbHandler&MockObject $dbHandler */
		$dbHandler = $this->createMock(DbHandler::class);
		$dbHandler->method('getAllServer')
			->willReturn([
				[
					'url' => 'https://cloud.drop.box',
					'url_hash' => 'sha1',
					'shared_secret' => 'ilovenextcloud',
					'sync_token' => '0'
				]
			]);
		$dbHandler->method('getServerStatus')->willReturn(TrustedServers::STATUS_FAILURE);
		$dbHandler->expects($this->once())->method('setServerStatus')->
			with('https://cloud.drop.box', 1);
		$syncService = $this->createMock(SyncService::class);
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willReturn('0');

		/** @var SyncService $syncService */
		$s = new SyncFederationAddressBooks($dbHandler, $syncService, $this->discoveryService, $this->logger);
		$s->syncThemAll(function ($url, $ex): void {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertCount(1, $this->callBacks);
	}
}
