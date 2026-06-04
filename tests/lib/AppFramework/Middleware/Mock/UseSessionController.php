<?php

namespace Test\AppFramework\Middleware\Mock;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\UseSession;

class UseSessionController extends Controller {
	/**
	 * @UseSession
	 */
	public function withAnnotation() {
	}
	#[UseSession]
	public function withAttribute() {
	}
	public function without() {
	}
}
