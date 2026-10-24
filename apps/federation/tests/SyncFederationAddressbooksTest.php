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
use OCA\DAV\Exception\InvalidSyncTokenException;
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
					'url' => 'https://cloud.example.org',
					'url_hash' => 'sha1',
					'shared_secret' => 'ilovenextcloud',
					'sync_token' => '0'
				]
			]);
		$dbHandler->expects($this->once())->method('setServerStatus')
			->with('https://cloud.example.org', 1, '1');
		$syncService = $this->createMock(SyncService::class);
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willReturn(['1', false]);

		/** @var SyncService $syncService */
		$s = new SyncFederationAddressBooks($dbHandler, $syncService, $this->discoveryService, $this->logger);
		$s->syncThemAll(function ($url, $ex): void {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertCount(1, $this->callBacks);
	}

	public function testFullSyncWhenSyncTokenIsRejected(): void {
		/** @var DbHandler&MockObject $dbHandler */
		$dbHandler = $this->createMock(DbHandler::class);
		$dbHandler->method('getAllServer')
			->willReturn([
				[
					'url' => 'https://cloud.example.org',
					'url_hash' => 'sha1',
					'shared_secret' => 'ilovenextcloud',
					'sync_token' => 'http://sabre.io/ns/sync/30996077'
				]
			]);
		$dbHandler->expects($this->once())->method('setServerStatus')
			->with('https://cloud.example.org', TrustedServers::STATUS_OK, 'http://sabre.io/ns/sync/31000000');
		$syncService = $this->createMock(SyncService::class);
		$syncService->method('ensureSystemAddressBookExists')
			->willReturn(['id' => 42]);
		$calls = [];
		$syncService->expects($this->exactly(3))->method('syncRemoteAddressBook')
			->willReturnCallback(function (string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken) use (&$calls) {
				$calls[] = $syncToken;
				return match (count($calls)) {
					1 => throw new InvalidSyncTokenException(),
					2 => ['http://sabre.io/ns/sync/init_100_31000000', true],
					3 => ['http://sabre.io/ns/sync/31000000', false],
				};
			});
		$syncService->expects($this->once())->method('markCardsAsPending')->with(42);
		$syncService->expects($this->once())->method('deletePendingCards')->with(42);

		/** @var SyncService $syncService */
		$s = new SyncFederationAddressBooks($dbHandler, $syncService, $this->discoveryService, $this->logger);
		$s->syncThemAll(function ($url, $ex): void {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertSame([
			'http://sabre.io/ns/sync/30996077',
			null,
			'http://sabre.io/ns/sync/init_100_31000000',
		], $calls);
		$this->assertCount(1, $this->callBacks);
	}

	public function testException(): void {
		/** @var DbHandler&MockObject $dbHandler */
		$dbHandler = $this->createMock(DbHandler::class);
		$dbHandler->method('getAllServer')
			->willReturn([
				[
					'url' => 'https://cloud.example.org',
					'url_hash' => 'sha1',
					'shared_secret' => 'ilovenextcloud',
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
					'url' => 'https://cloud.example.org',
					'url_hash' => 'sha1',
					'shared_secret' => 'ilovenextcloud',
					'sync_token' => '0'
				]
			]);
		$dbHandler->method('getServerStatus')->willReturn(TrustedServers::STATUS_FAILURE);
		$dbHandler->expects($this->once())->method('setServerStatus')
			->with('https://cloud.example.org', 1);
		$syncService = $this->createMock(SyncService::class);
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willReturn(['0', false]);

		/** @var SyncService $syncService */
		$s = new SyncFederationAddressBooks($dbHandler, $syncService, $this->discoveryService, $this->logger);
		$s->syncThemAll(function ($url, $ex): void {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertCount(1, $this->callBacks);
	}
}
