<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\ContentSecurityPolicy;

/**
 * Class ContentSecurityPolicyTest
 *
 * @package OC\AppFramework\Http
 */
class ContentSecurityPolicyTest extends \Test\TestCase {
	/** @var ContentSecurityPolicy */
	private $contentSecurityPolicy;

	protected function setUp(): void {
		parent::setUp();
		$this->contentSecurityPolicy = new ContentSecurityPolicy();
	}

	public function testGetPolicyDefault(): void {
		$defaultPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";
		$this->assertSame($defaultPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' www.nextcloud.com;style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' www.nextcloud.com www.nextcloud.org;style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowScriptDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowScriptDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowScriptDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' www.nextcloud.com;style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowScriptDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowScriptDomainMultipleStacked(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowScriptDomain('www.nextcloud.org')->disallowScriptDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptDisallowEval(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->allowEvalScript(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' www.nextcloud.com 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' www.nextcloud.com www.nextcloud.org 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowStyleDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowStyleDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' www.nextcloud.com 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowStyleDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowStyleDomainMultipleStacked(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowStyleDomain('www.nextcloud.org')->disallowStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleAllowInline(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->allowInlineStyle(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleAllowInlineWithDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' www.nextcloud.com 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleDisallowInline(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->allowInlineStyle(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyImageDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob: www.nextcloud.com;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyImageDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob: www.nextcloud.com www.nextcloud.org;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowImageDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowImageDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowImageDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob: www.nextcloud.com;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowImageDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowImageDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowImageDomain('www.nextcloud.org')->disallowImageDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFontDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data: www.nextcloud.com;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFontDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data: www.nextcloud.com www.nextcloud.org;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFontDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFontDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFontDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data: www.nextcloud.com;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFontDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFontDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFontDomain('www.nextcloud.org')->disallowFontDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyConnectDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self' www.nextcloud.com;media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyConnectDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self' www.nextcloud.com www.nextcloud.org;media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowConnectDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowConnectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowConnectDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self' www.nextcloud.com;media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowConnectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowConnectDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowConnectDomain('www.nextcloud.org')->disallowConnectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyMediaDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self' www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyMediaDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self' www.nextcloud.com www.nextcloud.org;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowMediaDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowMediaDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowMediaDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self' www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowMediaDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowMediaDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowMediaDomain('www.nextcloud.org')->disallowMediaDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyObjectDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';object-src www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyObjectDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';object-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowObjectDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowObjectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowObjectDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';object-src www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowObjectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowObjectDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowObjectDomain('www.nextcloud.org')->disallowObjectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetAllowedFrameDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-src www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFrameDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-src www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameDomain('www.nextcloud.org')->disallowFrameDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetAllowedChildSrcDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';child-src child.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('child.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyChildSrcValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';child-src child.nextcloud.com child.nextcloud.org;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('child.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedChildSrcDomain('child.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowChildSrcDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowChildSrcDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';child-src www.nextcloud.com;frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowChildSrcDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.org')->disallowChildSrcDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}



	public function testGetAllowedFrameAncestorDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self' sub.nextcloud.com;form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameAncestorDomain('sub.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFrameAncestorValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self' sub.nextcloud.com foo.nextcloud.com;form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameAncestorDomain('sub.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedFrameAncestorDomain('foo.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameAncestorDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameAncestorDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameAncestorDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameAncestorDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self' www.nextcloud.com;form-action 'self'";

		$this->contentSecurityPolicy->addAllowedFrameAncestorDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameAncestorDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameAncestorDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.org')->disallowChildSrcDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyUnsafeEval(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' 'unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->allowEvalScript(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyUnsafeWasmEval(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self' 'wasm-unsafe-eval';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->allowEvalWasm(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyNonce(): void {
		$nonce = base64_encode('my-nonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'nonce-$nonce';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->contentSecurityPolicy->useStrictDynamicOnScripts(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyNonceDefault(): void {
		$nonce = base64_encode('my-nonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'nonce-$nonce';script-src-elem 'strict-dynamic' 'nonce-$nonce';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyNonceStrictDynamic(): void {
		$nonce = base64_encode('my-nonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'strict-dynamic' 'nonce-$nonce';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->contentSecurityPolicy->useStrictDynamic(true);
		$this->contentSecurityPolicy->useStrictDynamicOnScripts(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyNonceStrictDynamicDefault(): void {
		$nonce = base64_encode('my-nonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'strict-dynamic' 'nonce-$nonce';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->contentSecurityPolicy->useStrictDynamic(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStrictDynamicOnScriptsOff(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->useStrictDynamicOnScripts(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStrictDynamicAndStrictDynamicOnScripts(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';style-src 'self' 'unsafe-inline';img-src 'self' data: blob:;font-src 'self' data:;connect-src 'self';media-src 'self';frame-ancestors 'self';form-action 'self'";

		$this->contentSecurityPolicy->useStrictDynamic(true);
		$this->contentSecurityPolicy->useStrictDynamicOnScripts(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}
}
