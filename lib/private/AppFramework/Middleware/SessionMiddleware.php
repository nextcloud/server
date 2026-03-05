<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\ISession;
use ReflectionMethod;

class SessionMiddleware extends Middleware {
	public function __construct(
		private ControllerMethodReflector $reflector,
		private ISession $session,
	) {
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		/**
		 * Annotation deprecated with Nextcloud 26
		 */
		$hasAnnotation = $this->reflector->hasAnnotation('UseSession');
		if ($hasAnnotation) {
			$this->session->reopen();
			return;
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$hasAttribute = !empty($reflectionMethod->getAttributes(UseSession::class));
		if ($hasAttribute) {
			$this->session->reopen();
		}
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response) {
		/**
		 * Annotation deprecated with Nextcloud 26
		 */
		$hasAnnotation = $this->reflector->hasAnnotation('UseSession');
		if ($hasAnnotation) {
			$this->session->close();
			return $response;
		}

		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$hasAttribute = !empty($reflectionMethod->getAttributes(UseSession::class));
		if ($hasAttribute) {
			$this->session->close();
		}

		return $response;
	}
}
