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
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ContentSecurityPolicyNonceManagerTest extends TestCase {
	/** @var CsrfTokenManager&MockObject */
	private $CSRFTokenManager;
	/** @var Request&MockObject */
	private $request;
	/** @var ContentSecurityPolicyNonceManager */
	private $nonceManager;

	protected function setUp(): void {
		$this->CSRFTokenManager = $this->createMock(CsrfTokenManager::class);
		$this->request = $this->createMock(Request::class);
		$this->nonceManager = new ContentSecurityPolicyNonceManager(
			$this->CSRFTokenManager,
			$this->request
		);
	}

	public function testGetNonce(): void {
		$secret = base64_encode('secret');
		$tokenValue = base64_encode('secret' ^ 'value_') . ':' . $secret;
		$token = $this->createMock(CsrfToken::class);
		$token
			->expects($this->once())
			->method('getEncryptedValue')
			->willReturn($tokenValue);

		$this->CSRFTokenManager
			->expects($this->once())
			->method('getToken')
			->willReturn($token);

		$this->assertSame($secret, $this->nonceManager->getNonce());
		// call it twice but `getEncryptedValue` is expected to be called only once
		$this->assertSame($secret, $this->nonceManager->getNonce());
	}

	public function testGetNonceServerVar(): void {
		$token = 'SERVERNONCE';
		$this->request
			->method('__isset')
			->with('server')
			->willReturn(true);

		$this->request
			->method('__get')
			->with('server')
			->willReturn(['CSP_NONCE' => $token]);

		$this->assertSame($token, $this->nonceManager->getNonce());
		// call it twice but `CSP_NONCE` variable is expected to be loaded only once
		$this->assertSame($token, $this->nonceManager->getNonce());
	}
}
