<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Session;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\PublicKeyTokenProvider;
use OC\Session\SessionIdRenewer;
use OC\User\Session as UserSession;
use OCP\Authentication\Token\IToken;
use OCP\ISession;
use OCP\Server;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\Session\ISessionIdRenewer;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
final class SessionIdRenewerTest extends TestCase {
	private const OLD_ID = 'old-session-id-0123456789abcdef';
	private const NEW_ID = 'new-session-id-0123456789abcdef';

	private UserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private string $currentId = self::OLD_ID;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->currentId = self::OLD_ID;
		$this->session = $this->createMock(ISession::class);
		$this->session->method('getId')
			->willReturnCallback(fn (): string => $this->currentId);
		$this->userSession = $this->createMock(UserSession::class);
		$this->userSession->method('getSession')
			->willReturn($this->session);
	}

	#[\Override]
	protected function tearDown(): void {
		$provider = Server::get(PublicKeyTokenProvider::class);
		$provider->invalidateToken(self::OLD_ID);
		$provider->invalidateToken(self::NEW_ID);
		parent::tearDown();
	}

	private function expectRegenerate(): void {
		$this->session->expects($this->once())
			->method('regenerateId')
			->with(true, false)
			->willReturnCallback(function (): void {
				$this->currentId = self::NEW_ID;
			});
	}

	public function testIsRegistered(): void {
		$this->assertInstanceOf(SessionIdRenewer::class, Server::get(ISessionIdRenewer::class));
	}

	public function testKeepsTheTokenUnderTheNewId(): void {
		$provider = Server::get(PublicKeyTokenProvider::class);
		$token = $provider->generateToken(self::OLD_ID, 'renew-user', 'renew-login', 'secret password', 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
		$this->expectRegenerate();
		$this->userSession->expects($this->once())
			->method('renewMagicSessionId')
			->with(self::OLD_ID);

		(new SessionIdRenewer($this->userSession, $provider))->renewSessionId();

		$renewed = $provider->getToken(self::NEW_ID);
		$this->assertSame($token->getId(), $renewed->getId());
		$this->assertSame('Firefox', $renewed->getName());
		$this->assertSame(IToken::REMEMBER, $renewed->getRemember());
		$this->assertSame('secret password', $provider->getPassword($renewed, self::NEW_ID));
		$this->expectException(InvalidTokenException::class);
		$provider->getToken(self::OLD_ID);
	}

	public function testMovesTheTokenBeforeTheRememberMeCookie(): void {
		$token = $this->createMock(PublicKeyToken::class);
		$provider = $this->createMock(IProvider::class);
		$provider->method('getToken')
			->with(self::OLD_ID)
			->willReturn($token);
		$this->expectRegenerate();
		$calls = [];
		$provider->expects($this->once())
			->method('rotate')
			->with($token, self::OLD_ID, self::NEW_ID)
			->willReturnCallback(function () use (&$calls, $token): PublicKeyToken {
				$calls[] = 'rotate';
				return $token;
			});
		$provider->expects($this->once())
			->method('invalidateToken')
			->with(self::OLD_ID)
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'forget';
			});
		$this->userSession->expects($this->once())
			->method('renewMagicSessionId')
			->with(self::OLD_ID)
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'cookie';
			});

		(new SessionIdRenewer($this->userSession, $provider))->renewSessionId();

		$this->assertSame(['rotate', 'forget', 'cookie'], $calls);
	}

	public function testRenewsTheIdOfASessionWithoutToken(): void {
		$provider = $this->createMock(IProvider::class);
		$provider->method('getToken')
			->willThrowException(new InvalidTokenException());
		$this->expectRegenerate();
		$provider->expects($this->never())->method('rotate');
		$provider->expects($this->never())->method('invalidateToken');
		$this->userSession->expects($this->never())->method('renewMagicSessionId');

		(new SessionIdRenewer($this->userSession, $provider))->renewSessionId();

		$this->assertSame(self::NEW_ID, $this->currentId);
	}

	public function testWithoutSessionNothingChanges(): void {
		$session = $this->createMock(ISession::class);
		$session->method('getId')
			->willThrowException(new SessionNotAvailableException());
		$session->expects($this->never())->method('regenerateId');
		$userSession = $this->createMock(UserSession::class);
		$userSession->method('getSession')->willReturn($session);
		$provider = $this->createMock(IProvider::class);
		$provider->expects($this->never())->method('rotate');

		$this->expectException(SessionNotAvailableException::class);
		(new SessionIdRenewer($userSession, $provider))->renewSessionId();
	}
}
