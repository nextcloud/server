<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\CSP;

use OC\Security\FeaturePolicy\FeaturePolicyManager;
use OCP\AppFramework\Http\FeaturePolicy;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\FeaturePolicy\AddFeaturePolicyEvent;
use OCP\Server;
use Test\TestCase;

class FeaturePolicyManagerTest extends TestCase {
	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var FeaturePolicyManager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();
		$this->dispatcher = Server::get(IEventDispatcher::class);
		$this->manager = new FeaturePolicyManager($this->dispatcher);
	}

	public function testAddDefaultPolicy(): void {
		$this->manager->addDefaultPolicy(new FeaturePolicy());
		$this->addToAssertionCount(1);
	}

	public function testGetDefaultPolicyWithPoliciesViaEvent(): void {
		$this->dispatcher->addListener(AddFeaturePolicyEvent::class, function (AddFeaturePolicyEvent $e): void {
			$policy = new FeaturePolicy();
			$policy->addAllowedMicrophoneDomain('mydomain.com');
			$policy->addAllowedPaymentDomain('mypaymentdomain.com');

			$e->addPolicy($policy);
		});

		$this->dispatcher->addListener(AddFeaturePolicyEvent::class, function (AddFeaturePolicyEvent $e): void {
			$policy = new FeaturePolicy();
			$policy->addAllowedPaymentDomain('mydomainother.com');
			$policy->addAllowedGeoLocationDomain('mylocation.here');

			$e->addPolicy($policy);
		});

		$this->dispatcher->addListener(AddFeaturePolicyEvent::class, function (AddFeaturePolicyEvent $e): void {
			$policy = new FeaturePolicy();
			$policy->addAllowedAutoplayDomain('youtube.com');

			$e->addPolicy($policy);
		});

		$expected = new \OC\Security\FeaturePolicy\FeaturePolicy();
		$expected->addAllowedMicrophoneDomain('mydomain.com');
		$expected->addAllowedPaymentDomain('mypaymentdomain.com');
		$expected->addAllowedPaymentDomain('mydomainother.com');
		$expected->addAllowedGeoLocationDomain('mylocation.here');
		$expected->addAllowedAutoplayDomain('youtube.com');

		$expectedStringPolicy = "autoplay 'self' youtube.com;camera 'none';fullscreen 'self';geolocation mylocation.here;microphone mydomain.com;payment mypaymentdomain.com mydomainother.com";

		$this->assertEquals($expected, $this->manager->getDefaultPolicy());
		$this->assertSame($expectedStringPolicy, $this->manager->getDefaultPolicy()->buildPolicy());
	}
}
