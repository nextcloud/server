<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\User\Session;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * The remember-me cookie login of OC::handleLogin()
 */
class OCHandleLoginTest extends TestCase {
	private Session&MockObject $userSession;
	private IRequest&MockObject $request;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->overwriteService(Session::class, $this->userSession);
		$this->overwriteService(IThrottler::class, $this->createMock(IThrottler::class));
		$this->request = $this->createMock(IRequest::class);
	}

	public function testLoginWithRememberMeCookies(): void {
		$this->userSession->method('getRememberMeCookies')
			->willReturn(['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123']);

		$this->userSession->expects($this->once())
			->method('loginWithCookie')
			->with('foo', 'fooToken', 'sess123')
			->willReturn(true);

		$this->assertTrue(\OC::handleLogin($this->request));
	}

	public function testNoRememberMeLoginWithoutTheCookies(): void {
		// Missing, not strings, or removed by a logout or Apache auth earlier in the request
		$this->userSession->method('getRememberMeCookies')
			->willReturn(null);

		$this->userSession->expects($this->never())
			->method('loginWithCookie');

		$this->assertFalse(\OC::handleLogin($this->request));
	}
}
