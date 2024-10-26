<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\FeaturePolicyMiddleware;
use OC\Security\FeaturePolicy\FeaturePolicy;
use OC\Security\FeaturePolicy\FeaturePolicyManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\EmptyFeaturePolicy;
use OCP\AppFramework\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;

class FeaturePolicyMiddlewareTest extends \Test\TestCase {
	/** @var FeaturePolicyMiddleware|MockObject */
	private $middleware;
	/** @var Controller|MockObject */
	private $controller;
	/** @var FeaturePolicyManager|MockObject */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->controller = $this->createMock(Controller::class);
		$this->manager = $this->createMock(FeaturePolicyManager::class);
		$this->middleware = new FeaturePolicyMiddleware(
			$this->manager
		);
	}

	public function testAfterController(): void {
		$response = $this->createMock(Response::class);
		$defaultPolicy = new FeaturePolicy();
		$defaultPolicy->addAllowedCameraDomain('defaultpolicy');
		$currentPolicy = new FeaturePolicy();
		$currentPolicy->addAllowedAutoplayDomain('currentPolicy');
		$mergedPolicy = new FeaturePolicy();
		$mergedPolicy->addAllowedGeoLocationDomain('mergedPolicy');
		$response->method('getFeaturePolicy')
			->willReturn($currentPolicy);
		$this->manager->method('getDefaultPolicy')
			->willReturn($defaultPolicy);
		$this->manager->method('mergePolicies')
			->with($defaultPolicy, $currentPolicy)
			->willReturn($mergedPolicy);
		$response->expects($this->once())
			->method('setFeaturePolicy')
			->with($mergedPolicy);

		$this->middleware->afterController($this->controller, 'test', $response);
	}

	public function testAfterControllerEmptyCSP(): void {
		$response = $this->createMock(Response::class);
		$emptyPolicy = new EmptyFeaturePolicy();
		$response->method('getFeaturePolicy')
			->willReturn($emptyPolicy);
		$response->expects($this->never())
			->method('setFeaturePolicy');

		$this->middleware->afterController($this->controller, 'test', $response);
	}
}
