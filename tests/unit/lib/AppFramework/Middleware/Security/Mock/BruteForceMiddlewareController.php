<?php

namespace Test\AppFramework\Middleware\Security\Mock;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;

class BruteForceMiddlewareController extends Controller {
	/**
	 * @BruteForceProtection(action=login)
	 */
	public function testMethodWithAnnotation() {
	}

	public function testMethodWithoutAnnotation() {
	}

	#[BruteForceProtection(action: 'single')]
	public function singleAttribute(): void {
	}

	#[BruteForceProtection(action: 'first')]
	#[BruteForceProtection(action: 'second')]
	public function multipleAttributes(): void {
	}
}
