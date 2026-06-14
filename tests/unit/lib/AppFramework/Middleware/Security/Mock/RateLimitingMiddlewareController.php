<?php

namespace Test\AppFramework\Middleware\Security\Mock;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\UserRateLimit;

class RateLimitingMiddlewareController extends Controller {
	/**
	 * @UserRateThrottle(limit=20, period=200)
	 * @AnonRateThrottle(limit=10, period=100)
	 */
	public function testMethodWithAnnotation() {
	}

	/**
	 * @AnonRateThrottle(limit=10, period=100)
	 */
	public function testMethodWithAnnotationFallback() {
	}

	public function testMethodWithoutAnnotation() {
	}

	#[UserRateLimit(limit: 20, period: 200)]
	#[AnonRateLimit(limit: 10, period: 100)]
	public function testMethodWithAttributes() {
	}

	#[AnonRateLimit(limit: 10, period: 100)]
	public function testMethodWithAttributesFallback() {
	}
}
