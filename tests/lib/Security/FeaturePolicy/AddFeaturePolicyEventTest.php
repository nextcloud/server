<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\CSP;

use OC\Security\FeaturePolicy\FeaturePolicyManager;
use OCP\AppFramework\Http\FeaturePolicy;
use OCP\Security\FeaturePolicy\AddFeaturePolicyEvent;
use Test\TestCase;

class AddFeaturePolicyEventTest extends TestCase {
	public function testAddEvent(): void {
		$manager = $this->createMock(FeaturePolicyManager::class);
		$policy = $this->createMock(FeaturePolicy::class);
		$event = new AddFeaturePolicyEvent($manager);

		$manager->expects($this->once())
			->method('addDefaultPolicy')
			->with($policy);

		$event->addPolicy($policy);
	}
}
