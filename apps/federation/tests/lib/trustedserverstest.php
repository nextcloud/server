<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\Federation\Tests\lib;


use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class TrustedServersTest extends TestCase {

	/** @var  TrustedServers */
	private $trustedServers;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | DbHandler */
	private $dbHandler;

	/** @var \PHPUnit_Framework_MockObject_MockObject | IClientService */
	private $httpClientService;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | IClient */
	private $httpClient;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | IResponse */
	private $response;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | ILogger */
	private $logger;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | IJobList */
	private $jobList;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | ISecureRandom */
	private $secureRandom;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | IConfig */
	private $config;

	public function setUp() {
		parent::setUp();

		$this->dbHandler = $this->getMockBuilder('\OCA\Federation\DbHandler')
			->disableOriginalConstructor()->getMock();
		$this->httpClientService = $this->getMock('OCP\Http\Client\IClientService');
		$this->httpClient = $this->getMock('OCP\Http\Client\IClient');
		$this->response = $this->getMock('OCP\Http\Client\IResponse');
		$this->logger = $this->getMock('OCP\ILogger');
		$this->jobList = $this->getMock('OCP\BackgroundJob\IJobList');
		$this->secureRandom = $this->getMock('OCP\Security\ISecureRandom');
		$this->config = $this->getMock('OCP\IConfig');

		$this->trustedServers = new TrustedServers(
			$this->dbHandler,
			$this->httpClientService,
			$this->logger,
			$this->jobList,
			$this->secureRandom,
			$this->config
		);

	}

	/**
	 * @dataProvider dataTrueFalse
	 *
	 * @param bool $success
	 */
	public function testAddServer($success) {
		/** @var \PHPUnit_Framework_MockObject_MockObject | TrustedServers $trustedServer */
		$trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
			->setConstructorArgs(
				[
					$this->dbHandler,
					$this->httpClientService,
					$this->logger,
					$this->jobList,
					$this->secureRandom,
					$this->config
				]
			)
			->setMethods(['normalizeUrl', 'updateProtocol'])
			->getMock();
		$trustedServers->expects($this->once())->method('updateProtocol')
				->with('url')->willReturn('https://url');
		$this->dbHandler->expects($this->once())->method('addServer')->with('https://url')
			->willReturn($success);

		if ($success) {
			$this->secureRandom->expects($this->once())->method('generate')
				->willReturn('token');
			$this->dbHandler->expects($this->once())->method('addToken')->with('https://url', 'token');
			$this->jobList->expects($this->once())->method('add')
				->with('OCA\Federation\BackgroundJob\RequestSharedSecret',
						['url' => 'https://url', 'token' => 'token']);
		} else {
			$this->jobList->expects($this->never())->method('add');
		}

		$this->assertSame($success,
			$trustedServers->addServer('url')
		);
	}

	public function dataTrueFalse() {
		return [
			[true],
			[false]
		];
	}

	/**
	 * @dataProvider dataTrueFalse
	 *
	 * @param bool $status
	 */
	public function testSetAutoAddServers($status) {
		if ($status) {
			$this->config->expects($this->once())->method('setAppValue')
				->with('federation', 'autoAddServers', '1');
		} else {
			$this->config->expects($this->once())->method('setAppValue')
				->with('federation', 'autoAddServers', '0');
		}

		$this->trustedServers->setAutoAddServers($status);
	}

	/**
	 * @dataProvider dataTestGetAutoAddServers
	 *
	 * @param string $status
	 * @param bool $expected
	 */
	public function testGetAutoAddServers($status, $expected) {
		$this->config->expects($this->once())->method('getAppValue')
			->with('federation', 'autoAddServers', '1')->willReturn($status);

		$this->assertSame($expected,
			$this->trustedServers->getAutoAddServers($status)
		);
	}

	public function dataTestGetAutoAddServers() {
		return [
			['1', true],
			['0', false]
		];
	}

	public function testAddSharedSecret() {
		$this->dbHandler->expects($this->once())->method('addSharedSecret')
			->with('url', 'secret');
		$this->trustedServers->addSharedSecret('url', 'secret');
	}

	public function testGetSharedSecret() {
		$this->dbHandler->expects($this->once())->method('getSharedSecret')
			->with('url')->willReturn(true);
		$this->assertTrue(
			$this->trustedServers->getSharedSecret('url')
		);
	}

	public function testRemoveServer() {
		$id = 42;
		$this->dbHandler->expects($this->once())->method('removeServer')->with($id);
		$this->trustedServers->removeServer($id);
	}

	public function testGetServers() {
		$this->dbHandler->expects($this->once())->method('getAllServer')->willReturn(true);

		$this->assertTrue(
			$this->trustedServers->getServers()
		);
	}


	public function testIsTrustedServer() {
		$this->dbHandler->expects($this->once())->method('serverExists')->with('url')
			->willReturn(true);

		$this->assertTrue(
			$this->trustedServers->isTrustedServer('url')
		);
	}

	public function testSetServerStatus() {
		$this->dbHandler->expects($this->once())->method('setServerStatus')
			->with('url', 'status');
		$this->trustedServers->setServerStatus('url', 'status');
	}

	public function testGetServerStatus() {
		$this->dbHandler->expects($this->once())->method('getServerStatus')
			->with('url')->willReturn(true);
		$this->assertTrue(
			$this->trustedServers->getServerStatus('url')
		);
	}

	/**
	 * @dataProvider dataTestIsOwnCloudServer
	 *
	 * @param int $statusCode
	 * @param bool $isValidOwnCloudVersion
	 * @param bool $expected
	 */
	public function testIsOwnCloudServer($statusCode, $isValidOwnCloudVersion, $expected) {

		$server = 'server1';

		/** @var \PHPUnit_Framework_MockObject_MockObject | TrustedServers $trustedServer */
		$trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
			->setConstructorArgs(
				[
					$this->dbHandler,
					$this->httpClientService,
					$this->logger,
					$this->jobList,
					$this->secureRandom,
					$this->config
				]
			)
			->setMethods(['checkOwnCloudVersion'])
			->getMock();

		$this->httpClientService->expects($this->once())->method('newClient')
			->willReturn($this->httpClient);

		$this->httpClient->expects($this->once())->method('get')->with($server . '/status.php')
			->willReturn($this->response);

		$this->response->expects($this->once())->method('getStatusCode')
			->willReturn($statusCode);

		if ($statusCode === 200) {
			$trustedServers->expects($this->once())->method('checkOwnCloudVersion')
				->willReturn($isValidOwnCloudVersion);
		} else {
			$trustedServers->expects($this->never())->method('checkOwnCloudVersion');
		}

		$this->assertSame($expected,
			$trustedServers->isOwnCloudServer($server)
		);

	}

	public function dataTestIsOwnCloudServer() {
		return [
			[200, true, true],
			[200, false, false],
			[404, true, false],
		];
	}

	public function testIsOwnCloudServerFail() {
		$server = 'server1';

		$this->httpClientService->expects($this->once())->method('newClient')
			->willReturn($this->httpClient);

		$this->logger->expects($this->once())->method('error')
			->with('simulated exception', ['app' => 'federation']);

		$this->httpClient->expects($this->once())->method('get')->with($server . '/status.php')
			->willReturnCallback(function () {
				throw new \Exception('simulated exception');
			});

		$this->assertFalse($this->trustedServers->isOwnCloudServer($server));

	}

	/**
	 * @dataProvider dataTestCheckOwnCloudVersion
	 *
	 * @param $statusphp
	 * @param $expected
	 */
	public function testCheckOwnCloudVersion($statusphp, $expected) {
		$this->assertSame($expected,
			$this->invokePrivate($this->trustedServers, 'checkOwnCloudVersion', [$statusphp])
		);
	}

	public function dataTestCheckOwnCloudVersion() {
		return [
			['{"version":"8.4.0"}', false],
			['{"version":"9.0.0"}', true],
			['{"version":"9.1.0"}', true]
		];
	}

	/**
	 * @dataProvider dataTestUpdateProtocol
	 * @param string $url
	 * @param string $expected
	 */
	public function testUpdateProtocol($url, $expected) {
		$this->assertSame($expected,
			$this->invokePrivate($this->trustedServers, 'updateProtocol', [$url])
		);
	}

	public function dataTestUpdateProtocol() {
		return [
			['http://owncloud.org', 'http://owncloud.org'],
			['https://owncloud.org', 'https://owncloud.org'],
			['owncloud.org', 'https://owncloud.org'],
			['httpserver', 'https://httpserver'],
		];
	}
}
