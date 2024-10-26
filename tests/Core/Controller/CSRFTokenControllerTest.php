<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\CSRFTokenController;
use OC\Security\CSRF\CsrfToken;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Test\TestCase;

class CSRFTokenControllerTest extends TestCase {
	/** @var CSRFTokenController */
	private $controller;

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var CsrfTokenManager|\PHPUnit\Framework\MockObject\MockObject */
	private $tokenManager;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->tokenManager = $this->createMock(CsrfTokenManager::class);

		$this->controller = new CSRFTokenController('core', $this->request,
			$this->tokenManager);
	}

	public function testGetToken(): void {
		$this->request->method('passesStrictCookieCheck')->willReturn(true);

		$token = $this->createMock(CsrfToken::class);
		$this->tokenManager->method('getToken')->willReturn($token);
		$token->method('getEncryptedValue')->willReturn('toktok123');

		$response = $this->controller->index();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals([
			'token' => 'toktok123'
		], $response->getData());
	}

	public function testGetTokenNoStrictSameSiteCookie(): void {
		$this->request->method('passesStrictCookieCheck')->willReturn(false);

		$response = $this->controller->index();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}
}
