<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\WellKnownController;
use OC\Http\WellKnown\RequestManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\IResponse;
use OCP\IRequest;
use Test\TestCase;

class WellKnownControllerTest extends TestCase {
	/** @var WellKnownController */
	private $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->controller = $this->createInstanceWithMocks(WellKnownController::class);
	}

	public function testHandleNotProcessed(): void {
		$httpResponse = $this->controller->handle('nodeinfo');

		self::assertInstanceOf(JSONResponse::class, $httpResponse);
		self::assertArrayHasKey('X-NEXTCLOUD-WELL-KNOWN', $httpResponse->getHeaders());
	}

	public function testHandle(): void {
		$response = $this->createMock(IResponse::class);
		$jsonResponse = $this->createMock(JSONResponse::class);
		$response->expects(self::once())
			->method('toHttpResponse')
			->willReturn($jsonResponse);
		$this->mocks[RequestManager::class]->expects(self::once())
			->method('process')
			->with(
				'nodeinfo',
				$this->mocks[IRequest::class]
			)->willReturn($response);
		$jsonResponse->expects(self::once())
			->method('addHeader')
			->willReturnSelf();

		$httpResponse = $this->controller->handle('nodeinfo');

		self::assertInstanceOf(JSONResponse::class, $httpResponse);
	}
}
