<?php

namespace OCA\Files_Sharing\Tests\Middleware;

use OCA\Files_Sharing\Middleware\OCSShareAPIMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IL10N;
use OCP\Share\IManager;

/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class OCSShareAPIMiddlewareTest extends \Test\TestCase {

	/** @var IManager */
	private $shareManager;
	/** @var IL10N */
	private $l;
	/** @var OCSShareAPIMiddleware */
	private $middleware;

	public function setUp() {
		$this->shareManager = $this->getMockBuilder('OCP\Share\IManager')->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')->getMock();

		$this->l->method('t')->will($this->returnArgument(0));

		$this->middleware = new OCSShareAPIMiddleware($this->shareManager, $this->l);
	}

	public function dataBeforeController() {
		return [
			[
				$this->getMockBuilder('OCP\AppFramework\Controller')->disableOriginalConstructor()->getMock(),
				false,
				false
			],
			[
				$this->getMockBuilder('OCP\AppFramework\Controller')->disableOriginalConstructor()->getMock(),
				true,
				false
			],
			[
				$this->getMockBuilder('OCP\AppFramework\OCSController')->disableOriginalConstructor()->getMock(),
				false,
				false
			],
			[
				$this->getMockBuilder('OCP\AppFramework\OCSController')->disableOriginalConstructor()->getMock(),
				true,
				false
			],
			[
				$this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')->disableOriginalConstructor()->getMock(),
				false,
				true
			],
			[
				$this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')->disableOriginalConstructor()->getMock(),
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
	public function testBeforeController(Controller $controller, $enabled, $exception) {
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
				$this->getMockBuilder('OCP\AppFramework\Controller')->disableOriginalConstructor()->getMock(),
			],
			[
				$this->getMockBuilder('OCP\AppFramework\OCSController')->disableOriginalConstructor()->getMock(),
			],
			[
				$this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')->disableOriginalConstructor()->getMock(),
			],
		];
	}

	/**
	 * @dataProvider dataAfterController
	 *
	 * @param Controller $controller
	 * @param bool $called
	 */
	public function testAfterController(Controller $controller) {
		if ($controller instanceof OCA\Files_Sharing\API\Share20OCS) {
			$controller->expects($this->once())->method('cleanup');
		}

		$response = $this->getMockBuilder('OCP\AppFramework\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->middleware->afterController($controller, 'foo', $response);
	}
}
