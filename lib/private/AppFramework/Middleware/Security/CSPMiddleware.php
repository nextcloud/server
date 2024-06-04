<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\Security\CSP\ContentSecurityPolicyManager;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

class CSPMiddleware extends Middleware {
	/** @var ContentSecurityPolicyManager */
	private $contentSecurityPolicyManager;
	/** @var ContentSecurityPolicyNonceManager */
	private $cspNonceManager;
	/** @var CsrfTokenManager */
	private $csrfTokenManager;

	public function __construct(ContentSecurityPolicyManager $policyManager,
		ContentSecurityPolicyNonceManager $cspNonceManager,
		CsrfTokenManager $csrfTokenManager) {
		$this->contentSecurityPolicyManager = $policyManager;
		$this->cspNonceManager = $cspNonceManager;
		$this->csrfTokenManager = $csrfTokenManager;
	}

	/**
	 * Performs the default CSP modifications that may be injected by other
	 * applications
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response): Response {
		$policy = !is_null($response->getContentSecurityPolicy()) ? $response->getContentSecurityPolicy() : new ContentSecurityPolicy();

		if (get_class($policy) === EmptyContentSecurityPolicy::class) {
			return $response;
		}

		$defaultPolicy = $this->contentSecurityPolicyManager->getDefaultPolicy();
		$defaultPolicy = $this->contentSecurityPolicyManager->mergePolicies($defaultPolicy, $policy);

		if ($this->cspNonceManager->browserSupportsCspV3()) {
			$defaultPolicy->useJsNonce($this->csrfTokenManager->getToken()->getEncryptedValue());
		}

		$response->setContentSecurityPolicy($defaultPolicy);

		return $response;
	}
}
