<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\FeaturePolicy;

class FeaturePolicyTest extends \Test\TestCase {
	/** @var EmptyFeaturePolicy */
	private $policy;

	protected function setUp(): void {
		parent::setUp();
		$this->policy = new FeaturePolicy();
	}

	public function testGetPolicyDefault(): void {
		$defaultPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation 'none';microphone 'none';payment 'none'";
		$this->assertSame($defaultPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyAutoplayDomainValid(): void {
		$expectedPolicy = "autoplay 'self' www.nextcloud.com;camera 'none';fullscreen 'self';geolocation 'none';microphone 'none';payment 'none'";

		$this->policy->addAllowedAutoplayDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyAutoplayDomainValidMultiple(): void {
		$expectedPolicy = "autoplay 'self' www.nextcloud.com www.nextcloud.org;camera 'none';fullscreen 'self';geolocation 'none';microphone 'none';payment 'none'";

		$this->policy->addAllowedAutoplayDomain('www.nextcloud.com');
		$this->policy->addAllowedAutoplayDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyCameraDomainValid(): void {
		$expectedPolicy = "autoplay 'self';camera www.nextcloud.com;fullscreen 'self';geolocation 'none';microphone 'none';payment 'none'";

		$this->policy->addAllowedCameraDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyCameraDomainValidMultiple(): void {
		$expectedPolicy = "autoplay 'self';camera www.nextcloud.com www.nextcloud.org;fullscreen 'self';geolocation 'none';microphone 'none';payment 'none'";

		$this->policy->addAllowedCameraDomain('www.nextcloud.com');
		$this->policy->addAllowedCameraDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyFullScreenDomainValid(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self' www.nextcloud.com;geolocation 'none';microphone 'none';payment 'none'";

		$this->policy->addAllowedFullScreenDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyFullScreenDomainValidMultiple(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self' www.nextcloud.com www.nextcloud.org;geolocation 'none';microphone 'none';payment 'none'";

		$this->policy->addAllowedFullScreenDomain('www.nextcloud.com');
		$this->policy->addAllowedFullScreenDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyGeoLocationDomainValid(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation www.nextcloud.com;microphone 'none';payment 'none'";

		$this->policy->addAllowedGeoLocationDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyGeoLocationDomainValidMultiple(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation www.nextcloud.com www.nextcloud.org;microphone 'none';payment 'none'";

		$this->policy->addAllowedGeoLocationDomain('www.nextcloud.com');
		$this->policy->addAllowedGeoLocationDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyMicrophoneDomainValid(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation 'none';microphone www.nextcloud.com;payment 'none'";

		$this->policy->addAllowedMicrophoneDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyMicrophoneDomainValidMultiple(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation 'none';microphone www.nextcloud.com www.nextcloud.org;payment 'none'";

		$this->policy->addAllowedMicrophoneDomain('www.nextcloud.com');
		$this->policy->addAllowedMicrophoneDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyPaymentDomainValid(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation 'none';microphone 'none';payment www.nextcloud.com";

		$this->policy->addAllowedPaymentDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}

	public function testGetPolicyPaymentDomainValidMultiple(): void {
		$expectedPolicy = "autoplay 'self';camera 'none';fullscreen 'self';geolocation 'none';microphone 'none';payment www.nextcloud.com www.nextcloud.org";

		$this->policy->addAllowedPaymentDomain('www.nextcloud.com');
		$this->policy->addAllowedPaymentDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->policy->buildPolicy());
	}
}
