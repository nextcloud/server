<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\Middleware;

use OCA\Files_Sharing\Controller\ShareInfoController;
use OCA\Files_Sharing\Exceptions\S2SException;
use OCA\Files_Sharing\Middleware\ShareInfoMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\Share\IManager as ShareManager;
use Test\TestCase;

class ShareInfoMiddlewareTest extends TestCase {

	/** @var ShareManager|\PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;

	/** @var ShareInfoMiddleware */
	private $middleware;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(ShareManager::class);
		$this->middleware = new ShareInfoMiddleware($this->shareManager);
	}

	public function testBeforeControllerNoShareInfo(): void {
		$this->shareManager->expects($this->never())
			->method($this->anything());

		$this->middleware->beforeController($this->createMock(ShareInfoMiddlewareTestController::class), 'foo');
	}

	public function testBeforeControllerShareInfoNoS2s(): void {
		$this->shareManager->expects($this->once())
			->method('outgoingServer2ServerSharesAllowed')
			->willReturn(false);

		$this->expectException(S2SException::class);
		$this->middleware->beforeController($this->createMock(ShareInfoController::class), 'foo');
	}

	public function testBeforeControllerShareInfo(): void {
		$this->shareManager->expects($this->once())
			->method('outgoingServer2ServerSharesAllowed')
			->willReturn(true);

		$this->middleware->beforeController($this->createMock(ShareInfoController::class), 'foo');
	}

	public function testAfterExceptionNoShareInfo(): void {
		$exeption = new \Exception();

		try {
			$this->middleware->afterException($this->createMock(ShareInfoMiddlewareTestController::class), 'foo', $exeption);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertSame($exeption, $e);
		}
	}


	public function testAfterExceptionNoS2S(): void {
		$exeption = new \Exception();

		try {
			$this->middleware->afterException($this->createMock(ShareInfoController::class), 'foo', $exeption);
			$this->fail();
		} catch (\Exception $e) {
			$this->assertSame($exeption, $e);
		}
	}

	public function testAfterExceptionS2S(): void {
		$expected = new JSONResponse([], Http::STATUS_NOT_FOUND);

		$this->assertEquals(
			$expected,
			$this->middleware->afterException($this->createMock(ShareInfoController::class), 'foo', new S2SException())
		);
	}

	public function testAfterControllerNoShareInfo(): void {
		$response = $this->createMock(Response::class);

		$this->assertEquals(
			$response,
			$this->middleware->afterController($this->createMock(ShareInfoMiddlewareTestController::class), 'foo', $response)
		);
	}

	public function testAfterControllerNoJSON(): void {
		$response = $this->createMock(Response::class);

		$this->assertEquals(
			$response,
			$this->middleware->afterController($this->createMock(ShareInfoController::class), 'foo', $response)
		);
	}

	public function testAfterControllerJSONok(): void {
		$data = ['foo' => 'bar'];
		$response = new JSONResponse($data);

		$expected = new JSONResponse([
			'data' => $data,
			'status' => 'success',
		]);

		$this->assertEquals(
			$expected,
			$this->middleware->afterController($this->createMock(ShareInfoController::class), 'foo', $response)
		);
	}

	public function testAfterControllerJSONerror(): void {
		$data = ['foo' => 'bar'];
		$response = new JSONResponse($data, Http::STATUS_FORBIDDEN);

		$expected = new JSONResponse([
			'data' => $data,
			'status' => 'error',
		], Http::STATUS_FORBIDDEN);

		$this->assertEquals(
			$expected,
			$this->middleware->afterController($this->createMock(ShareInfoController::class), 'foo', $response)
		);
	}
}

class ShareInfoMiddlewareTestController extends Controller {
}
