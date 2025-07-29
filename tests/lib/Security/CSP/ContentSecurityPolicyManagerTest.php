<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Security\CSP;

use OC\Security\CSP\ContentSecurityPolicyManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Server;
use Test\TestCase;

class ContentSecurityPolicyManagerTest extends TestCase {
	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var ContentSecurityPolicyManager */
	private $contentSecurityPolicyManager;

	protected function setUp(): void {
		parent::setUp();
		$this->dispatcher = Server::get(IEventDispatcher::class);
		$this->contentSecurityPolicyManager = new ContentSecurityPolicyManager($this->dispatcher);
	}

	public function testAddDefaultPolicy(): void {
		$this->contentSecurityPolicyManager->addDefaultPolicy(new ContentSecurityPolicy());
		$this->addToAssertionCount(1);
	}

	public function testGetDefaultPolicyWithPolicies(): void {
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFontDomain('mydomain.com');
		$policy->addAllowedImageDomain('anotherdomain.de');
		$this->contentSecurityPolicyManager->addDefaultPolicy($policy);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFontDomain('example.com');
		$policy->addAllowedImageDomain('example.org');
		$policy->allowEvalScript(true);
		$this->contentSecurityPolicyManager->addDefaultPolicy($policy);
		$policy = new EmptyContentSecurityPolicy();
		$policy->addAllowedChildSrcDomain('childdomain');
		$policy->addAllowedFontDomain('anotherFontDomain');
		$policy->addAllowedFormActionDomain('thirdDomain');
		$this->contentSecurityPolicyManager->addDefaultPolicy($policy);

		$expected = new \OC\Security\CSP\ContentSecurityPolicy();
		$expected->allowEvalScript(true);
		$expected->addAllowedFontDomain('mydomain.com');
		$expected->addAllowedFontDomain('example.com');
		$expected->addAllowedFontDomain('anotherFontDomain');
		$expected->addAllowedFormActionDomain('thirdDomain');
		$expected->addAllowedImageDomain('anotherdomain.de');
		$expected->addAllowedImageDomain('example.org');
		$expected->addAllowedChildSrcDomain('childdomain');
		$expectedStringPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' data: blob: anotherdomain.de example.org;font-src 'self' data: mydomain.com example.com anotherFontDomain;connect-src 'self';media-src 'self';child-src childdomain;frame-ancestors 'self';form-action 'self' thirdDomain";

		$this->assertEquals($expected, $this->contentSecurityPolicyManager->getDefaultPolicy());
		$this->assertSame($expectedStringPolicy, $this->contentSecurityPolicyManager->getDefaultPolicy()->buildPolicy());
	}

	public function testGetDefaultPolicyWithPoliciesViaEvent(): void {
		$this->dispatcher->addListener(AddContentSecurityPolicyEvent::class, function (AddContentSecurityPolicyEvent $e): void {
			$policy = new ContentSecurityPolicy();
			$policy->addAllowedFontDomain('mydomain.com');
			$policy->addAllowedImageDomain('anotherdomain.de');
			$policy->useStrictDynamic(true);
			$policy->allowEvalScript(true);

			$e->addPolicy($policy);
		});

		$this->dispatcher->addListener(AddContentSecurityPolicyEvent::class, function (AddContentSecurityPolicyEvent $e): void {
			$policy = new ContentSecurityPolicy();
			$policy->addAllowedFontDomain('example.com');
			$policy->addAllowedImageDomain('example.org');
			$policy->allowEvalScript(false);
			$e->addPolicy($policy);
		});

		$this->dispatcher->addListener(AddContentSecurityPolicyEvent::class, function (AddContentSecurityPolicyEvent $e): void {
			$policy = new EmptyContentSecurityPolicy();
			$policy->addAllowedChildSrcDomain('childdomain');
			$policy->addAllowedFontDomain('anotherFontDomain');
			$policy->addAllowedFormActionDomain('thirdDomain');
			$e->addPolicy($policy);
		});

		$expected = new \OC\Security\CSP\ContentSecurityPolicy();
		$expected->allowEvalScript(true);
		$expected->addAllowedFontDomain('mydomain.com');
		$expected->addAllowedFontDomain('example.com');
		$expected->addAllowedFontDomain('anotherFontDomain');
		$expected->addAllowedImageDomain('anotherdomain.de');
		$expected->addAllowedImageDomain('example.org');
		$expected->addAllowedChildSrcDomain('childdomain');
		$expected->addAllowedFormActionDomain('thirdDomain');
		$expected->useStrictDynamic(true);
		$expectedStringPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' data: blob: anotherdomain.de example.org;font-src 'self' data: mydomain.com example.com anotherFontDomain;connect-src 'self';media-src 'self';child-src childdomain;frame-ancestors 'self';form-action 'self' thirdDomain";

		$this->assertEquals($expected, $this->contentSecurityPolicyManager->getDefaultPolicy());
		$this->assertSame($expectedStringPolicy, $this->contentSecurityPolicyManager->getDefaultPolicy()->buildPolicy());
	}
}
