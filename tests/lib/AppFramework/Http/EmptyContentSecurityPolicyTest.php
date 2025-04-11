<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\EmptyContentSecurityPolicy;

/**
 * Class ContentSecurityPolicyTest
 *
 * @package OC\AppFramework\Http
 */
class EmptyContentSecurityPolicyTest extends \Test\TestCase {
	/** @var EmptyContentSecurityPolicy */
	private $contentSecurityPolicy;

	protected function setUp(): void {
		parent::setUp();
		$this->contentSecurityPolicy = new EmptyContentSecurityPolicy();
	}

	public function testGetPolicyDefault(): void {
		$defaultPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";
		$this->assertSame($defaultPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowScriptDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowScriptDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowScriptDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowScriptDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowScriptDomainMultipleStacked(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowScriptDomain('www.nextcloud.org')->disallowScriptDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptAllowEval(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src  'unsafe-eval';frame-ancestors 'none'";

		$this->contentSecurityPolicy->allowEvalScript(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyScriptAllowWasmEval(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src  'wasm-unsafe-eval';frame-ancestors 'none'";

		$this->contentSecurityPolicy->allowEvalWasm(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';style-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';style-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowStyleDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowStyleDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';style-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowStyleDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowStyleDomainMultipleStacked(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowStyleDomain('www.nextcloud.org')->disallowStyleDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleAllowInline(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';style-src  'unsafe-inline';frame-ancestors 'none'";

		$this->contentSecurityPolicy->allowInlineStyle(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleAllowInlineWithDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';style-src www.nextcloud.com 'unsafe-inline';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedStyleDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->allowInlineStyle(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyStyleDisallowInline(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->allowInlineStyle(false);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyImageDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';img-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyImageDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';img-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowImageDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowImageDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowImageDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';img-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowImageDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowImageDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedImageDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowImageDomain('www.nextcloud.org')->disallowImageDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFontDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';font-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFontDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';font-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFontDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFontDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFontDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';font-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFontDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFontDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFontDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFontDomain('www.nextcloud.org')->disallowFontDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyConnectDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';connect-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyConnectDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';connect-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowConnectDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowConnectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowConnectDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';connect-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowConnectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowConnectDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedConnectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowConnectDomain('www.nextcloud.org')->disallowConnectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyMediaDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';media-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyMediaDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';media-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowMediaDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowMediaDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowMediaDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';media-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowMediaDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowMediaDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedMediaDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowMediaDomain('www.nextcloud.org')->disallowMediaDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyObjectDomainValid(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';object-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyObjectDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';object-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowObjectDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowObjectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowObjectDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';object-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowObjectDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowObjectDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedObjectDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowObjectDomain('www.nextcloud.org')->disallowObjectDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetAllowedFrameDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyFrameDomainValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-src www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowFrameDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedFrameDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowFrameDomain('www.nextcloud.org')->disallowFrameDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetAllowedChildSrcDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';child-src child.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('child.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyChildSrcValidMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';child-src child.nextcloud.com child.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('child.nextcloud.com');
		$this->contentSecurityPolicy->addAllowedChildSrcDomain('child.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowChildSrcDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowChildSrcDomainMultiple(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';child-src www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyDisallowChildSrcDomainMultipleStakes(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedChildSrcDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->disallowChildSrcDomain('www.nextcloud.org')->disallowChildSrcDomain('www.nextcloud.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithJsNonceAndScriptDomains(): void {
		$nonce = base64_encode('MyJsNonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'nonce-$nonce' www.nextcloud.com www.nextcloud.org;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.org');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithJsNonceAndStrictDynamic(): void {
		$nonce = base64_encode('MyJsNonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'strict-dynamic' 'nonce-$nonce' www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->useStrictDynamic(true);
		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithJsNonceAndStrictDynamicAndStrictDynamicOnScripts(): void {
		$nonce = base64_encode('MyJsNonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'strict-dynamic' 'nonce-$nonce' www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->useStrictDynamic(true);
		$this->contentSecurityPolicy->useStrictDynamicOnScripts(true);
		$this->contentSecurityPolicy->useJsNonce($nonce);
		// Should be same as `testGetPolicyWithJsNonceAndStrictDynamic` because of fallback
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithJsNonceAndStrictDynamicOnScripts(): void {
		$nonce = base64_encode('MyJsNonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'nonce-$nonce' www.nextcloud.com;script-src-elem 'strict-dynamic' 'nonce-$nonce' www.nextcloud.com;frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain('www.nextcloud.com');
		$this->contentSecurityPolicy->useStrictDynamicOnScripts(true);
		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithStrictDynamicOnScripts(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->useStrictDynamicOnScripts(true);
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithJsNonceAndSelfScriptDomain(): void {
		$nonce = base64_encode('MyJsNonce');
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'nonce-$nonce';frame-ancestors 'none'";

		$this->contentSecurityPolicy->useJsNonce($nonce);
		$this->contentSecurityPolicy->addAllowedScriptDomain("'self'");
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithoutJsNonceAndSelfScriptDomain(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';script-src 'self';frame-ancestors 'none'";

		$this->contentSecurityPolicy->addAllowedScriptDomain("'self'");
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithReportUri(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none';report-uri https://my-report-uri.com";

		$this->contentSecurityPolicy->addReportTo('https://my-report-uri.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}

	public function testGetPolicyWithMultipleReportUri(): void {
		$expectedPolicy = "default-src 'none';base-uri 'none';manifest-src 'self';frame-ancestors 'none';report-uri https://my-report-uri.com https://my-other-report-uri.com";

		$this->contentSecurityPolicy->addReportTo('https://my-report-uri.com');
		$this->contentSecurityPolicy->addReportTo('https://my-other-report-uri.com');
		$this->assertSame($expectedPolicy, $this->contentSecurityPolicy->buildPolicy());
	}
}
