<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
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


namespace OCA\FederatedFileSharing\Tests;


use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\DiscoveryManager;
use OCA\FederatedFileSharing\Notifications;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClientService;

class NotificationsTest extends \Test\TestCase {

	/** @var  AddressHandler | \PHPUnit_Framework_MockObject_MockObject */
	private $addressHandler;

	/** @var  IClientService | \PHPUnit_Framework_MockObject_MockObject*/
	private $httpClientService;

	/** @var  DiscoveryManager | \PHPUnit_Framework_MockObject_MockObject */
	private $discoveryManager;

	/** @var  IJobList | \PHPUnit_Framework_MockObject_MockObject */
	private $jobList;

	public function setUp() {
		parent::setUp();

		$this->jobList = $this->getMock('OCP\BackgroundJob\IJobList');
		$this->discoveryManager = $this->getMockBuilder('OCA\FederatedFileSharing\DiscoveryManager')
			->disableOriginalConstructor()->getMock();
		$this->httpClientService = $this->getMock('OCP\Http\Client\IClientService');
		$this->addressHandler = $this->getMockBuilder('OCA\FederatedFileSharing\AddressHandler')
			->disableOriginalConstructor()->getMock();

	}

	/**
	 * get instance of Notifications class
	 * 
	 * @param array $mockedMethods methods which should be mocked
	 * @return Notifications | \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getInstance(array $mockedMethods = []) {
		if (empty($mockedMethods)) {
			$instance = new Notifications(
				$this->addressHandler,
				$this->httpClientService,
				$this->discoveryManager,
				$this->jobList
			);
		} else {
			$instance = $this->getMockBuilder('OCA\FederatedFileSharing\Notifications')
				->setConstructorArgs(
					[
						$this->addressHandler,
						$this->httpClientService,
						$this->discoveryManager,
						$this->jobList
					]
				)->setMethods($mockedMethods)->getMock();
		}
		
		return $instance;
	}


	/**
	 * @dataProvider dataTestSendUpdateToRemote
	 *
	 * @param int $try
	 * @param array $httpRequestResult
	 * @param bool $expected
	 */
	public function testSendUpdateToRemote($try, $httpRequestResult, $expected) {
		$remote = 'remote';
		$id = 42;
		$timestamp = 63576;
		$token = 'token';
		$instance = $this->getInstance(['tryHttpPostToShareEndpoint', 'getTimestamp']);

		$instance->expects($this->any())->method('getTimestamp')->willReturn($timestamp);

		$instance->expects($this->once())->method('tryHttpPostToShareEndpoint')
			->with($remote, '/'.$id.'/unshare', ['token' => $token, 'data1Key' => 'data1Value'])
			->willReturn($httpRequestResult);

		$this->addressHandler->expects($this->once())->method('removeProtocolFromUrl')
			->with($remote)->willReturn($remote);

		// only add background job on first try
		if ($try === 0 && $expected === false) {
			$this->jobList->expects($this->once())->method('add')
				->with(
					'OCA\FederatedFileSharing\BackgroundJob\RetryJob',
					[
						'remote' => $remote,
						'remoteId' => $id,
						'action' => 'unshare',
						'data' => json_encode(['data1Key' => 'data1Value']),
						'token' => $token,
						'try' => $try,
						'lastRun' => $timestamp
					]
				);
		} else {
			$this->jobList->expects($this->never())->method('add');
		}

		$this->assertSame($expected,
			$instance->sendUpdateToRemote($remote, $id, $token, 'unshare', ['data1Key' => 'data1Value'], $try)
		);

	}


	public function dataTestSendUpdateToRemote() {
		return [
			// test if background job is added correctly
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], true],
			[1, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], true],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], false],
			[1, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], false],
			// test all combinations of 'statuscode' and 'success'
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], true],
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])], true],
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 400]]])], false],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], false],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])], false],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 400]]])], false],
		];
	}

}
