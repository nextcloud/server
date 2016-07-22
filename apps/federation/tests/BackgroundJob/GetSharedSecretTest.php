<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\Federation\Tests\BackgroundJob;


use OCA\Federation\BackgroundJob\GetSharedSecret;
use OCA\Files_Sharing\Tests\TestCase;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use OCP\ILogger;
use OCP\IURLGenerator;

/**
 * Class GetSharedSecretTest
 *
 * @group DB
 *
 * @package OCA\Federation\Tests\BackgroundJob
 */
class GetSharedSecretTest extends TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject | IClient */
	private $httpClient;

	/** @var \PHPUnit_Framework_MockObject_MockObject | IJobList */
	private $jobList;

	/** @var \PHPUnit_Framework_MockObject_MockObject | IURLGenerator */
	private $urlGenerator;

	/** @var \PHPUnit_Framework_MockObject_MockObject | TrustedServers  */
	private $trustedServers;

	/** @var \PHPUnit_Framework_MockObject_MockObject | DbHandler */
	private $dbHandler;

	/** @var \PHPUnit_Framework_MockObject_MockObject | ILogger */
	private $logger;

	/** @var \PHPUnit_Framework_MockObject_MockObject | IResponse */
	private $response;

	/** @var GetSharedSecret */
	private $getSharedSecret;

	public function setUp() {
		parent::setUp();

		$this->httpClient = $this->getMock('OCP\Http\Client\IClient');
		$this->jobList = $this->getMock('OCP\BackgroundJob\IJobList');
		$this->urlGenerator = $this->getMock('OCP\IURLGenerator');
		$this->trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
			->disableOriginalConstructor()->getMock();
		$this->dbHandler = $this->getMockBuilder('OCA\Federation\DbHandler')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMock('OCP\ILogger');
		$this->response = $this->getMock('OCP\Http\Client\IResponse');

		$this->getSharedSecret = new GetSharedSecret(
			$this->httpClient,
			$this->urlGenerator,
			$this->jobList,
			$this->trustedServers,
			$this->logger,
			$this->dbHandler
		);
	}

	/**
	 * @dataProvider dataTestExecute
	 *
	 * @param bool $isTrustedServer
	 * @param bool $retainBackgroundJob
	 */
	public function testExecute($isTrustedServer, $retainBackgroundJob) {
		/** @var GetSharedSecret |\PHPUnit_Framework_MockObject_MockObject $getSharedSecret */
		$getSharedSecret = $this->getMockBuilder('OCA\Federation\BackgroundJob\GetSharedSecret')
			->setConstructorArgs(
				[
					$this->httpClient,
					$this->urlGenerator,
					$this->jobList,
					$this->trustedServers,
					$this->logger,
					$this->dbHandler
				]
			)->setMethods(['parentExecute'])->getMock();
		$this->invokePrivate($getSharedSecret, 'argument', [['url' => 'url']]);

		$this->trustedServers->expects($this->once())->method('isTrustedServer')
			->with('url')->willReturn($isTrustedServer);
		if ($isTrustedServer) {
			$getSharedSecret->expects($this->once())->method('parentExecute');
		} else {
			$getSharedSecret->expects($this->never())->method('parentExecute');
		}
		$this->invokePrivate($getSharedSecret, 'retainJob', [$retainBackgroundJob]);
		if ($retainBackgroundJob) {
			$this->jobList->expects($this->never())->method('remove');
		} else {
			$this->jobList->expects($this->once())->method('remove');
		}

		$getSharedSecret->execute($this->jobList);

	}

	public function dataTestExecute() {
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
	public function testRun($statusCode) {

		$target = 'targetURL';
		$source = 'sourceURL';
		$token = 'token';

		$argument = ['url' => $target, 'token' => $token];

		$this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/')
			->willReturn($source);
		$this->httpClient->expects($this->once())->method('get')
			->with(
				$target . '/ocs/v2.php/apps/federation/api/v1/shared-secret?format=json',
				[
					'query' =>
						[
							'url' => $source,
							'token' => $token
						],
					'timeout' => 3,
					'connect_timeout' => 3,
				]
			)->willReturn($this->response);

		$this->response->expects($this->once())->method('getStatusCode')
			->willReturn($statusCode);

		if (
			$statusCode !== Http::STATUS_OK
			&& $statusCode !== Http::STATUS_FORBIDDEN
		) {
			$this->dbHandler->expects($this->never())->method('addToken');
		}  else {
			$this->dbHandler->expects($this->once())->method('addToken')->with($target, '');
		}

		if ($statusCode === Http::STATUS_OK) {
			$this->response->expects($this->once())->method('getBody')
				->willReturn('{"ocs":{"data":{"sharedSecret":"secret"}}}');
			$this->trustedServers->expects($this->once())->method('addSharedSecret')
				->with($target, 'secret');
		} else {
			$this->trustedServers->expects($this->never())->method('addSharedSecret');
		}

		$this->invokePrivate($this->getSharedSecret, 'run', [$argument]);
		if (
			$statusCode !== Http::STATUS_OK
			&& $statusCode !== Http::STATUS_FORBIDDEN
		) {
			$this->assertTrue($this->invokePrivate($this->getSharedSecret, 'retainJob'));
		} else {
			$this->assertFalse($this->invokePrivate($this->getSharedSecret, 'retainJob'));
		}

	}

	public function dataTestRun() {
		return [
			[Http::STATUS_OK],
			[Http::STATUS_FORBIDDEN],
			[Http::STATUS_CONFLICT],
		];
	}

}
