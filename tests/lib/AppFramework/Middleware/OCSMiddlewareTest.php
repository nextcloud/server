<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\OCSMiddleware;
use OC\AppFramework\OCS\BaseResponse;
use OC\AppFramework\OCS\V1Response;
use OC\AppFramework\OCS\V2Response;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class OCSMiddlewareTest extends \Test\TestCase {
	/**
	 * @var IRequest
	 */
	private $request;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)
			->getMock();
	}

	public function dataAfterException() {
		$OCSController = $this->getMockBuilder(OCSController::class)
			->disableOriginalConstructor()
			->getMock();
		$controller = $this->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->getMock();

		return [
			[$OCSController, new \Exception(), true],
			[$OCSController, new OCSException(), false, '', Http::STATUS_INTERNAL_SERVER_ERROR],
			[$OCSController, new OCSException('foo'), false, 'foo', Http::STATUS_INTERNAL_SERVER_ERROR],
			[$OCSController, new OCSException('foo', Http::STATUS_IM_A_TEAPOT), false, 'foo', Http::STATUS_IM_A_TEAPOT],
			[$OCSController, new OCSBadRequestException(), false, '', Http::STATUS_BAD_REQUEST],
			[$OCSController, new OCSBadRequestException('foo'), false, 'foo', Http::STATUS_BAD_REQUEST],
			[$OCSController, new OCSForbiddenException(), false, '', Http::STATUS_FORBIDDEN],
			[$OCSController, new OCSForbiddenException('foo'), false, 'foo', Http::STATUS_FORBIDDEN],
			[$OCSController, new OCSNotFoundException(), false, '', Http::STATUS_NOT_FOUND],
			[$OCSController, new OCSNotFoundException('foo'), false, 'foo', Http::STATUS_NOT_FOUND],

			[$controller, new \Exception(), true],
			[$controller, new OCSException(), true],
			[$controller, new OCSException('foo'), true],
			[$controller, new OCSException('foo', Http::STATUS_IM_A_TEAPOT), true],
			[$controller, new OCSBadRequestException(), true],
			[$controller, new OCSBadRequestException('foo'), true],
			[$controller, new OCSForbiddenException(), true],
			[$controller, new OCSForbiddenException('foo'), true],
			[$controller, new OCSNotFoundException(), true],
			[$controller, new OCSNotFoundException('foo'), true],
		];
	}

	/**
	 * @dataProvider dataAfterException
	 *
	 * @param Controller $controller
	 * @param \Exception $exception
	 * @param bool $forward
	 * @param string $message
	 * @param int $code
	 */
	public function testAfterExceptionOCSv1($controller, $exception, $forward, $message = '', $code = 0): void {
		$this->request
			->method('getScriptName')
			->willReturn('/ocs/v1.php');
		$OCSMiddleware = new OCSMiddleware($this->request);
		$OCSMiddleware->beforeController($controller, 'method');

		if ($forward) {
			$this->expectException(get_class($exception));
			$this->expectExceptionMessage($exception->getMessage());
		}

		$result = $OCSMiddleware->afterException($controller, 'method', $exception);

		$this->assertInstanceOf(V1Response::class, $result);

		$this->assertSame($message, $this->invokePrivate($result, 'statusMessage'));

		if ($exception->getCode() === 0) {
			$this->assertSame(\OCP\AppFramework\OCSController::RESPOND_UNKNOWN_ERROR, $result->getOCSStatus());
		} else {
			$this->assertSame($code, $result->getOCSStatus());
		}

		$this->assertSame(Http::STATUS_OK, $result->getStatus());
	}

	/**
	 * @dataProvider dataAfterException
	 *
	 * @param Controller $controller
	 * @param \Exception $exception
	 * @param bool $forward
	 * @param string $message
	 * @param int $code
	 */
	public function testAfterExceptionOCSv2($controller, $exception, $forward, $message = '', $code = 0): void {
		$this->request
			->method('getScriptName')
			->willReturn('/ocs/v2.php');
		$OCSMiddleware = new OCSMiddleware($this->request);
		$OCSMiddleware->beforeController($controller, 'method');

		if ($forward) {
			$this->expectException(get_class($exception));
			$this->expectExceptionMessage($exception->getMessage());
		}

		$result = $OCSMiddleware->afterException($controller, 'method', $exception);

		$this->assertInstanceOf(V2Response::class, $result);

		$this->assertSame($message, $this->invokePrivate($result, 'statusMessage'));
		if ($exception->getCode() === 0) {
			$this->assertSame(\OCP\AppFramework\OCSController::RESPOND_UNKNOWN_ERROR, $result->getOCSStatus());
		} else {
			$this->assertSame($code, $result->getOCSStatus());
		}
		$this->assertSame($code, $result->getStatus());
	}

	/**
	 * @dataProvider dataAfterException
	 *
	 * @param Controller $controller
	 * @param \Exception $exception
	 * @param bool $forward
	 * @param string $message
	 * @param int $code
	 */
	public function testAfterExceptionOCSv2SubFolder($controller, $exception, $forward, $message = '', $code = 0): void {
		$this->request
			->method('getScriptName')
			->willReturn('/mysubfolder/ocs/v2.php');
		$OCSMiddleware = new OCSMiddleware($this->request);
		$OCSMiddleware->beforeController($controller, 'method');

		if ($forward) {
			$this->expectException(get_class($exception));
			$this->expectExceptionMessage($exception->getMessage());
		}

		$result = $OCSMiddleware->afterException($controller, 'method', $exception);

		$this->assertInstanceOf(V2Response::class, $result);

		$this->assertSame($message, $this->invokePrivate($result, 'statusMessage'));
		if ($exception->getCode() === 0) {
			$this->assertSame(\OCP\AppFramework\OCSController::RESPOND_UNKNOWN_ERROR, $result->getOCSStatus());
		} else {
			$this->assertSame($code, $result->getOCSStatus());
		}
		$this->assertSame($code, $result->getStatus());
	}

	public function dataAfterController() {
		$OCSController = $this->getMockBuilder(OCSController::class)
			->disableOriginalConstructor()
			->getMock();
		$controller = $this->getMockBuilder(Controller::class)
			->disableOriginalConstructor()
			->getMock();

		return [
			[$OCSController, new Http\Response(), false],
			[$OCSController, new Http\JSONResponse(), false],
			[$OCSController, new Http\JSONResponse(['message' => 'foo']), false],
			[$OCSController, new Http\JSONResponse(['message' => 'foo'], Http::STATUS_UNAUTHORIZED), true, OCSController::RESPOND_UNAUTHORISED],
			[$OCSController, new Http\JSONResponse(['message' => 'foo'], Http::STATUS_FORBIDDEN), true],

			[$controller, new Http\Response(), false],
			[$controller, new Http\JSONResponse(), false],
			[$controller, new Http\JSONResponse(['message' => 'foo']), false],
			[$controller, new Http\JSONResponse(['message' => 'foo'], Http::STATUS_UNAUTHORIZED), false],
			[$controller, new Http\JSONResponse(['message' => 'foo'], Http::STATUS_FORBIDDEN), false],

		];
	}

	/**
	 * @dataProvider dataAfterController
	 *
	 * @param Controller $controller
	 * @param Http\Response $response
	 * @param bool $converted
	 * @param int $convertedOCSStatus
	 */
	public function testAfterController($controller, $response, $converted, $convertedOCSStatus = 0): void {
		$OCSMiddleware = new OCSMiddleware($this->request);
		$newResponse = $OCSMiddleware->afterController($controller, 'foo', $response);

		if ($converted === false) {
			$this->assertSame($response, $newResponse);
		} else {
			$this->assertInstanceOf(BaseResponse::class, $newResponse);
			$this->assertSame($response->getData()['message'], $this->invokePrivate($newResponse, 'statusMessage'));

			if ($convertedOCSStatus) {
				$this->assertSame($convertedOCSStatus, $newResponse->getOCSStatus());
			} else {
				$this->assertSame($response->getStatus(), $newResponse->getOCSStatus());
			}
			$this->assertSame($response->getStatus(), $newResponse->getStatus());
		}
	}
}
