<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware;

use OC\Core\Controller\LoginController;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserSession;

class AdditionalScriptsMiddleware extends Middleware {
	public function __construct(
		private IUserSession $userSession,
		private IEventDispatcher $dispatcher,
	) {
	}

	public function afterController($controller, $methodName, Response $response): Response {
		if ($response instanceof TemplateResponse) {
			if ($controller instanceof LoginController) {
				$this->dispatcher->dispatchTyped(new BeforeLoginTemplateRenderedEvent($response));
			} else {
				$isLoggedIn = !($response instanceof StandaloneTemplateResponse) && $this->userSession->isLoggedIn();
				$this->dispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($isLoggedIn, $response));
			}
		}

		return $response;
	}
}
