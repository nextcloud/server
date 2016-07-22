<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Robin Appelman <robin@icewind.nl>
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


namespace OCA\Federation\Tests\API;


use OC\BackgroundJob\JobList;
use OCA\Federation\API\OCSAuthAPI;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Http;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class OCSAuthAPITest extends TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject | IRequest */
	private $request;

	/** @var \PHPUnit_Framework_MockObject_MockObject | ISecureRandom  */
	private $secureRandom;

	/** @var \PHPUnit_Framework_MockObject_MockObject | JobList */
	private $jobList;

	/** @var \PHPUnit_Framework_MockObject_MockObject | TrustedServers */
	private $trustedServers;

	/** @var \PHPUnit_Framework_MockObject_MockObject | DbHandler */
	private $dbHandler;

	/** @var \PHPUnit_Framework_MockObject_MockObject | ILogger */
	private $logger;

	/** @var  OCSAuthApi */
	private $ocsAuthApi;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMock('OCP\IRequest');
		$this->secureRandom = $this->getMock('OCP\Security\ISecureRandom');
		$this->trustedServers = $this->getMockBuilder('OCA\Federation\TrustedServers')
			->disableOriginalConstructor()->getMock();
		$this->dbHandler = $this->getMockBuilder('OCA\Federation\DbHandler')
			->disableOriginalConstructor()->getMock();
		$this->jobList = $this->getMockBuilder('OC\BackgroundJob\JobList')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()->getMock();

		$this->ocsAuthApi = new OCSAuthAPI(
			$this->request,
			$this->secureRandom,
			$this->jobList,
			$this->trustedServers,
			$this->dbHandler,
			$this->logger
		);

	}

	/**
	 * @dataProvider dataTestRequestSharedSecret
	 *
	 * @param string $token
	 * @param string $localToken
	 * @param bool $isTrustedServer
	 * @param int $expected
	 */
	public function testRequestSharedSecret($token, $localToken, $isTrustedServer, $expected) {

		$url = 'url';

		$this->request->expects($this->at(0))->method('getParam')->with('url')->willReturn($url);
		$this->request->expects($this->at(1))->method('getParam')->with('token')->willReturn($token);
		$this->trustedServers
			->expects($this->once())
			->method('isTrustedServer')->with($url)->willReturn($isTrustedServer);
		$this->dbHandler->expects($this->any())
			->method('getToken')->with($url)->willReturn($localToken);

		if ($expected === Http::STATUS_OK) {
			$this->jobList->expects($this->once())->method('add')
				->with('OCA\Federation\BackgroundJob\GetSharedSecret', ['url' => $url, 'token' => $token]);
			$this->jobList->expects($this->once())->method('remove')
				->with('OCA\Federation\BackgroundJob\RequestSharedSecret', ['url' => $url, 'token' => $localToken]);
		} else {
			$this->jobList->expects($this->never())->method('add');
			$this->jobList->expects($this->never())->method('remove');
		}

		$result = $this->ocsAuthApi->requestSharedSecret();
		$this->assertSame($expected, $result->getStatusCode());
	}

	public function dataTestRequestSharedSecret() {
		return [
			['token2', 'token1', true, Http::STATUS_OK],
			['token1', 'token2', false, Http::STATUS_FORBIDDEN],
			['token1', 'token2', true, Http::STATUS_FORBIDDEN],
		];
	}

	/**
	 * @dataProvider dataTestGetSharedSecret
	 *
	 * @param bool $isTrustedServer
	 * @param bool $isValidToken
	 * @param int $expected
	 */
	public function testGetSharedSecret($isTrustedServer, $isValidToken, $expected) {

		$url = 'url';
		$token = 'token';

		$this->request->expects($this->at(0))->method('getParam')->with('url')->willReturn($url);
		$this->request->expects($this->at(1))->method('getParam')->with('token')->willReturn($token);

		/** @var OCSAuthAPI | \PHPUnit_Framework_MockObject_MockObject $ocsAuthApi */
		$ocsAuthApi = $this->getMockBuilder('OCA\Federation\API\OCSAuthAPI')
			->setConstructorArgs(
				[
					$this->request,
					$this->secureRandom,
					$this->jobList,
					$this->trustedServers,
					$this->dbHandler,
					$this->logger
				]
			)->setMethods(['isValidToken'])->getMock();

		$this->trustedServers
			->expects($this->any())
			->method('isTrustedServer')->with($url)->willReturn($isTrustedServer);
		$ocsAuthApi->expects($this->any())
			->method('isValidToken')->with($url, $token)->willReturn($isValidToken);

		if($expected === Http::STATUS_OK) {
			$this->secureRandom->expects($this->once())->method('generate')->with(32)
				->willReturn('secret');
			$this->trustedServers->expects($this->once())
				->method('addSharedSecret')->willReturn($url, 'secret');
			$this->dbHandler->expects($this->once())
				->method('addToken')->with($url, '');
		} else {
			$this->secureRandom->expects($this->never())->method('getMediumStrengthGenerator');
			$this->secureRandom->expects($this->never())->method('generate');
			$this->trustedServers->expects($this->never())->method('addSharedSecret');
			$this->dbHandler->expects($this->never())->method('addToken');
		}

		$result = $ocsAuthApi->getSharedSecret();

		$this->assertSame($expected, $result->getStatusCode());

		if ($expected === Http::STATUS_OK) {
			$data =  $result->getData();
			$this->assertSame('secret', $data['sharedSecret']);
		}
	}

	public function dataTestGetSharedSecret() {
		return [
			[true, true, Http::STATUS_OK],
			[false, true, Http::STATUS_FORBIDDEN],
			[true, false, Http::STATUS_FORBIDDEN],
			[false, false, Http::STATUS_FORBIDDEN],
		];
	}

}
