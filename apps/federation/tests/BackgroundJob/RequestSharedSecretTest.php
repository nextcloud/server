<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
use OCP\IURLGenerator;
use OCP\OCS\IDiscoveryService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RequestSharedSecretTest extends TestCase {
	/** @var MockObject|IClientService */
	private $httpClientService;

	/** @var MockObject|IClient */
	private $httpClient;

	/** @var MockObject|IJobList */
	private $jobList;

	/** @var MockObject|IURLGenerator */
	private $urlGenerator;

	/** @var MockObject|TrustedServers */
	private $trustedServers;

	/** @var MockObject|IResponse */
	private $response;

	/** @var MockObject|IDiscoveryService */
	private $discoveryService;

	/** @var MockObject|LoggerInterface */
	private $logger;

	/** @var MockObject|ITimeFactory */
	private $timeFactory;

	/** @var  RequestSharedSecret */
	private $requestSharedSecret;

	protected function setUp(): void {
		parent::setUp();

		$this->httpClientService = $this->createMock(IClientService::class);
		$this->httpClient = $this->getMockBuilder(IClient::class)->getMock();
		$this->jobList = $this->getMockBuilder(IJobList::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->trustedServers = $this->getMockBuilder(TrustedServers::class)
			->disableOriginalConstructor()->getMock();
		$this->response = $this->getMockBuilder(IResponse::class)->getMock();
		$this->discoveryService = $this->getMockBuilder(IDiscoveryService::class)->getMock();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->discoveryService->expects($this->any())->method('discover')->willReturn([]);
		$this->httpClientService->expects($this->any())->method('newClient')->willReturn($this->httpClient);

		$this->requestSharedSecret = new RequestSharedSecret(
			$this->httpClientService,
			$this->urlGenerator,
			$this->jobList,
			$this->trustedServers,
			$this->discoveryService,
			$this->logger,
			$this->timeFactory
		);
	}

	/**
	 * @dataProvider dataTestStart
	 *
	 * @param bool $isTrustedServer
	 * @param bool $retainBackgroundJob
	 */
	public function testStart($isTrustedServer, $retainBackgroundJob) {
		/** @var RequestSharedSecret |MockObject $requestSharedSecret */
		$requestSharedSecret = $this->getMockBuilder('OCA\Federation\BackgroundJob\RequestSharedSecret')
			->setConstructorArgs(
				[
					$this->httpClientService,
					$this->urlGenerator,
					$this->jobList,
					$this->trustedServers,
					$this->discoveryService,
					$this->logger,
					$this->timeFactory
				]
			)->setMethods(['parentStart'])->getMock();
		$this->invokePrivate($requestSharedSecret, 'argument', [['url' => 'url', 'token' => 'token']]);

		$this->trustedServers->expects($this->once())->method('isTrustedServer')
			->with('url')->willReturn($isTrustedServer);
		if ($isTrustedServer) {
			$requestSharedSecret->expects($this->once())->method('parentStart');
		} else {
			$requestSharedSecret->expects($this->never())->method('parentStart');
		}
		$this->invokePrivate($requestSharedSecret, 'retainJob', [$retainBackgroundJob]);
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

	public function dataTestStart() {
		return [
			[true, true],
			[true, false],
			[false, false],
		];
	}

	/**
	 * @dataProvider dataTestRun
	 *
	 * @param int $statusCode
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
				]
			)->willReturn($this->response);

		$this->response->expects($this->once())->method('getStatusCode')
			->willReturn($statusCode);

		$this->invokePrivate($this->requestSharedSecret, 'run', [$argument]);
		if (
			$statusCode !== Http::STATUS_OK
			&& ($statusCode !== Http::STATUS_FORBIDDEN || $attempt < 5)
		) {
			$this->assertTrue($this->invokePrivate($this->requestSharedSecret, 'retainJob'));
		} else {
			$this->assertFalse($this->invokePrivate($this->requestSharedSecret, 'retainJob'));
		}
	}

	public function dataTestRun() {
		return [
			[Http::STATUS_OK],
			[Http::STATUS_FORBIDDEN, 5],
			[Http::STATUS_FORBIDDEN],
			[Http::STATUS_CONFLICT],
		];
	}

	public function testRunExpired() {
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

		$this->invokePrivate($this->requestSharedSecret, 'run', [$argument]);
	}

	public function testRunConnectionError() {
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
				]
			)->willThrowException($this->createMock(ConnectException::class));

		$this->invokePrivate($this->requestSharedSecret, 'run', [$argument]);
		$this->assertTrue($this->invokePrivate($this->requestSharedSecret, 'retainJob'));
	}
}
