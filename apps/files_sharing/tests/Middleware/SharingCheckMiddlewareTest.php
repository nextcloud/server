<?php

declare(strict_types=1);
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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class SharingCheckMiddlewareTest extends \Test\TestCase {
	private IConfig&MockObject $config;
	private IAppManager&MockObject $appManager;
	private SharingCheckMiddleware $sharingCheckMiddleware;
	private Controller&MockObject $controllerMock;
	private IControllerMethodReflector&MockObject $reflector;
	private IManager&MockObject $shareManager;
	private IRequest&MockObject $request;

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

	public static function externalSharesChecksDataProvider(): array {
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
	public function testExternalSharesChecks(array $annotations, array $config, bool $expectedResult): void {
		$this->reflector
			->expects($this->atLeastOnce())
			->method('hasAnnotation')
			->willReturnMap($annotations);

		$this->config
			->method('getAppValue')
			->willReturnMap($config);

		$this->assertEquals($expectedResult, self::invokePrivate($this->sharingCheckMiddleware, 'externalSharesChecks'));
	}

	/**
	 * @dataProvider externalSharesChecksDataProvider
	 */
	public function testBeforeControllerWithExternalShareControllerWithSharingEnabled(array $annotations, array $config, bool $noException): void {
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
