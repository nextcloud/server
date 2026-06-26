<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Middleware;

use OCA\Files_Sharing\Controller\ExternalSharesController;
use OCA\Files_Sharing\Controller\ShareController;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class SharingCheckMiddlewareTest extends \Test\TestCase {

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var SharingCheckMiddleware */
	private $sharingCheckMiddleware;
	/** @var Controller|\PHPUnit\Framework\MockObject\MockObject */
	private $controllerMock;
	/** @var IControllerMethodReflector|\PHPUnit\Framework\MockObject\MockObject */
	private $reflector;
	/** @var IManager | \PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;
	/** @var IRequest | \PHPUnit\Framework\MockObject\MockObject */
	private $request;

	protected function setUp(): void {
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

	public function testIsSharingEnabledWithAppEnabled(): void {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->willReturn(true);

		$this->assertTrue(self::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsSharingEnabledWithAppDisabled(): void {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->willReturn(false);

		$this->assertFalse(self::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public static function externalSharesChecksDataProvider() {
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'externalSharesChecksDataProvider')]
	public function testExternalSharesChecks($annotations, $config, $expectedResult): void {
		$this->reflector
			->expects($this->atLeastOnce())
			->method('hasAnnotation')
			->willReturnMap($annotations);

		$this->config
			->method('getAppValue')
			->willReturnMap($config);

		$this->assertEquals($expectedResult, self::invokePrivate($this->sharingCheckMiddleware, 'externalSharesChecks'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'externalSharesChecksDataProvider')]
	public function testBeforeControllerWithExternalShareControllerWithSharingEnabled($annotations, $config, $noException): void {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->willReturn(true);

		$this->reflector
			->expects($this->atLeastOnce())
			->method('hasAnnotation')
			->willReturnMap($annotations);

		$this->config
			->method('getAppValue')
			->willReturnMap($config);

		$controller = $this->createMock(ExternalSharesController::class);

		$exceptionThrown = false;

		try {
			$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
		} catch (S2SException $exception) {
			$exceptionThrown = true;
		}

		$this->assertNotEquals($noException, $exceptionThrown);
	}

	public function testBeforeControllerWithShareControllerWithSharingEnabled(): void {
		$share = $this->createMock(IShare::class);

		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->willReturn(true);

		$controller = $this->createMock(ShareController::class);

		$this->sharingCheckMiddleware->beforeController($controller, 'myMethod');
	}


	public function testBeforeControllerWithSharingDisabled(): void {
		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Sharing is disabled.');

		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->willReturn(false);

		$this->sharingCheckMiddleware->beforeController($this->controllerMock, 'myMethod');
	}


	public function testAfterExceptionWithRegularException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('My Exception message');

		$this->sharingCheckMiddleware->afterException($this->controllerMock, 'myMethod', new \Exception('My Exception message'));
	}

	public function testAfterExceptionWithNotFoundException(): void {
		$this->assertEquals(new NotFoundResponse(), $this->sharingCheckMiddleware->afterException($this->controllerMock, 'myMethod', new NotFoundException('My Exception message')));
	}

	public function testAfterExceptionWithS2SException(): void {
		$this->assertEquals(new JSONResponse('My Exception message', 405), $this->sharingCheckMiddleware->afterException($this->controllerMock, 'myMethod', new S2SException('My Exception message')));
	}
}
