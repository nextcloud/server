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

	public static function noRememberMeLoginData(): array {
		return [
			'username cookie is an array' => ['cookies' => ['nc_username' => ['foo']] + self::COOKIES],
			'token cookie is an array' => ['cookies' => ['nc_token' => ['fooToken']] + self::COOKIES],
			'session id cookie is an array' => ['cookies' => ['nc_session_id' => ['sess123']] + self::COOKIES],
			'cookies removed by a logout or Apache auth earlier in the request' => ['cookies' => []],
		];
	}

	#[DataProvider(methodName: 'noRememberMeLoginData')]
	public function testNoRememberMeLogin(array $cookies): void {
		$_COOKIE = $cookies;

		$this->userSession->expects($this->never())
			->method('loginWithCookie');

		$this->assertFalse(\OC::handleLogin($this->request));
	}
}
