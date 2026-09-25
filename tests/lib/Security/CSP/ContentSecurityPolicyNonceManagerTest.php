<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\CSP;

use OC\AppFramework\Http\Request;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\Security\CSRF\CsrfToken;
use OC\Security\CSRF\CsrfTokenManager;
use Test\TestCase;

class ContentSecurityPolicyNonceManagerTest extends TestCase {
	/** @var ContentSecurityPolicyNonceManager */
	private $nonceManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->nonceManager = $this->createInstanceWithMocks(ContentSecurityPolicyNonceManager::class);
	}

	public function testGetNonce(): void {
		$secret = base64_encode('secret');
		$tokenValue = base64_encode('secret' ^ 'value_') . ':' . $secret;
		$token = $this->createMock(CsrfToken::class);
		$token
			->expects($this->once())
			->method('getEncryptedValue')
			->willReturn($tokenValue);

		$this->mocks[CsrfTokenManager::class]
			->expects($this->once())
			->method('getToken')
			->willReturn($token);

		$this->assertSame($secret, $this->nonceManager->getNonce());
		// call it twice but `getEncryptedValue` is expected to be called only once
		$this->assertSame($secret, $this->nonceManager->getNonce());
	}

	public function testGetNonceServerVar(): void {
		$token = 'SERVERNONCE';
		$this->mocks[Request::class]
			->method('__isset')
			->with('server')
			->willReturn(true);

		$this->mocks[Request::class]
			->method('__get')
			->with('server')
			->willReturn(['CSP_NONCE' => $token]);

		$this->assertSame($token, $this->nonceManager->getNonce());
		// call it twice but `CSP_NONCE` variable is expected to be loaded only once
		$this->assertSame($token, $this->nonceManager->getNonce());
	}
}
