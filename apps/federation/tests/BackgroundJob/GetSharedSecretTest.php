<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\BackgroundJob;

use GuzzleHttp\Exception\ConnectException;
use OCA\Federation\BackgroundJob\GetSharedSecret;
use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\Tests\TestCase;
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

/**
 * Class GetSharedSecretTest
 *
 * @group DB
 *
 * @package OCA\Federation\Tests\BackgroundJob
 */
class GetSharedSecretTest extends TestCase {

	private MockObject&IClient $httpClient;
	private MockObject&IClientService $httpClientService;
	private MockObject&IJobList $jobList;
	private MockObject&IURLGenerator $urlGenerator;
	private MockObject&TrustedServers $trustedServers;
	private MockObject&LoggerInterface $logger;
	private MockObject&IResponse $response;
	private MockObject&IDiscoveryService $discoverService;
	private MockObject&ITimeFactory $timeFactory;
	private MockObject&IConfig $config;

	private GetSharedSecret $getSharedSecret;

	protected function setUp(): void {
		parent::setUp();

		$this->httpClientService = $this->createMock(IClientService::class);
		$this->httpClient = $this->getMockBuilder(IClient::class)->getMock();
		$this->jobList = $this->getMockBuilder(IJobList::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->trustedServers = $this->getMockBuilder(TrustedServers::class)
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->response = $this->getMockBuilder(IResponse::class)->getMock();
		$this->discoverService = $this->getMockBuilder(IDiscoveryService::class)->getMock();
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);

		$this->discoverService->expects($this->any())->method('discover')->willReturn([]);
		$this->httpClientService->expects($this->any())->method('newClient')->willReturn($this->httpClient);

		$this->getSharedSecret = new GetSharedSecret(
			$this->httpClientService,
			$this->urlGenerator,
			$this->jobList,
			$this->trustedServers,
			$this->logger,
			$this->discoverService,
			$this->timeFactory,
			$this->config,
		);
	}

	/**
	 * @dataProvider dataTestExecute
	 */
	public function testExecute(bool $isTrustedServer, bool $retainBackgroundJob): void {
		/** @var GetSharedSecret&MockObject $getSharedSecret */
		$getSharedSecret = $this->getMockBuilder(GetSharedSecret::class)
			->setConstructorArgs(
				[
					$this->httpClientService,
					$this->urlGenerator,
					$this->jobList,
					$this->trustedServers,
					$this->logger,
					$this->discoverService,
					$this->timeFactory,
					$this->config,
				]
			)
			->onlyMethods(['parentStart'])
			->getMock();
		self::invokePrivate($getSharedSecret, 'argument', [['url' => 'url', 'token' => 'token']]);

		$this->trustedServers->expects($this->once())->method('isTrustedServer')
			->with('url')->willReturn($isTrustedServer);
		if ($isTrustedServer) {
			$getSharedSecret->expects($this->once())->method('parentStart');
		} else {
			$getSharedSecret->expects($this->never())->method('parentStart');
		}
		self::invokePrivate($getSharedSecret, 'retainJob', [$retainBackgroundJob]);
		$this->jobList->expects($this->once())->method('remove');

		$this->timeFactory->method('getTime')->willReturn(42);

		if ($retainBackgroundJob) {
			$this->jobList->expects($this->once())
				->method('add')
				->with(
					GetSharedSecret::class,
					[
						'url' => 'url',
						'token' => 'token',
						'created' => 42,
					]
				);
		} else {
			$this->jobList->expects($this->never())->method('add');
		}

		$getSharedSecret->start($this->jobList);
	}

	public static function dataTestExecute(): array {
		return [
			[true, true],
			[true, false],
			[false, false],
		];
	}

	/**
	 * @dataProvider dataTestRun
	 */
	public function testRun(int $statusCode): void {
		$target = 'targetURL';
		$source = 'sourceURL';
		$token = 'token';

		$argument = ['url' => $target, 'token' => $token];

		$this->timeFactory->method('getTime')
			->willReturn(42);

		$this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/')
			->willReturn($source);
		$this->httpClient->expects($this->once())->method('get')
			->with(
				$target . '/ocs/v2.php/apps/federation/api/v1/shared-secret',
				[
					'query' =>
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

		if ($statusCode === Http::STATUS_OK) {
			$this->response->expects($this->once())->method('getBody')
				->willReturn('{"ocs":{"data":{"sharedSecret":"secret"}}}');
			$this->trustedServers->expects($this->once())->method('addSharedSecret')
				->with($target, 'secret');
		} else {
			$this->trustedServers->expects($this->never())->method('addSharedSecret');
		}

		self::invokePrivate($this->getSharedSecret, 'run', [$argument]);
		if (
			$statusCode !== Http::STATUS_OK
			&& $statusCode !== Http::STATUS_FORBIDDEN
		) {
			$this->assertTrue(self::invokePrivate($this->getSharedSecret, 'retainJob'));
		} else {
			$this->assertFalse(self::invokePrivate($this->getSharedSecret, 'retainJob'));
		}
	}

	public static function dataTestRun(): array {
		return [
			[Http::STATUS_OK],
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

		self::invokePrivate($this->getSharedSecret, 'run', [$argument]);
	}

	public function testRunConnectionError(): void {
		$target = 'targetURL';
		$source = 'sourceURL';
		$token = 'token';

		$argument = ['url' => $target, 'token' => $token];

		$this->timeFactory->method('getTime')
			->willReturn(42);

		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/')
			->willReturn($source);
		$this->httpClient->expects($this->once())->method('get')
			->with(
				$target . '/ocs/v2.php/apps/federation/api/v1/shared-secret',
				[
					'query' =>
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

		$this->trustedServers->expects($this->never())->method('addSharedSecret');

		self::invokePrivate($this->getSharedSecret, 'run', [$argument]);

		$this->assertTrue(self::invokePrivate($this->getSharedSecret, 'retainJob'));
	}
}
