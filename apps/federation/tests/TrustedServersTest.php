<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests;

use OCA\Federation\BackgroundJob\RequestSharedSecret;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use OCP\HintException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TrustedServersTest extends TestCase {
	private TrustedServers $trustedServers;
	private DbHandler&MockObject $dbHandler;
	private IClientService&MockObject $httpClientService;
	private IClient&MockObject $httpClient;
	private IResponse&MockObject $response;
	private LoggerInterface&MockObject $logger;
	private IJobList&MockObject $jobList;
	private ISecureRandom&MockObject $secureRandom;
	private IConfig&MockObject $config;
	private IEventDispatcher&MockObject $dispatcher;
	private ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dbHandler = $this->createMock(DbHandler::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->httpClientService = $this->createMock(IClientService::class);
		$this->httpClient = $this->createMock(IClient::class);
		$this->response = $this->createMock(IResponse::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->trustedServers = new TrustedServers(
			$this->dbHandler,
			$this->httpClientService,
			$this->logger,
			$this->jobList,
			$this->secureRandom,
			$this->config,
			$this->dispatcher,
			$this->timeFactory
		);
	}

	public function testAddServer(): void {
		/** @var TrustedServers&MockObject $trustedServers */
		$trustedServers = $this->getMockBuilder(TrustedServers::class)
			->setConstructorArgs(
				[
					$this->dbHandler,
					$this->httpClientService,
					$this->logger,
					$this->jobList,
					$this->secureRandom,
					$this->config,
					$this->dispatcher,
					$this->timeFactory
				]
			)
			->onlyMethods(['updateProtocol'])
			->getMock();
		$trustedServers->expects($this->once())->method('updateProtocol')
			->with('url')->willReturn('https://url');
		$this->timeFactory->method('getTime')
			->willReturn(1234567);
		$this->dbHandler->expects($this->once())->method('addServer')->with('https://url')
			->willReturn(1);

		$this->secureRandom->expects($this->once())->method('generate')
			->willReturn('token');
		$this->dbHandler->expects($this->once())->method('addToken')->with('https://url', 'token');
		$this->jobList->expects($this->once())->method('add')
			->with(RequestSharedSecret::class,
				['url' => 'https://url', 'token' => 'token', 'created' => 1234567]);

		$this->assertSame(
			1,
			$trustedServers->addServer('url')
		);
	}

	public function testAddSharedSecret(): void {
		$this->dbHandler->expects($this->once())->method('addSharedSecret')
			->with('url', 'secret');
		$this->trustedServers->addSharedSecret('url', 'secret');
	}

	public function testGetSharedSecret(): void {
		$this->dbHandler->expects($this->once())
			->method('getSharedSecret')
			->with('url')
			->willReturn('secret');
		$this->assertSame(
			$this->trustedServers->getSharedSecret('url'),
			'secret'
		);
	}

	public function testRemoveServer(): void {
		$id = 42;
		$server = ['url_hash' => 'url_hash'];
		$this->dbHandler->expects($this->once())->method('removeServer')->with($id);
		$this->dbHandler->expects($this->once())->method('getServerById')->with($id)
			->willReturn($server);
		$this->dispatcher->expects($this->once())->method('dispatchTyped')
			->willReturnCallback(
				function ($event): void {
					$this->assertSame(get_class($event), TrustedServerRemovedEvent::class);
					/** @var \OCP\Federated\Events\TrustedServerRemovedEvent $event */
					$this->assertSame('url_hash', $event->getUrlHash());
				}
			);
		$this->trustedServers->removeServer($id);
	}

	public function testGetServers(): void {
		$this->dbHandler->expects($this->once())->method('getAllServer')->willReturn(['servers']);

		$this->assertEquals(
			['servers'],
			$this->trustedServers->getServers()
		);
	}


	public function testIsTrustedServer(): void {
		$this->dbHandler->expects($this->once())
			->method('serverExists')->with('url')
			->willReturn(true);

		$this->assertTrue(
			$this->trustedServers->isTrustedServer('url')
		);
	}

	public function testSetServerStatus(): void {
		$this->dbHandler->expects($this->once())->method('setServerStatus')
			->with('url', 1);
		$this->trustedServers->setServerStatus('url', 1);
	}

	public function testGetServerStatus(): void {
		$this->dbHandler->expects($this->once())->method('getServerStatus')
			->with('url')->willReturn(1);
		$this->assertSame(
			$this->trustedServers->getServerStatus('url'),
			1
		);
	}

	/**
	 * @dataProvider dataTestIsNextcloudServer
	 */
	public function testIsNextcloudServer(int $statusCode, bool $isValidNextcloudVersion, bool $expected): void {
		$server = 'server1';

		/** @var TrustedServers&MockObject $trustedServers */
		$trustedServers = $this->getMockBuilder(TrustedServers::class)
			->setConstructorArgs(
				[
					$this->dbHandler,
					$this->httpClientService,
					$this->logger,
					$this->jobList,
					$this->secureRandom,
					$this->config,
					$this->dispatcher,
					$this->timeFactory
				]
			)
			->onlyMethods(['checkNextcloudVersion'])
			->getMock();

		$this->httpClientService->expects($this->once())->method('newClient')
			->willReturn($this->httpClient);

		$this->httpClient->expects($this->once())->method('get')->with($server . '/status.php')
			->willReturn($this->response);

		$this->response->expects($this->once())->method('getStatusCode')
			->willReturn($statusCode);

		if ($statusCode === 200) {
			$this->response->expects($this->once())->method('getBody')
				->willReturn('');
			$trustedServers->expects($this->once())->method('checkNextcloudVersion')
				->willReturn($isValidNextcloudVersion);
		} else {
			$trustedServers->expects($this->never())->method('checkNextcloudVersion');
		}

		$this->assertSame($expected,
			$trustedServers->isNextcloudServer($server)
		);
	}

	public static function dataTestIsNextcloudServer(): array {
		return [
			[200, true, true],
			[200, false, false],
			[404, true, false],
		];
	}

	public function testIsNextcloudServerFail(): void {
		$server = 'server1';

		$this->httpClientService->expects($this->once())
			->method('newClient')
			->willReturn($this->httpClient);

		$this->httpClient->expects($this->once())
			->method('get')
			->with($server . '/status.php')
			->willThrowException(new \Exception('simulated exception'));

		$this->assertFalse($this->trustedServers->isNextcloudServer($server));
	}

	/**
	 * @dataProvider dataTestCheckNextcloudVersion
	 */
	public function testCheckNextcloudVersion(string $status): void {
		$this->assertTrue(self::invokePrivate($this->trustedServers, 'checkNextcloudVersion', [$status]));
	}

	public static function dataTestCheckNextcloudVersion(): array {
		return [
			['{"version":"9.0.0"}'],
			['{"version":"9.1.0"}']
		];
	}

	/**
	 * @dataProvider dataTestCheckNextcloudVersionTooLow
	 */
	public function testCheckNextcloudVersionTooLow(string $status): void {
		$this->expectException(HintException::class);
		$this->expectExceptionMessage('Remote server version is too low. 9.0 is required.');

		self::invokePrivate($this->trustedServers, 'checkNextcloudVersion', [$status]);
	}

	public static function dataTestCheckNextcloudVersionTooLow(): array {
		return [
			['{"version":"8.2.3"}'],
		];
	}

	/**
	 * @dataProvider dataTestUpdateProtocol
	 */
	public function testUpdateProtocol(string $url, string $expected): void {
		$this->assertSame($expected,
			self::invokePrivate($this->trustedServers, 'updateProtocol', [$url])
		);
	}

	public static function dataTestUpdateProtocol(): array {
		return [
			['http://owncloud.org', 'http://owncloud.org'],
			['https://owncloud.org', 'https://owncloud.org'],
			['owncloud.org', 'https://owncloud.org'],
			['httpserver', 'https://httpserver'],
		];
	}
}
