<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\FeaturePolicy;

use OC\Security\FeaturePolicy\FeaturePolicyManager;
use OCP\AppFramework\Http\EmptyFeaturePolicy;
use OCP\EventDispatcher\Event;

/**
 * Event that allows to register a feature policy header to a request.
 *
 * @since 17.0.0
 */
class AddFeaturePolicyEvent extends Event {
	/** @var FeaturePolicyManager */
	private $policyManager;

	/**
	 * @since 17.0.0
	 */
	public function __construct(FeaturePolicyManager $policyManager) {
		parent::__construct();
		$this->policyManager = $policyManager;
	}

	/**
	 * @since 17.0.0
	 */
	public function addPolicy(EmptyFeaturePolicy $policy) {
		$this->policyManager->addDefaultPolicy($policy);
	}
}
