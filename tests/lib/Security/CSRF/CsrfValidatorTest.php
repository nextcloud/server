<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\CSRF;

use OC\Security\CSRF\CsrfTokenManager;
use OC\Security\CSRF\CsrfValidator;
use OCP\IRequest;
use Test\TestCase;

class CsrfValidatorTest extends TestCase {
	private CsrfTokenManager $csrfTokenManager;
	private CsrfValidator $csrfValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->csrfTokenManager = $this->createMock(CsrfTokenManager::class);
		$this->csrfValidator = new CsrfValidator($this->csrfTokenManager);
	}

	public function testFailStrictCookieCheck(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('passesStrictCookieCheck')
			->willReturn(false);

		$this->assertFalse($this->csrfValidator->validate($request));
	}

	public function testFailMissingToken(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('passesStrictCookieCheck')
			->willReturn(true);
		$request->method('getParam')
			->with('requesttoken', '')
			->willReturn('');
		$request->method('getHeader')
			->with('REQUESTTOKEN')
			->willReturn('');

		$this->assertFalse($this->csrfValidator->validate($request));
	}

	public function testFailInvalidToken(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('passesStrictCookieCheck')
			->willReturn(true);
		$request->method('getParam')
			->with('requesttoken', '')
			->willReturn('token123');
		$request->method('getHeader')
			->with('REQUESTTOKEN')
			->willReturn('');

		$this->csrfTokenManager
			->method('isTokenValid')
			->willReturn(false);

		$this->assertFalse($this->csrfValidator->validate($request));
	}

	public function testPass(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('passesStrictCookieCheck')
			->willReturn(true);
		$request->method('getParam')
			->with('requesttoken', '')
			->willReturn('token123');
		$request->method('getHeader')
			->with('REQUESTTOKEN')
			->willReturn('');

		$this->csrfTokenManager
			->method('isTokenValid')
			->willReturn(true);

		$this->assertTrue($this->csrfValidator->validate($request));
	}
}
