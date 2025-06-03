<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\BackgroundJob;

use GuzzleHttp\Exception\ConnectException;
use OCA\Federation\BackgroundJob\RequestSharedSecret;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\OCS\IDiscoveryService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RequestSharedSecretTest extends TestCase {
	private IClientService&MockObject $httpClientService;
	private IClient&MockObject $httpClient;
	private IJobList&MockObject $jobList;
	private IURLGenerator&MockObject $urlGenerator;
	private TrustedServers&MockObject $trustedServers;
	private IResponse&MockObject $response;
	private IDiscoveryService&MockObject $discoveryService;
	private LoggerInterface&MockObject $logger;
	private ITimeFactory&MockObject $timeFactory;
	private IConfig&MockObject $config;
	private RequestSharedSecret $requestSharedSecret;

	protected function setUp(): void {
		parent::setUp();

		$this->httpClientService = $this->createMock(IClientService::class);
		$this->httpClient = $this->createMock(IClient::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->response = $this->createMock(IResponse::class);
		$this->discoveryService = $this->createMock(IDiscoveryService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);

		$this->discoveryService->expects($this->any())->method('discover')->willReturn([]);
		$this->httpClientService->expects($this->any())->method('newClient')->willReturn($this->httpClient);

		$this->requestSharedSecret = new RequestSharedSecret(
			$this->httpClientService,
			$this->urlGenerator,
			$this->jobList,
			$this->trustedServers,
			$this->discoveryService,
			$this->logger,
			$this->timeFactory,
			$this->config,
		);
	}

	/**
	 * @dataProvider dataTestStart
	 */
	public function testStart(bool $isTrustedServer, bool $retainBackgroundJob): void {
		/** @var RequestSharedSecret&MockObject $requestSharedSecret */
		$requestSharedSecret = $this->getMockBuilder(RequestSharedSecret::class)
			->setConstructorArgs(
				[
					$this->httpClientService,
					$this->urlGenerator,
					$this->jobList,
					$this->trustedServers,
					$this->discoveryService,
					$this->logger,
					$this->timeFactory,
					$this->config,
				]
			)
			->onlyMethods(['parentStart'])
			->getMock();
		self::invokePrivate($requestSharedSecret, 'argument', [['url' => 'url', 'token' => 'token']]);

		$this->trustedServers->expects($this->once())->method('isTrustedServer')
			->with('url')->willReturn($isTrustedServer);
		if ($isTrustedServer) {
			$requestSharedSecret->expects($this->once())->method('parentStart');
		} else {
			$requestSharedSecret->expects($this->never())->method('parentStart');
		}
		self::invokePrivate($requestSharedSecret, 'retainJob', [$retainBackgroundJob]);
		$this->jobList->expects($this->once())->method('remove');

		$this->timeFactory->method('getTime')->willReturn(42);

		if ($retainBackgroundJob) {
			$this->jobList->expects($this->once())
				->method('add')
				->with(
					RequestSharedSecret::class,
					[
						'url' => 'url',
						'token' => 'token',
						'created' => 42,
						'attempt' => 1,
					]
				);
		} else {
			$this->jobList->expects($this->never())->method('add');
		}

		$requestSharedSecret->start($this->jobList);
	}

	public static function dataTestStart(): array {
		return [
			[true, true],
			[true, false],
			[false, false],
		];
	}

	/**
	 * @dataProvider dataTestRun
	 */
	public function testRun(int $statusCode, int $attempt = 0): void {
		$target = 'targetURL';
		$source = 'sourceURL';
		$token = 'token';

		$argument = ['url' => $target, 'token' => $token, 'attempt' => $attempt];

		$this->timeFactory->method('getTime')->willReturn(42);

		$this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/')
			->willReturn($source);
		$this->httpClient->expects($this->once())->method('post')
			->with(
				$target . '/ocs/v2.php/apps/federation/api/v1/request-shared-secret',
				[
					'body' =>
						[
							'url' => $source,
							'token' => $token,
							'format' => 'json',
						],
					'timeout' => 3,
					'connect_timeout' => 3,
					'verify' => true,
				]
			)->willReturn($this->response);

		$this->response->expects($this->once())->method('getStatusCode')
			->willReturn($statusCode);

		self::invokePrivate($this->requestSharedSecret, 'run', [$argument]);
		if (
			$statusCode !== Http::STATUS_OK
			&& ($statusCode !== Http::STATUS_FORBIDDEN || $attempt < 5)
		) {
			$this->assertTrue(self::invokePrivate($this->requestSharedSecret, 'retainJob'));
		} else {
			$this->assertFalse(self::invokePrivate($this->requestSharedSecret, 'retainJob'));
		}
	}

	public static function dataTestRun(): array {
		return [
			[Http::STATUS_OK],
			[Http::STATUS_FORBIDDEN, 5],
			[Http::STATUS_FORBIDDEN],
			[Http::STATUS_CONFLICT],
		];
	}

	public function testRunExpired(): void {
		$target = 'targetURL';
		$source = 'sourceURL';
		$token = 'token';
		$created = 42;

		$argument = [
			'url' => $target,
			'token' => $token,
			'created' => $created,
		];

		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn($source);

		$this->timeFactory->method('getTime')
			->willReturn($created + 2592000 + 1);

		$this->trustedServers->expects($this->once())
			->method('setServerStatus')
			->with(
				$target,
				TrustedServers::STATUS_FAILURE
			);

		self::invokePrivate($this->requestSharedSecret, 'run', [$argument]);
	}

	public function testRunConnectionError(): void {
		$target = 'targetURL';
		$source = 'sourceURL';
		$token = 'token';

		$argument = ['url' => $target, 'token' => $token];

		$this->timeFactory->method('getTime')->willReturn(42);

		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn($source);

		$this->httpClient
			->expects($this->once())
			->method('post')
			->with(
				$target . '/ocs/v2.php/apps/federation/api/v1/request-shared-secret',
				[
					'body' =>
						[
							'url' => $source,
							'token' => $token,
							'format' => 'json',
						],
					'timeout' => 3,
					'connect_timeout' => 3,
					'verify' => true,
				]
			)->willThrowException($this->createMock(ConnectException::class));

		self::invokePrivate($this->requestSharedSecret, 'run', [$argument]);
		$this->assertTrue(self::invokePrivate($this->requestSharedSecret, 'retainJob'));
	}
}
