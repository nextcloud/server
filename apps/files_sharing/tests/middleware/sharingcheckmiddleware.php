<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Files\NotFoundException;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCP\AppFramework\Http\JSONResponse;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class SharingCheckMiddlewareTest extends \Test\TestCase {

	/** @var \OCP\IConfig */
	private $config;
	/** @var \OCP\App\IAppManager */
	private $appManager;
	/** @var SharingCheckMiddleware */
	private $sharingCheckMiddleware;
	/** @var \OCP\AppFramework\Controller */
	private $controllerMock;
	/** @var IControllerMethodReflector */
	private $reflector;

	protected function setUp() {
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->appManager = $this->getMockBuilder('\OCP\App\IAppManager')
			->disableOriginalConstructor()->getMock();
		$this->controllerMock = $this->getMockBuilder('\OCP\AppFramework\Controller')
			->disableOriginalConstructor()->getMock();
		$this->reflector = $this->getMockBuilder('\OCP\AppFramework\Utility\IControllerMethodReflector')
			->disableOriginalConstructor()->getMock();

		$this->sharingCheckMiddleware = new SharingCheckMiddleware(
			'files_sharing',
			$this->config,
			$this->appManager,
			$this->reflector);
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

		$controller = $this->getMockBuilder('\OCA\Files_Sharing\Controllers\ExternalSharesController')
			->disableOriginalConstructor()->getMock();

		$exceptionThrown = false;

		try {
			$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
		} catch (\OCA\Files_Sharing\Exceptions\S2SException $exception) {
			$exceptionThrown = true;
		}

		$this->assertNotEquals($noException, $exceptionThrown);
	}

	public function testBeforeControllerWithShareControllerWithSharingEnabled() {
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

		$controller = $this->getMockBuilder('\OCA\Files_Sharing\Controllers\ShareController')
			->disableOriginalConstructor()->getMock();

		$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 * @expectedExceptionMessage Link sharing is disabled
	 */
	public function testBeforeControllerWithShareControllerWithSharingEnabledAPIDisabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$controller = $this->getMockBuilder('\OCA\Files_Sharing\Controllers\ShareController')
			->disableOriginalConstructor()->getMock();

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
