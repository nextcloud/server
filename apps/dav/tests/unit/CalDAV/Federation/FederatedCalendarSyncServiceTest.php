<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\Federation\FederatedCalendarSyncService;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FederatedCalendarSyncServiceTest extends TestCase {
	private FederatedCalendarSyncService $federatedCalendarSyncService;

	private FederatedCalendarMapper&MockObject $federatedCalendarMapper;
	private LoggerInterface&MockObject $logger;
	private CalDavBackend&MockObject $backend;
	private IDBConnection&MockObject $dbConnection;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IClientService&MockObject $clientService;
	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->federatedCalendarMapper = $this->createMock(FederatedCalendarMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->backend = $this->createMock(CalDavBackend::class);
		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->federatedCalendarSyncService = new FederatedCalendarSyncService(
			$this->clientService,
			$this->config,
			$this->federatedCalendarMapper,
			$this->logger,
			$this->backend,
			$this->dbConnection,
			$this->cloudIdManager,
		);
	}

	public function testSyncOne(): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		// Mock HTTP client for sync report
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:sync-token>http://sabre.io/ns/sync/101</d:sync-token></d:multistatus>');

		$client->expects(self::once())
			->method('request')
			->with('REPORT', 'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', self::anything())
			->willReturn($response);

		$this->clientService->method('newClient')
			->willReturn($client);

		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTokenAndTime')
			->with(1, 101);
		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTime');

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public function testSyncOneUnchanged(): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		// Mock HTTP client for sync report
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:sync-token>http://sabre.io/ns/sync/100</d:sync-token></d:multistatus>');

		$client->expects(self::once())
			->method('request')
			->with('REPORT', 'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', self::anything())
			->willReturn($response);

		$this->clientService->method('newClient')
			->willReturn($client);

		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTokenAndTime');
		$this->federatedCalendarMapper->expects(self::once())
			->method('updateSyncTime')
			->with(1);

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}

	public static function provideUnexpectedSyncTokenData(): array {
		return [
			['http://sabre.io/ns/sync/'],
			['http://sabre.io/ns/sync/foobar'],
			['http://sabre.io/ns/sync/23abc'],
			['http://nextcloud.com/ns/sync/33'],
		];
	}

	#[DataProvider(methodName: 'provideUnexpectedSyncTokenData')]
	public function testSyncOneWithUnexpectedSyncTokenFormat(string $syncToken): void {
		$calendar = new FederatedCalendarEntity();
		$calendar->setId(1);
		$calendar->setPrincipaluri('principals/users/user1');
		$calendar->setRemoteUrl('https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2');
		$calendar->setSyncToken(100);
		$calendar->setToken('token');

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getId')
			->willReturn('user1@nextcloud.testing');
		$this->cloudIdManager->expects(self::once())
			->method('getCloudId')
			->with('user1')
			->willReturn($cloudId);

		// Mock HTTP client for sync report with unexpected token format
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:sync-token>' . $syncToken . '</d:sync-token></d:multistatus>');

		$client->expects(self::once())
			->method('request')
			->with('REPORT', 'https://remote.tld/remote.php/dav/remote-calendars/abcdef123/cal1_shared_by_user2', self::anything())
			->willReturn($response);

		$this->clientService->method('newClient')
			->willReturn($client);

		$this->config->method('getSystemValueInt')
			->willReturn(30);
		$this->config->method('getSystemValue')
			->willReturn(false);

		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTokenAndTime');
		$this->federatedCalendarMapper->expects(self::never())
			->method('updateSyncTime');

		$this->assertEquals(0, $this->federatedCalendarSyncService->syncOne($calendar));
	}
}
