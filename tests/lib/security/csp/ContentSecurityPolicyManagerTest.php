<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Security\CSP;


use OC\Security\CSP\ContentSecurityPolicyManager;

class ContentSecurityPolicyManagerTest extends \Test\TestCase {
	/** @var ContentSecurityPolicyManager */
	private $contentSecurityPolicyManager;

	public function setUp() {
		parent::setUp();
		$this->contentSecurityPolicyManager = new ContentSecurityPolicyManager();
	}

	public function testAddDefaultPolicy() {
		$this->contentSecurityPolicyManager->addDefaultPolicy(new \OCP\AppFramework\Http\ContentSecurityPolicy());
	}

	public function testGetDefaultPolicyWithPolicies() {
		$policy = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$policy->addAllowedFontDomain('mydomain.com');
		$policy->addAllowedImageDomain('anotherdomain.de');
		$this->contentSecurityPolicyManager->addDefaultPolicy($policy);
		$policy = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$policy->addAllowedFontDomain('example.com');
		$policy->addAllowedImageDomain('example.org');
		$policy->allowInlineScript(true);
		$this->contentSecurityPolicyManager->addDefaultPolicy($policy);
		$policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();
		$policy->addAllowedChildSrcDomain('childdomain');
		$policy->addAllowedFontDomain('anotherFontDomain');
		$this->contentSecurityPolicyManager->addDefaultPolicy($policy);

		$expected = new \OC\Security\CSP\ContentSecurityPolicy();
		$expected->allowInlineScript(true);
		$expected->addAllowedFontDomain('mydomain.com');
		$expected->addAllowedFontDomain('example.com');
		$expected->addAllowedFontDomain('anotherFontDomain');
		$expected->addAllowedImageDomain('anotherdomain.de');
		$expected->addAllowedImageDomain('example.org');
		$expected->addAllowedChildSrcDomain('childdomain');
		$expectedStringPolicy = 'default-src \'none\';script-src \'self\' \'unsafe-inline\' \'unsafe-eval\';style-src \'self\' \'unsafe-inline\';img-src \'self\' data: blob: anotherdomain.de example.org;font-src \'self\' mydomain.com example.com anotherFontDomain;connect-src \'self\';media-src \'self\';child-src childdomain';

		$this->assertEquals($expected, $this->contentSecurityPolicyManager->getDefaultPolicy());
		$this->assertSame($expectedStringPolicy, $this->contentSecurityPolicyManager->getDefaultPolicy()->buildPolicy());
	}

}
