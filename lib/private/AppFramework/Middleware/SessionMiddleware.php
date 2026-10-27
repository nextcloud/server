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
	public function __construct(
		private ControllerMethodReflector $reflector,
		private ISession $session,
	) {
	}

	#[\Override]
	public function beforeController(Controller $controller, string $methodName): void {
		if ($this->reflector->hasAnnotationOrAttribute('UseSession', UseSession::class)) {
			$this->session->reopen();
		}
	}

	#[\Override]
	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		if ($this->reflector->hasAnnotationOrAttribute('UseSession', UseSession::class)) {
			$this->session->close();
		}

		return $response;
	}
}
