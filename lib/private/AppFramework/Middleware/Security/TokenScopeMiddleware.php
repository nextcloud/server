<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware\Security;

use NCU\AppFramework\Http\Attribute\TokenScope;
use OC\AppFramework\Http\Attributes\NoTokenScopeRequired;
use OC\AppFramework\Middleware\Security\Exceptions\TokenScopeException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Lockdown\LockdownManager;
use OCP\App\IAppManager;
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
		private IAppManager $appManager,
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

		$attribute = $this->tokenScope($controller);
		if ($attribute instanceof NoTokenScopeRequired && $this->isShipped()) {
			return;
		}
		if (!$attribute instanceof TokenScope
			|| !$this->mayRequire($attribute->scope)
			|| !$this->lockdownManager->hasScope($attribute->scope)) {
			throw new TokenScopeException();
		}
	}

	/**
	 * Apps that aren't shipped may only require scopes named after themselves,
	 * so they can't open their routes to app passwords limited to another app
	 */
	private function mayRequire(string $scope): bool {
		return $this->isShipped() || str_starts_with($scope, $this->appName . ':');
	}

	private function isShipped(): bool {
		return $this->appName === 'core' || $this->appManager->isShipped($this->appName);
	}

	/**
	 * The method's attribute, else its class's
	 */
	private function tokenScope(Controller $controller): TokenScope|NoTokenScopeRequired|null {
		$attribute = $this->reflector->getAttribute(TokenScope::class)
			?? $this->reflector->getAttribute(NoTokenScopeRequired::class)
			?? (new ReflectionClass($controller))->getAttributes(TokenScope::class)[0]
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
