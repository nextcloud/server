<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Security\CSRF;

class CsrfTokenManagerTest extends \Test\TestCase {
	/** @var \OC\Security\CSRF\CsrfTokenManager */
	private $csrfTokenManager;
	/** @var \OC\Security\CSRF\CsrfTokenGenerator */
	private $tokenGenerator;
	/** @var \OC\Security\CSRF\TokenStorage\SessionStorage */
	private $storageInterface;

	protected function setUp(): void {
		parent::setUp();
		$this->tokenGenerator = $this->getMockBuilder('\OC\Security\CSRF\CsrfTokenGenerator')
			->disableOriginalConstructor()->getMock();
		$this->storageInterface = $this->getMockBuilder('\OC\Security\CSRF\TokenStorage\SessionStorage')
			->disableOriginalConstructor()->getMock();

		$this->csrfTokenManager = new \OC\Security\CSRF\CsrfTokenManager(
			$this->tokenGenerator,
			$this->storageInterface
		);
	}

	public function testGetTokenWithExistingToken() {
		$this->storageInterface
			->expects($this->once())
			->method('hasToken')
			->willReturn(true);
		$this->storageInterface
			->expects($this->once())
			->method('getToken')
			->willReturn('MyExistingToken');

		$expected = new \OC\Security\CSRF\CsrfToken('MyExistingToken');
		$this->assertEquals($expected, $this->csrfTokenManager->getToken());
	}

	public function testGetTokenWithExistingTokenKeepsOnSecondRequest() {
		$this->storageInterface
			->expects($this->once())
			->method('hasToken')
			->willReturn(true);
		$this->storageInterface
			->expects($this->once())
			->method('getToken')
			->willReturn('MyExistingToken');

		$expected = new \OC\Security\CSRF\CsrfToken('MyExistingToken');
		$token = $this->csrfTokenManager->getToken();
		$this->assertSame($token, $this->csrfTokenManager->getToken());
		$this->assertSame($token, $this->csrfTokenManager->getToken());
	}

	public function testGetTokenWithoutExistingToken() {
		$this->storageInterface
			->expects($this->once())
			->method('hasToken')
			->willReturn(false);
		$this->tokenGenerator
			->expects($this->once())
			->method('generateToken')
			->willReturn('MyNewToken');
		$this->storageInterface
			->expects($this->once())
			->method('setToken')
			->with('MyNewToken');

		$expected = new \OC\Security\CSRF\CsrfToken('MyNewToken');
		$this->assertEquals($expected, $this->csrfTokenManager->getToken());
	}

	public function testRefreshToken() {
		$this->tokenGenerator
			->expects($this->once())
			->method('generateToken')
			->willReturn('MyNewToken');
		$this->storageInterface
			->expects($this->once())
			->method('setToken')
			->with('MyNewToken');

		$expected = new \OC\Security\CSRF\CsrfToken('MyNewToken');
		$this->assertEquals($expected, $this->csrfTokenManager->refreshToken());
	}

	public function testRemoveToken() {
		$this->storageInterface
			->expects($this->once())
			->method('removeToken');

		$this->csrfTokenManager->removeToken();
	}

	public function testIsTokenValidWithoutToken() {
		$this->storageInterface
			->expects($this->once())
			->method('hasToken')
			->willReturn(false);
		$token = new \OC\Security\CSRF\CsrfToken('Token');

		$this->assertSame(false, $this->csrfTokenManager->isTokenValid($token));
	}

	public function testIsTokenValidWithWrongToken() {
		$this->storageInterface
			->expects($this->once())
			->method('hasToken')
			->willReturn(true);
		$token = new \OC\Security\CSRF\CsrfToken('Token');
		$this->storageInterface
			->expects($this->once())
			->method('getToken')
			->willReturn('MyToken');

		$this->assertSame(false, $this->csrfTokenManager->isTokenValid($token));
	}

	public function testIsTokenValidWithValidToken() {
		$a = 'abc';
		$b = 'def';
		$xorB64 = 'BQcF';
		$tokenVal = sprintf('%s:%s', $xorB64, base64_encode($a));
		$this->storageInterface
				->expects($this->once())
				->method('hasToken')
				->willReturn(true);
		$token = new \OC\Security\CSRF\CsrfToken($tokenVal);
		$this->storageInterface
				->expects($this->once())
				->method('getToken')
				->willReturn($b);

		$this->assertSame(true, $this->csrfTokenManager->isTokenValid($token));
	}
}
