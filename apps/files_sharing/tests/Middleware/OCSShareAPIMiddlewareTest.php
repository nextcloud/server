<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\Middleware;

use OCA\Files_Sharing\Controller\ShareAPIController;
use OCA\Files_Sharing\Middleware\OCSShareAPIMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class OCSShareAPIMiddlewareTest extends \Test\TestCase {
	private IManager&MockObject $shareManager;
	private IL10N&MockObject $l;
	private OCSShareAPIMiddleware $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->l = $this->createMock(IL10N::class);

		$this->l->method('t')->willReturnArgument(0);

		$this->middleware = new OCSShareAPIMiddleware($this->shareManager, $this->l);
	}

	public static function dataBeforeController(): array {
		return [
			[
				Controller::class,
				false,
				false
			],
			[
				Controller::class,
				true,
				false
			],
			[
				OCSController::class,
				false,
				false
			],
			[
				OCSController::class,
				true,
				false
			],
			[
				ShareAPIController::class,
				false,
				true
			],
			[
				ShareAPIController::class,
				true,
				false
			],
		];
	}

	/**
	 * @dataProvider dataBeforeController
	 */
	public function testBeforeController(string $controllerClass, bool $enabled, bool $exception): void {
		$this->shareManager->method('shareApiEnabled')->willReturn($enabled);
		$controller = $this->createMock($controllerClass);

		try {
			$this->middleware->beforeController($controller, 'foo');
			$this->assertFalse($exception);
		} catch (OCSNotFoundException $e) {
			$this->assertTrue($exception);
		}
	}

	public static function dataAfterController(): array {
		return [
			[Controller::class],
			[OCSController::class],
			[ShareAPIController::class],
		];
	}

	/**
	 * @dataProvider dataAfterController
	 */
	public function testAfterController(string $controllerClass): void {
		$controller = $this->createMock($controllerClass);
		if ($controller instanceof ShareAPIController) {
			$controller->expects($this->once())->method('cleanup');
		}

		$response = $this->createMock(Response::class);
		$this->middleware->afterController($controller, 'foo', $response);
		$this->addToAssertionCount(1);
	}
}
