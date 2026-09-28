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
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class WellKnownControllerTest extends TestCase {
	/** @var IRequest|MockObject */
	private $request;

	/** @var RequestManager|MockObject */
	private $manager;

	/** @var WellKnownController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->manager = $this->createMock(RequestManager::class);

		$this->controller = new WellKnownController(
			$this->request,
			$this->manager,
		);
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
		$this->manager->expects(self::once())
			->method('process')
			->with(
				'nodeinfo',
				$this->request
			)->willReturn($response);
		$jsonResponse->expects(self::once())
			->method('addHeader')
			->willReturnSelf();

		$httpResponse = $this->controller->handle('nodeinfo');

		self::assertInstanceOf(JSONResponse::class, $httpResponse);
	}
}
