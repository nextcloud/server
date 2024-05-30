<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Middleware\Security;

use OC\Security\FeaturePolicy\FeaturePolicy;
use OC\Security\FeaturePolicy\FeaturePolicyManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\EmptyFeaturePolicy;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

class FeaturePolicyMiddleware extends Middleware {
	/** @var FeaturePolicyManager */
	private $policyManager;

	public function __construct(FeaturePolicyManager $policyManager) {
		$this->policyManager = $policyManager;
	}

	/**
	 * Performs the default FeaturePolicy modifications that may be injected by other
	 * applications
	 *
	 * @param Controller $controller
	 * @param string $methodName
	 * @param Response $response
	 * @return Response
	 */
	public function afterController($controller, $methodName, Response $response): Response {
		$policy = !is_null($response->getFeaturePolicy()) ? $response->getFeaturePolicy() : new FeaturePolicy();

		if (get_class($policy) === EmptyFeaturePolicy::class) {
			return $response;
		}

		$defaultPolicy = $this->policyManager->getDefaultPolicy();
		$defaultPolicy = $this->policyManager->mergePolicies($defaultPolicy, $policy);
		$response->setFeaturePolicy($defaultPolicy);

		return $response;
	}
}
