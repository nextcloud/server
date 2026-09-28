<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\CSPMiddleware;
use OC\Security\CSP\ContentSecurityPolicy;
use OC\Security\CSP\ContentSecurityPolicyManager;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;

class CSPMiddlewareTest extends \Test\TestCase {
	/** @var CSPMiddleware&MockObject */
	private $middleware;
	/** @var Controller&MockObject */
	private $controller;
	/** @var ContentSecurityPolicyManager&MockObject */
	private $contentSecurityPolicyManager;
	/** @var ContentSecurityPolicyNonceManager&MockObject */
	private $cspNonceManager;

	protected function setUp(): void {
		parent::setUp();

		$this->controller = $this->createMock(Controller::class);
		$this->contentSecurityPolicyManager = $this->createMock(ContentSecurityPolicyManager::class);
		$this->cspNonceManager = $this->createMock(ContentSecurityPolicyNonceManager::class);
		$this->middleware = new CSPMiddleware(
			$this->contentSecurityPolicyManager,
			$this->cspNonceManager,
		);
	}

	public function testAfterController(): void {
		$this->cspNonceManager
			->expects($this->once())
			->method('browserSupportsCspV3')
			->willReturn(false);
		$response = $this->createMock(Response::class);
		$defaultPolicy = new ContentSecurityPolicy();
		$defaultPolicy->addAllowedImageDomain('defaultpolicy');
		$currentPolicy = new ContentSecurityPolicy();
		$currentPolicy->addAllowedConnectDomain('currentPolicy');
		$mergedPolicy = new ContentSecurityPolicy();
		$mergedPolicy->addAllowedMediaDomain('mergedPolicy');
		$response
			->expects($this->exactly(2))
			->method('getContentSecurityPolicy')
			->willReturn($currentPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('getDefaultPolicy')
			->willReturn($defaultPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('mergePolicies')
			->with($defaultPolicy, $currentPolicy)
			->willReturn($mergedPolicy);
		$response->expects($this->once())
			->method('setContentSecurityPolicy')
			->with($mergedPolicy);

		$this->middleware->afterController($this->controller, 'test', $response);
	}

	public function testAfterControllerEmptyCSP(): void {
		$response = $this->createMock(Response::class);
		$emptyPolicy = new EmptyContentSecurityPolicy();
		$response->expects($this->any())
			->method('getContentSecurityPolicy')
			->willReturn($emptyPolicy);
		$response->expects($this->never())
			->method('setContentSecurityPolicy');

		$this->middleware->afterController($this->controller, 'test', $response);
	}

	public function testAfterControllerWithContentSecurityPolicy3Support(): void {
		$this->cspNonceManager
			->expects($this->once())
			->method('browserSupportsCspV3')
			->willReturn(true);
		$token = base64_encode('the-nonce');
		$this->cspNonceManager
			->expects($this->once())
			->method('getNonce')
			->willReturn($token);
		$response = $this->createMock(Response::class);
		$defaultPolicy = new ContentSecurityPolicy();
		$defaultPolicy->addAllowedImageDomain('defaultpolicy');
		$currentPolicy = new ContentSecurityPolicy();
		$currentPolicy->addAllowedConnectDomain('currentPolicy');
		$mergedPolicy = new ContentSecurityPolicy();
		$mergedPolicy->addAllowedMediaDomain('mergedPolicy');
		$response
			->expects($this->exactly(2))
			->method('getContentSecurityPolicy')
			->willReturn($currentPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('getDefaultPolicy')
			->willReturn($defaultPolicy);
		$this->contentSecurityPolicyManager
			->expects($this->once())
			->method('mergePolicies')
			->with($defaultPolicy, $currentPolicy)
			->willReturn($mergedPolicy);
		$response->expects($this->once())
			->method('setContentSecurityPolicy')
			->with($mergedPolicy);

		$this->assertEquals($response, $this->middleware->afterController($this->controller, 'test', $response));
	}
}
