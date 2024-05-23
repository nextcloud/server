<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\CSP;

use OC\Security\CSP\ContentSecurityPolicyManager;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;

/**
 * Allows to inject something into the default content policy. This is for
 * example useful when you're injecting Javascript code into a view belonging
 * to another controller and cannot modify its Content-Security-Policy itself.
 * Note that the adjustment is only applied to applications that use AppFramework
 * controllers.
 *
 * WARNING: Using this API incorrectly may make the instance more insecure.
 * Do think twice before adding whitelisting resources. Please do also note
 * that it is not possible to use the `disallowXYZ` functions.
 *
 * @since 17.0.0
 */
class AddContentSecurityPolicyEvent extends Event {
	/** @var ContentSecurityPolicyManager */
	private $policyManager;

	/**
	 * @since 17.0.0
	 */
	public function __construct(ContentSecurityPolicyManager $policyManager) {
		parent::__construct();
		$this->policyManager = $policyManager;
	}

	/**
	 * @since 17.0.0
	 */
	public function addPolicy(EmptyContentSecurityPolicy $csp): void {
		$this->policyManager->addDefaultPolicy($csp);
	}
}
