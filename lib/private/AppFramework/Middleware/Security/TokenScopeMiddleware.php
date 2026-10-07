<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\TokenScopeException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Authentication\Token\TokenRoutes;
use OC\Lockdown\LockdownManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\SubAdminRequired;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\IUserSession;

class TokenScopeMiddleware extends Middleware {
	public function __construct(
		private IRequest $request,
		private ControllerMethodReflector $reflector,
		private LockdownManager $lockdownManager,
		private TokenRoutes $tokenRoutes,
		private IUserSession $userSession,
		private string $appName,
	) {
	}

	#[\Override]
	public function beforeController(Controller $controller, string $methodName): void {
		if (!$this->lockdownManager->isScoped()) {
			return;
		}

		if (!$this->reflector->hasAnnotationOrAttribute('PublicPage', PublicPage::class)
			&& !$this->reflector->hasAnnotationOrAttribute('NoAdminRequired', NoAdminRequired::class)
			&& !$this->reflector->hasAnnotationOrAttribute('SubAdminRequired', SubAdminRequired::class)) {
			throw new TokenScopeException();
		}

		$route = (string)$this->request->getParam('_route');
		$scopes = $this->tokenRoutes->requiredScopes($this->appName, $route);
		if ($scopes === null) {
			throw new TokenScopeException();
		}
		foreach ($scopes as $scope) {
			if (!$this->lockdownManager->hasScope($scope)) {
				throw new TokenScopeException();
			}
		}

		if ($this->tokenRoutes->acceptsActAsUser($route)) {
			$user = $this->request->getParam('user');
			if ($user !== null && $user !== $this->userSession->getUser()?->getUID()) {
				throw new TokenScopeException();
			}
		}
	}

	/**
	 * Page renders run every app's listeners and initial state, so none reach a token with scopes
	 */
	#[\Override]
	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		if ($response instanceof TemplateResponse && $this->lockdownManager->isScoped()) {
			return new Response(Http::STATUS_FORBIDDEN);
		}
		return $response;
	}
}
