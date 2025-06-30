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
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
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

	public static function dataAfterException(): array {
		return [
			[OCSController::class, new \Exception(), true],
			[OCSController::class, new OCSException(), false, '', Http::STATUS_INTERNAL_SERVER_ERROR],
			[OCSController::class, new OCSException('foo'), false, 'foo', Http::STATUS_INTERNAL_SERVER_ERROR],
			[OCSController::class, new OCSException('foo', Http::STATUS_IM_A_TEAPOT), false, 'foo', Http::STATUS_IM_A_TEAPOT],
			[OCSController::class, new OCSBadRequestException(), false, '', Http::STATUS_BAD_REQUEST],
			[OCSController::class, new OCSBadRequestException('foo'), false, 'foo', Http::STATUS_BAD_REQUEST],
			[OCSController::class, new OCSForbiddenException(), false, '', Http::STATUS_FORBIDDEN],
			[OCSController::class, new OCSForbiddenException('foo'), false, 'foo', Http::STATUS_FORBIDDEN],
			[OCSController::class, new OCSNotFoundException(), false, '', Http::STATUS_NOT_FOUND],
			[OCSController::class, new OCSNotFoundException('foo'), false, 'foo', Http::STATUS_NOT_FOUND],

			[Controller::class, new \Exception(), true],
			[Controller::class, new OCSException(), true],
			[Controller::class, new OCSException('foo'), true],
			[Controller::class, new OCSException('foo', Http::STATUS_IM_A_TEAPOT), true],
			[Controller::class, new OCSBadRequestException(), true],
			[Controller::class, new OCSBadRequestException('foo'), true],
			[Controller::class, new OCSForbiddenException(), true],
			[Controller::class, new OCSForbiddenException('foo'), true],
			[Controller::class, new OCSNotFoundException(), true],
			[Controller::class, new OCSNotFoundException('foo'), true],
		];
	}

	/**
	 * @dataProvider dataAfterException
	 */
	public function testAfterExceptionOCSv1(string $controller, \Exception $exception, bool $forward, string $message = '', int $code = 0): void {
		$controller = $this->createMock($controller);
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
			$this->assertSame(OCSController::RESPOND_UNKNOWN_ERROR, $result->getOCSStatus());
		} else {
			$this->assertSame($code, $result->getOCSStatus());
		}

		$this->assertSame(Http::STATUS_OK, $result->getStatus());
	}

	/**
	 * @dataProvider dataAfterException
	 */
	public function testAfterExceptionOCSv2(string $controller, \Exception $exception, bool $forward, string $message = '', int $code = 0): void {
		$controller = $this->createMock($controller);
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
			$this->assertSame(OCSController::RESPOND_UNKNOWN_ERROR, $result->getOCSStatus());
		} else {
			$this->assertSame($code, $result->getOCSStatus());
		}
		$this->assertSame($code, $result->getStatus());
	}

	/**
	 * @dataProvider dataAfterException
	 */
	public function testAfterExceptionOCSv2SubFolder(string $controller, \Exception $exception, bool $forward, string $message = '', int $code = 0): void {
		$controller = $this->createMock($controller);
		$this->request
			->method('getScriptName')
			->willReturn('/mysubfolder/ocs/v2.php');
		$OCSMiddleware = new OCSMiddleware($this->request);
		$OCSMiddleware->beforeController($controller, 'method');

		if ($forward) {
			$this->expectException($exception::class);
			$this->expectExceptionMessage($exception->getMessage());
		}

		$result = $OCSMiddleware->afterException($controller, 'method', $exception);

		$this->assertInstanceOf(V2Response::class, $result);

		$this->assertSame($message, $this->invokePrivate($result, 'statusMessage'));
		if ($exception->getCode() === 0) {
			$this->assertSame(OCSController::RESPOND_UNKNOWN_ERROR, $result->getOCSStatus());
		} else {
			$this->assertSame($code, $result->getOCSStatus());
		}
		$this->assertSame($code, $result->getStatus());
	}

	public static function dataAfterController(): array {
		return [
			[OCSController::class, new Response(), false],
			[OCSController::class, new JSONResponse(), false],
			[OCSController::class, new JSONResponse(['message' => 'foo']), false],
			[OCSController::class, new JSONResponse(['message' => 'foo'], Http::STATUS_UNAUTHORIZED), true, OCSController::RESPOND_UNAUTHORISED],
			[OCSController::class, new JSONResponse(['message' => 'foo'], Http::STATUS_FORBIDDEN), true],

			[Controller::class, new Response(), false],
			[Controller::class, new JSONResponse(), false],
			[Controller::class, new JSONResponse(['message' => 'foo']), false],
			[Controller::class, new JSONResponse(['message' => 'foo'], Http::STATUS_UNAUTHORIZED), false],
			[Controller::class, new JSONResponse(['message' => 'foo'], Http::STATUS_FORBIDDEN), false],

		];
	}

	/**
	 * @dataProvider dataAfterController
	 */
	public function testAfterController(string $controller, Response $response, bool $converted, int $convertedOCSStatus = 0): void {
		$controller = $this->createMock($controller);
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
