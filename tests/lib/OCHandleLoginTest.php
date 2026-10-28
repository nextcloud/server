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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * The remember-me cookie login of OC::handleLogin()
 */
class OCHandleLoginTest extends TestCase {
	private const COOKIES = ['nc_username' => 'foo', 'nc_token' => 'fooToken', 'nc_session_id' => 'sess123'];

	private Session&MockObject $userSession;
	private IRequest&MockObject $request;
	private array $cookiesBefore;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cookiesBefore = $_COOKIE;
		$this->userSession = $this->createMock(Session::class);
		$this->overwriteService(Session::class, $this->userSession);
		$this->overwriteService(IThrottler::class, $this->createMock(IThrottler::class));
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getHeader')->willReturn('');
		// The cookies as the request came in
		$this->request->method('getCookie')->willReturnCallback(fn (string $name) => self::COOKIES[$name] ?? null);
	}

	#[\Override]
	protected function tearDown(): void {
		$_COOKIE = $this->cookiesBefore;

		parent::tearDown();
	}

	public function testLoginWithRememberMeCookies(): void {
		$_COOKIE = self::COOKIES;

		$this->userSession->expects($this->once())
			->method('loginWithCookie')
			->with('foo', 'fooToken', 'sess123')
			->willReturn(true);

		$this->assertTrue(\OC::handleLogin($this->request));
	}

	public static function cookieAsArrayData(): array {
		return [
			'username' => ['name' => 'nc_username'],
			'token' => ['name' => 'nc_token'],
			'session id' => ['name' => 'nc_session_id'],
		];
	}

	#[DataProvider(methodName: 'cookieAsArrayData')]
	public function testRememberMeCookieAsArray(string $name): void {
		$_COOKIE = [$name => [self::COOKIES[$name]]] + self::COOKIES;

		$this->userSession->expects($this->never())
			->method('loginWithCookie');

		$this->assertFalse(\OC::handleLogin($this->request));
	}

	public function testRememberMeCookiesRemovedEarlierInTheRequest(): void {
		// A logout or the Apache auth removed them from $_COOKIE, but the request still carries them
		$_COOKIE = [];

		$this->userSession->expects($this->never())
			->method('loginWithCookie');

		$this->assertFalse(\OC::handleLogin($this->request));
	}
}
