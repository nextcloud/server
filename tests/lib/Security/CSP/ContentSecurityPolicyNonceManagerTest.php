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
	/** @var CsrfTokenManager */
	private $csrfTokenManager;
	/** @var Request */
	private $request;
	/** @var ContentSecurityPolicyNonceManager */
	private $nonceManager;

	protected function setUp(): void {
		$this->csrfTokenManager = $this->createMock(CsrfTokenManager::class);
		$this->request = $this->createMock(Request::class);
		$this->nonceManager = new ContentSecurityPolicyNonceManager(
			$this->csrfTokenManager,
			$this->request
		);
	}

	public function testGetNonce() {
		$token = $this->createMock(CsrfToken::class);
		$token
			->expects($this->once())
			->method('getEncryptedValue')
			->willReturn('MyToken');

		$this->csrfTokenManager
			->expects($this->once())
			->method('getToken')
			->willReturn($token);

		$this->assertSame('TXlUb2tlbg==', $this->nonceManager->getNonce());
		$this->assertSame('TXlUb2tlbg==', $this->nonceManager->getNonce());
	}

	public function testGetNonceServerVar() {
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
		$this->assertSame($token, $this->nonceManager->getNonce());
	}
}
