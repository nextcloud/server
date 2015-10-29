<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IDBConnection;
use OCP\ILogger;
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

	public function setUp() {
		parent::setUp();

		$this->dbHandler = $this->getMockBuilder('\OCA\Federation\DbHandler')
			->disableOriginalConstructor()->getMock();
		$this->httpClientService = $this->getMock('OCP\Http\Client\IClientService');
		$this->httpClient = $this->getMock('OCP\Http\Client\IClient');
		$this->response = $this->getMock('OCP\Http\Client\IResponse');
		$this->logger = $this->getMock('OCP\ILogger');

		$this->trustedServers = new TrustedServers(
			$this->dbHandler,
			$this->httpClientService,
			$this->logger
		);

	}

	public function testAddServer() {
		/** @var \PHPUnit_Framework_MockObject_MockObject | TrustedServers $trustedServer */
		$trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
			->setConstructorArgs(
				[
					$this->dbHandler,
					$this->httpClientService,
					$this->logger
				]
			)
			->setMethods(['normalizeUrl'])
			->getMock();
		$trustedServers->expects($this->once())->method('normalizeUrl')
			->with('url')->willReturn('normalized');
		$this->dbHandler->expects($this->once())->method('add')->with('normalized')
		->willReturn(true);

		$this->assertTrue(
			$trustedServers->addServer('url')
		);
	}

	public function testRemoveServer() {
		$id = 42;
		$this->dbHandler->expects($this->once())->method('remove')->with($id);
		$this->trustedServers->removeServer($id);
	}

	public function testGetServers() {
		$this->dbHandler->expects($this->once())->method('getAll')->willReturn(true);

		$this->assertTrue(
			$this->trustedServers->getServers()
		);
	}


	public function testIsTrustedServer() {
		/** @var \PHPUnit_Framework_MockObject_MockObject | TrustedServers $trustedServer */
		$trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
			->setConstructorArgs(
				[
					$this->dbHandler,
					$this->httpClientService,
					$this->logger
				]
			)
			->setMethods(['normalizeUrl'])
			->getMock();
		$trustedServers->expects($this->once())->method('normalizeUrl')
			->with('url')->willReturn('normalized');
		$this->dbHandler->expects($this->once())->method('exists')->with('normalized')
		->willReturn(true);

		$this->assertTrue(
			$trustedServers->isTrustedServer('url')
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
					$this->logger
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
			->willReturnCallback(function() {
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
	 * @dataProvider dataTestNormalizeUrl
	 *
	 * @param string $url
	 * @param string $expected
	 */
	public function testNormalizeUrl($url, $expected) {
		$this->assertSame($expected,
			$this->invokePrivate($this->trustedServers, 'normalizeUrl', [$url])
		);
	}

	public function dataTestNormalizeUrl() {
		return [
			['owncloud.org', 'owncloud.org'],
			['http://owncloud.org', 'owncloud.org'],
			['https://owncloud.org', 'owncloud.org'],
			['https://owncloud.org//mycloud', 'owncloud.org/mycloud'],
			['https://owncloud.org/mycloud/', 'owncloud.org/mycloud'],
		];
	}
}
