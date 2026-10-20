<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware\Security;

use OC\AppFramework\Http\Attributes\NoTokenScopeRequired;
use OC\AppFramework\Http\Attributes\TokenScope;
use OC\AppFramework\Middleware\Security\Exceptions\TokenScopeException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Lockdown\LockdownManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\SubAdminRequired;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use ReflectionClass;

class TokenScopeMiddleware extends Middleware {
	public function __construct(
		private ControllerMethodReflector $reflector,
		private LockdownManager $lockdownManager,
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

		$attribute = $this->tokenScope($controller);
		if ($attribute instanceof NoTokenScopeRequired) {
			return;
		}
		if ($attribute === null || !$this->lockdownManager->hasScope($attribute->scope)) {
			throw new TokenScopeException();
		}
	}

	/**
	 * The method's attribute, else its class's
	 */
	private function tokenScope(Controller $controller): TokenScope|NoTokenScopeRequired|null {
		$class = new ReflectionClass($controller);
		$attribute = $this->reflector->getAttribute(TokenScope::class)
			?? $this->reflector->getAttribute(NoTokenScopeRequired::class)
			?? $class->getAttributes(TokenScope::class)[0]
			?? $class->getAttributes(NoTokenScopeRequired::class)[0]
			?? null;
		return $attribute?->newInstance();
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
