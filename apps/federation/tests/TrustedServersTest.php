<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Federation\Tests;

use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\Security\ISecureRandom;
use OCP\EventDispatcher\IEventDispatcher;
use Test\TestCase;
use Psr\Log\LoggerInterface;

class TrustedServersTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject | TrustedServers */
	private $trustedServers;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | DbHandler */
	private $dbHandler;

	/** @var \PHPUnit\Framework\MockObject\MockObject | IClientService */
	private $httpClientService;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | IClient */
	private $httpClient;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | IResponse */
	private $response;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | LoggerInterface */
	private $logger;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | IJobList */
	private $jobList;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | ISecureRandom */
	private $secureRandom;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | IConfig */
	private $config;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | IEventDispatcher */
	private $dispatcher;

	/** @var \PHPUnit\Framework\MockObject\MockObject|ITimeFactory */
	private $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dbHandler = $this->getMockBuilder(DbHandler::class)
			->disableOriginalConstructor()->getMock();
		$this->dispatcher = $this->getMockBuilder(IEventDispatcher::class)
			->disableOriginalConstructor()->getMock();
		$this->httpClientService = $this->getMockBuilder(IClientService::class)->getMock();
		$this->httpClient = $this->getMockBuilder(IClient::class)->getMock();
		$this->response = $this->getMockBuilder(IResponse::class)->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->jobList = $this->getMockBuilder(IJobList::class)->getMock();
		$this->secureRandom = $this->getMockBuilder(ISecureRandom::class)->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
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
		/** @var \PHPUnit\Framework\MockObject\MockObject|TrustedServers $trustedServers */
		$trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
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
			->setMethods(['normalizeUrl', 'updateProtocol'])
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
			->with('OCA\Federation\BackgroundJob\RequestSharedSecret',
				['url' => 'https://url', 'token' => 'token', 'created' => 1234567]);

		$this->assertSame(
			$trustedServers->addServer('url'),
			1
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
				function ($event) {
					$this->assertSame(get_class($event), \OCP\Federation\Events\TrustedServerRemovedEvent::class);
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

	public function testSetServerStatus() {
		$this->dbHandler->expects($this->once())->method('setServerStatus')
			->with('url', 1);
		$this->trustedServers->setServerStatus('url', 1);
	}

	public function testGetServerStatus() {
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

		/** @var \PHPUnit\Framework\MockObject\MockObject | TrustedServers $trustedServers */
		$trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
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
			->setMethods(['checkNextcloudVersion'])
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

	public function dataTestIsNextcloudServer(): array {
		return [
			[200, true, true],
			[200, false, false],
			[404, true, false],
		];
	}

	/**
	 * @expectedExceptionMessage simulated exception
	 */
	public function testIsNextcloudServerFail(): void {
		$server = 'server1';

		$this->httpClientService->expects($this->once())->method('newClient')
			->willReturn($this->httpClient);

		$this->httpClient->expects($this->once())->method('get')->with($server . '/status.php')
			->willReturnCallback(function () {
				throw new \Exception('simulated exception');
			});

		$this->assertFalse($this->trustedServers->isNextcloudServer($server));
	}

	/**
	 * @dataProvider dataTestCheckNextcloudVersion
	 */
	public function testCheckNextcloudVersion($status): void {
		$this->assertTrue($this->invokePrivate($this->trustedServers, 'checkNextcloudVersion', [$status]));
	}

	public function dataTestCheckNextcloudVersion(): array {
		return [
			['{"version":"9.0.0"}'],
			['{"version":"9.1.0"}']
		];
	}

	/**
	 * @dataProvider dataTestCheckNextcloudVersionTooLow
	 */
	public function testCheckNextcloudVersionTooLow(string $status): void {
		$this->expectException(\OCP\HintException::class);
		$this->expectExceptionMessage('Remote server version is too low. 9.0 is required.');

		$this->invokePrivate($this->trustedServers, 'checkNextcloudVersion', [$status]);
	}

	public function dataTestCheckNextcloudVersionTooLow(): array {
		return [
			['{"version":"8.2.3"}'],
		];
	}

	/**
	 * @dataProvider dataTestUpdateProtocol
	 */
	public function testUpdateProtocol(string $url, string $expected): void {
		$this->assertSame($expected,
			$this->invokePrivate($this->trustedServers, 'updateProtocol', [$url])
		);
	}

	public function dataTestUpdateProtocol(): array {
		return [
			['http://owncloud.org', 'http://owncloud.org'],
			['https://owncloud.org', 'https://owncloud.org'],
			['owncloud.org', 'https://owncloud.org'],
			['httpserver', 'https://httpserver'],
		];
	}
}
