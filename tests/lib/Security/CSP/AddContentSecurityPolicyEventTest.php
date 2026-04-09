<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\CSP;

use OC\Security\CSP\ContentSecurityPolicyManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use Test\TestCase;

class AddContentSecurityPolicyEventTest extends TestCase {
	public function testAddEvent(): void {
		$cspManager = $this->createMock(ContentSecurityPolicyManager::class);
		$policy = $this->createMock(ContentSecurityPolicy::class);
		$event = new AddContentSecurityPolicyEvent($cspManager);

		$cspManager->expects($this->once())
			->method('addDefaultPolicy')
			->with($policy);

		$event->addPolicy($policy);
	}
}
