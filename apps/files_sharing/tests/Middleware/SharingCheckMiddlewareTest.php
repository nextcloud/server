<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Middleware;
use OCA\Files_Sharing\Controller\ExternalSharesController;
use OCA\Files_Sharing\Controller\ShareController;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Files\NotFoundException;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class SharingCheckMiddlewareTest extends \Test\TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;
	/** @var SharingCheckMiddleware */
	private $sharingCheckMiddleware;
	/** @var Controller|\PHPUnit_Framework_MockObject_MockObject */
	private $controllerMock;
	/** @var IControllerMethodReflector|\PHPUnit_Framework_MockObject_MockObject */
	private $reflector;
	/** @var  IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;
	/** @var  IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->controllerMock = $this->createMock(Controller::class);
		$this->reflector = $this->createMock(IControllerMethodReflector::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->request = $this->createMock(IRequest::class);

		$this->sharingCheckMiddleware = new SharingCheckMiddleware(
			'files_sharing',
			$this->config,
			$this->appManager,
			$this->reflector,
			$this->shareManager,
			$this->request);
	}

	public function testIsSharingEnabledWithAppEnabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->assertTrue(self::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsSharingEnabledWithAppDisabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(false));

		$this->assertFalse(self::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsLinkSharingEnabledWithEverythinEnabled() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'shareapi_enabled', 'yes')
			->will($this->returnValue('yes'));

		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('yes'));

		$this->assertTrue(self::invokePrivate($this->sharingCheckMiddleware, 'isLinkSharingEnabled'));
	}


	public function testIsLinkSharingEnabledWithLinkSharingDisabled() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'shareapi_enabled', 'yes')
			->will($this->returnValue('yes'));

		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('no'));

		$this->assertFalse(self::invokePrivate($this->sharingCheckMiddleware, 'isLinkSharingEnabled'));
	}

	public function testIsLinkSharingEnabledWithSharingAPIDisabled() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_enabled', 'yes')
			->will($this->returnValue('no'));

		$this->assertFalse(self::invokePrivate($this->sharingCheckMiddleware, 'isLinkSharingEnabled'));
	}

	public function externalSharesChecksDataProvider() {

		$data = [];

		foreach ([false, true] as $annIn) {
			foreach ([false, true] as $annOut) {
				foreach ([false, true] as $confIn) {
					foreach ([false, true] as $confOut) {

						$res = true;
						if (!$annIn && !$confIn) {
							$res = false;
						} elseif (!$annOut && !$confOut) {
							$res = false;
						}

						$d = [
							[
								['NoIncomingFederatedSharingRequired', $annIn],
								['NoOutgoingFederatedSharingRequired', $annOut],
							],
							[
								['files_sharing', 'incoming_server2server_share_enabled', 'yes', $confIn ? 'yes' : 'no'],
								['files_sharing', 'outgoing_server2server_share_enabled', 'yes', $confOut ? 'yes' : 'no'],
							],
							$res
						];

						$data[] = $d;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @dataProvider externalSharesChecksDataProvider
	 */
	public function testExternalSharesChecks($annotations, $config, $expectedResult) {
		$this->reflector
			->expects($this->atLeastOnce())
			->method('hasAnnotation')
			->will($this->returnValueMap($annotations));

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap($config));

		$this->assertEquals($expectedResult, self::invokePrivate($this->sharingCheckMiddleware, 'externalSharesChecks'));
	}

	/**
	 * @dataProvider externalSharesChecksDataProvider
	 */
	public function testBeforeControllerWithExternalShareControllerWithSharingEnabled($annotations, $config, $noException) {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->reflector
			->expects($this->atLeastOnce())
			->method('hasAnnotation')
			->will($this->returnValueMap($annotations));

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap($config));

		$controller = $this->createMock(ExternalSharesController::class);

		$exceptionThrown = false;

		try {
			$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
		} catch (S2SException $exception) {
			$exceptionThrown = true;
		}

		$this->assertNotEquals($noException, $exceptionThrown);
	}

	public function testBeforeControllerWithShareControllerWithSharingEnabled() {

		$share = $this->createMock(IShare::class);

		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'shareapi_enabled', 'yes')
			->will($this->returnValue('yes'));

		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('yes'));

		$this->request->expects($this->once())->method('getParam')->with('token')
			->willReturn('token');
		$this->shareManager->expects($this->once())->method('getShareByToken')
			->with('token')->willReturn($share);

		$share->expects($this->once())->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);

		$controller = $this->createMock(ShareController::class);

		$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 * @expectedExceptionMessage Link sharing is disabled
	 */
	public function testBeforeControllerWithShareControllerWithSharingEnabledAPIDisabled() {

		$share = $this->createMock(IShare::class);

		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$controller = $this->createMock(ShareController::class);

		$this->request->expects($this->once())->method('getParam')->with('token')
			->willReturn('token');
		$this->shareManager->expects($this->once())->method('getShareByToken')
			->with('token')->willReturn($share);

		$share->expects($this->once())->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);


		$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 * @expectedExceptionMessage Sharing is disabled.
	 */
	public function testBeforeControllerWithSharingDisabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(false));

		$this->sharingCheckMiddleware->beforeController($this->controllerMock, 'myMethod');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage My Exception message
	 */
	public function testAfterExceptionWithRegularException() {
		$this->sharingCheckMiddleware->afterException($this->controllerMock, 'myMethod', new \Exception('My Exception message'));
	}

	public function testAfterExceptionWithNotFoundException() {
		$this->assertEquals(new NotFoundResponse(), $this->sharingCheckMiddleware->afterException($this->controllerMock, 'myMethod', new NotFoundException('My Exception message')));
	}

	public function testAfterExceptionWithS2SException() {
		$this->assertEquals(new JSONResponse('My Exception message', 405), $this->sharingCheckMiddleware->afterException($this->controllerMock, 'myMethod', new S2SException('My Exception message')));
	}


}
