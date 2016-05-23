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


namespace OCA\FederatedFileSharing\Tests\Controller;

use OC\HintException;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\Controller\SaveToOwnCloudController;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\Share;
use OCP\Share\IManager;
use OCP\Share\IShare;

class SaveToOwnCloudControllerTest extends \Test\TestCase {

	/** @var  SaveToOwnCloudController */
	private $controller;

	/** @var  \OCP\IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var  FederatedShareProvider | \PHPUnit_Framework_MockObject_MockObject */
	private $federatedShareProvider;

	/** @var  IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;

	/** @var  AddressHandler | \PHPUnit_Framework_MockObject_MockObject */
	private $addressHandler;

	/** @var  IRootFolder | \PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	/** @var  IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var  IShare */
	private $share;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMock('OCP\IRequest');
		$this->federatedShareProvider = $this->getMockBuilder('OCA\FederatedFileSharing\FederatedShareProvider')
			->disableOriginalConstructor()->getMock();
		$this->shareManager = $this->getMock('OCP\Share\IManager');
		$this->addressHandler = $this->getMockBuilder('OCA\FederatedFileSharing\AddressHandler')
			->disableOriginalConstructor()->getMock();
		$this->rootFolder = $this->getMock('OCP\Files\IRootFolder');
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->share = new \OC\Share20\Share($this->rootFolder, $this->userManager);

		$this->controller = new SaveToOwnCloudController(
			'federatedfilesharing', $this->request,
			$this->federatedShareProvider,
			$this->shareManager,
			$this->addressHandler
		);
	}

	/**
	 * @dataProvider dataTestSaveToOwnCloud
	 *
	 * @param string $shareWith
	 * @param bool $validShareWith
	 * @param string $token
	 * @param bool $validToken
	 * @param bool $createSuccessful
	 * @param string $expectedReturnData
	 */
	public function testSaveToOwnCloud($shareWith, $validShareWith, $token, $validToken, $createSuccessful, $expectedReturnData) {
		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->with($shareWith)
			->willReturnCallback(
				function($shareWith) use ($validShareWith, $expectedReturnData) {
					if ($validShareWith) {
						return ['user', 'server'];
					}
					throw new HintException($expectedReturnData, $expectedReturnData);
				}
			);
		
		$share = $this->share;
		
		$this->shareManager->expects($this->any())->method('getShareByToken')
			->with($token)
			->willReturnCallback(
				function ($token) use ($validToken, $share, $expectedReturnData) {
					if ($validToken) {
						return $share;
					}
					throw new HintException($expectedReturnData, $expectedReturnData);
				}
			);
		
		$this->federatedShareProvider->expects($this->any())->method('create')
			->with($share)
			->willReturnCallback(
				function (IShare $share) use ($createSuccessful, $shareWith, $expectedReturnData) {
					$this->assertEquals($shareWith, $share->getSharedWith());
					if ($createSuccessful) {
						return $share;
					}
					throw new HintException($expectedReturnData, $expectedReturnData);
				}
			);

		$result = $this->controller->saveToOwnCloud($shareWith, $token);

		$errorCase = !$validShareWith || !$validToken || !$createSuccessful;

		if ($errorCase) {
			$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
			$this->assertTrue(isset($result->getData()['message']));
			$this->assertSame($expectedReturnData, $result->getData()['message']);
		} else {
			$this->assertSame(Http::STATUS_OK, $result->getStatus());
			$this->assertTrue(isset($result->getData()['remoteUrl']));
			$this->assertSame($expectedReturnData, $result->getData()['remoteUrl']);

		}
		
	}

	public function dataTestSaveToOwnCloud() {
		return [
			//shareWith, validShareWith, token, validToken, createSuccessful, expectedReturnData
			['user@server', true, 'token', true, true, 'server'],
			['user@server', false, 'token', true, true, 'invalid federated cloud id'],
			['user@server', false, 'token', false, true, 'invalid federated cloud id'],
			['user@server', false, 'token', false, false, 'invalid federated cloud id'],
			['user@server', false, 'token', true, false, 'invalid federated cloud id'],
			['user@server', true, 'token', false, true, 'invalid token'],
			['user@server', true, 'token', false, false, 'invalid token'],
			['user@server', true, 'token', true, false, 'can not create share']
		];
	}

}
