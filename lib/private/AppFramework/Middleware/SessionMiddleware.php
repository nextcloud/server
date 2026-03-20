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

class SessionMiddleware extends Middleware {
	/** @var ControllerMethodReflector */
	private $reflector;

	/** @var ISession */
	private $session;

	public function __construct(ControllerMethodReflector $reflector,
		ISession $session) {
		$this->reflector = $reflector;
		$this->session = $session;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 */
	public function beforeController($controller, $methodName) {
		if ($this->reflector->hasAnnotationOrAttribute('UseSession', UseSession::class)) {
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
		if ($this->reflector->hasAnnotationOrAttribute('UseSession', UseSession::class)) {
			$this->session->close();
		}

		return $response;
	}
}
