<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\WebcalCaching\Connection;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\LocalServerException;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

use Test\TestCase;

class ConnectionTest extends TestCase {

	private IClientService&MockObject $clientService;
	private IAppConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private Connection $connection;

	public function setUp(): void {
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->connection = new Connection($this->clientService, $this->config, $this->logger);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'runLocalURLDataProvider')]
	public function testLocalUrl($source): void {
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => $source,
			'lastmodified' => 0,
		];

		$client = $this->createMock(IClient::class);
		$this->clientService->expects(self::once())
			->method('newClient')
			->with()
			->willReturn($client);

		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->willReturn('no');

		$localServerException = new LocalServerException();
		$client->expects(self::once())
			->method('get')
			->willThrowException($localServerException);
		$this->logger->expects(self::once())
			->method('warning')
			->with('Subscription 42 was not refreshed because it violates local access rules', ['exception' => $localServerException]);

		$this->connection->queryWebcalFeed($subscription);
	}

	public function testInvalidUrl(): void {
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => '!@#$',
			'lastmodified' => 0,
		];

		$client = $this->createMock(IClient::class);
		$this->config->expects(self::never())
			->method('getValueString');
		$client->expects(self::never())
			->method('get');

		$this->connection->queryWebcalFeed($subscription);

	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'urlDataProvider')]
	public function testConnection(string $url, string $contentType, string $expectedFormat): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => $url,
			'lastmodified' => 0,
		];

		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->willReturn($client);

		$this->config->expects($this->once())
			->method('getValueString')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->willReturn('no');

		$client->expects($this->once())
			->method('get')
			->with('https://foo.bar/bla2')
			->willReturn($response);

		$response->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn($contentType);

		// Create a stream resource to simulate streaming response
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, 'test calendar data');
		rewind($stream);

		$response->expects($this->once())
			->method('getBody')
			->willReturn($stream);

		$output = $this->connection->queryWebcalFeed($subscription);

		$this->assertIsArray($output);
		$this->assertArrayHasKey('data', $output);
		$this->assertArrayHasKey('format', $output);
		$this->assertIsResource($output['data']);
		$this->assertEquals($expectedFormat, $output['format']);

		// Cleanup
		if (is_resource($output['data'])) {
			fclose($output['data']);
		}
	}

	public function testConnectionReturnsNullWhenBodyIsNotResource(): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => 'https://foo.bar/bla2',
			'lastmodified' => 0,
		];

		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->willReturn($client);

		$this->config->expects($this->once())
			->method('getValueString')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->willReturn('no');

		$client->expects($this->once())
			->method('get')
			->with('https://foo.bar/bla2')
			->willReturn($response);

		$response->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('text/calendar');

		// Return a string instead of a resource
		$response->expects($this->once())
			->method('getBody')
			->willReturn('not a resource');

		$output = $this->connection->queryWebcalFeed($subscription);

		$this->assertNull($output);
	}

	public static function runLocalURLDataProvider(): array {
		return [
			['localhost/foo.bar'],
			['localHost/foo.bar'],
			['random-host/foo.bar'],
			['[::1]/bla.blub'],
			['[::]/bla.blub'],
			['192.168.0.1'],
			['172.16.42.1'],
			['[fdf8:f53b:82e4::53]/secret.ics'],
			['[fe80::200:5aee:feaa:20a2]/secret.ics'],
			['[0:0:0:0:0:0:10.0.0.1]/secret.ics'],
			['[0:0:0:0:0:ffff:127.0.0.0]/secret.ics'],
			['10.0.0.1'],
			['another-host.local'],
			['service.localhost'],
		];
	}

	public static function urlDataProvider(): array {
		return [
			['https://foo.bar/bla2', 'text/calendar;charset=utf8', 'ical'],
			['https://foo.bar/bla2', 'application/calendar+json', 'jcal'],
			['https://foo.bar/bla2', 'application/calendar+xml', 'xcal'],
		];
	}
}
