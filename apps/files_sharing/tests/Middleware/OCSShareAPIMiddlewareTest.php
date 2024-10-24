<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\Middleware;

use OCA\Files_Sharing\Controller\ShareAPIController;
use OCA\Files_Sharing\Middleware\OCSShareAPIMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\Share\IManager;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class OCSShareAPIMiddlewareTest extends \Test\TestCase {

	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;
	/** @var IL10N */
	private $l;
	/** @var OCSShareAPIMiddleware */
	private $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->l = $this->createMock(IL10N::class);

		$this->l->method('t')->willReturnArgument(0);

		$this->middleware = new OCSShareAPIMiddleware($this->shareManager, $this->l);
	}

	public function dataBeforeController() {
		return [
			[
				$this->createMock(Controller::class),
				false,
				false
			],
			[
				$this->createMock(Controller::class),
				true,
				false
			],
			[
				$this->createMock(OCSController::class),
				false,
				false
			],
			[
				$this->createMock(OCSController::class),
				true,
				false
			],
			[
				$this->createMock(ShareAPIController::class),
				false,
				true
			],
			[
				$this->createMock(ShareAPIController::class),
				true,
				false
			],
		];
	}

	/**
	 * @dataProvider dataBeforeController
	 *
	 * @param Controller $controller
	 * @param bool $enabled
	 * @param bool $exception
	 */
	public function testBeforeController(Controller $controller, $enabled, $exception): void {
		$this->shareManager->method('shareApiEnabled')->willReturn($enabled);

		try {
			$this->middleware->beforeController($controller, 'foo');
			$this->assertFalse($exception);
		} catch (OCSNotFoundException $e) {
			$this->assertTrue($exception);
		}
	}

	public function dataAfterController() {
		return [
			[
				$this->createMock(Controller::class),
			],
			[
				$this->createMock(OCSController::class),
			],
			[
				$this->createMock(ShareAPIController::class),
			],
		];
	}

	/**
	 * @dataProvider dataAfterController
	 *
	 * @param Controller $controller
	 * @param bool $called
	 */
	public function testAfterController(Controller $controller): void {
		if ($controller instanceof ShareAPIController) {
			$controller->expects($this->once())->method('cleanup');
		}

		$response = $this->getMockBuilder('OCP\AppFramework\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->afterController($controller, 'foo', $response);
		$this->addToAssertionCount(1);
	}
}
