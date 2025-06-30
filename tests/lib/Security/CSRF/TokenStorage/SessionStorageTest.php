<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Security\CSRF\TokenStorage;

use OC\Security\CSRF\TokenStorage\SessionStorage;
use OCP\ISession;

class SessionStorageTest extends \Test\TestCase {
	/** @var \OCP\ISession */
	private $session;
	/** @var \OC\Security\CSRF\TokenStorage\SessionStorage */
	private $sessionStorage;

	protected function setUp(): void {
		parent::setUp();
		$this->session = $this->getMockBuilder(ISession::class)
			->disableOriginalConstructor()->getMock();
		$this->sessionStorage = new SessionStorage($this->session);
	}

	/**
	 * @return array
	 */
	public static function getTokenDataProvider(): array {
		return [
			[
				'',
			],
			[
				null,
			],
		];
	}

	/**
	 * @param string $token
	 * @dataProvider getTokenDataProvider
	 *
	 */
	public function testGetTokenWithEmptyToken($token): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Session does not contain a requesttoken');

		$this->session
			->expects($this->once())
			->method('get')
			->with('requesttoken')
			->willReturn($token);
		$this->sessionStorage->getToken();
	}

	public function testGetTokenWithValidToken(): void {
		$this->session
			->expects($this->once())
			->method('get')
			->with('requesttoken')
			->willReturn('MyFancyCsrfToken');
		$this->assertSame('MyFancyCsrfToken', $this->sessionStorage->getToken());
	}

	public function testSetToken(): void {
		$this->session
			->expects($this->once())
			->method('set')
			->with('requesttoken', 'TokenToSet');
		$this->sessionStorage->setToken('TokenToSet');
	}

	public function testRemoveToken(): void {
		$this->session
			->expects($this->once())
			->method('remove')
			->with('requesttoken');
		$this->sessionStorage->removeToken();
	}

	public function testHasTokenWithExistingToken(): void {
		$this->session
			->expects($this->once())
			->method('exists')
			->with('requesttoken')
			->willReturn(true);
		$this->assertSame(true, $this->sessionStorage->hasToken());
	}

	public function testHasTokenWithoutExistingToken(): void {
		$this->session
			->expects($this->once())
			->method('exists')
			->with('requesttoken')
			->willReturn(false);
		$this->assertSame(false, $this->sessionStorage->hasToken());
	}

	public function testSetSession(): void {
		$session = $this->createMock(ISession::class);
		$session
			->expects($this->once())
			->method('get')
			->with('requesttoken')
			->willReturn('MyToken');
		$this->sessionStorage->setSession($session);
		$this->assertSame('MyToken', $this->sessionStorage->getToken());
	}
}
