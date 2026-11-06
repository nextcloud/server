<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\PublicKeyTokenMapper;
use OC\Authentication\Token\PublicKeyTokenProvider;
use OCP\Authentication\Token\IToken;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

/**
 * The tokens of one login, with the real mapper and database
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class PublicKeyTokenProviderLoginTest extends TestCase {
	private const UID = 'login-test-user';

	private PublicKeyTokenProvider $tokenProvider;
	/** Keeps the session ids of each test apart in the token cache of the shared provider */
	private string $sessionPrefix;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->deleteTokens();
		$this->tokenProvider = Server::get(PublicKeyTokenProvider::class);
		$this->sessionPrefix = bin2hex(random_bytes(4));
	}

	#[\Override]
	protected function tearDown(): void {
		$this->deleteTokens();

		parent::tearDown();
	}

	public function testRevokingOneSessionTokenEndsTheWholeLogin(): void {
		$first = $this->login('session0');
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session1'));
		$sibling = $this->addSessionToken($first, 'session2');
		$this->addSessionToken($first, 'session3');
		$otherLogin = $this->login('session9');
		$appPassword = $this->tokenProvider->generateToken($this->sessionId('appPassword'), self::UID, self::UID, 'password', 'Desktop client', IToken::PERMANENT_TOKEN);

		$this->assertEqualsCanonicalizing([$first->getId(), $otherLogin->getId(), $appPassword->getId()], array_keys($this->tokenProvider->getLoginsOfUser(self::UID)));
		$this->assertCount(3, $this->tokensOfLogin($first->getId()));

		$this->tokenProvider->invalidateTokenById(self::UID, $sibling->getId());

		$this->assertNull($this->tokenProvider->getLoginOfUser(self::UID, $first->getId()));
		foreach (['session0', 'session1', 'session2', 'session3'] as $session) {
			$this->assertFalse($this->hasToken($session), $session . ' must not be usable anymore');
		}
		$this->assertTrue($this->hasToken('session9'));
		$this->assertTrue($this->hasToken('appPassword'));
	}

	public function testLogoutEndsTheWholeLogin(): void {
		$first = $this->login('session0');
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session1'));
		$this->addSessionToken($first, 'session2');

		$this->tokenProvider->invalidateToken($this->sessionId('session2'));

		$this->assertFalse($this->hasToken('session1'));
		$this->assertFalse($this->hasToken('session2'));
	}

	public function testInvalidatingTheOtherTokensOfALoginKeepsTheGivenOne(): void {
		$first = $this->login('session0');
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session1'));
		$current = $this->addSessionToken($first, 'session2');
		$this->login('session9');

		$this->tokenProvider->invalidateOtherTokensOfLogin($current);

		$this->assertFalse($this->hasToken('session1'));
		$this->assertTrue($this->hasToken('session2'));
		$this->assertTrue($this->hasToken('session9'));
	}

	public function testUpdatingALoginRenamesAndRestrictsEveryTokenOfIt(): void {
		$first = $this->login('session0');
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session1'));
		$this->addSessionToken($first, 'session2');
		$this->login('session9');

		$this->tokenProvider->updateLogin(self::UID, $first->getId(), 'Laptop', [IToken::SCOPE_FILESYSTEM => false]);

		$tokens = $this->tokensOfLogin($first->getId());
		$this->assertCount(2, $tokens);
		foreach ($tokens as $token) {
			$this->assertSame('Laptop', $token->getName());
			$this->assertSame([IToken::SCOPE_FILESYSTEM => false], $token->getScopeAsArray());
		}
		$this->assertSame('Firefox', $this->tokenProvider->getToken($this->sessionId('session9'))->getName());
	}

	public function testTheLoginKeepsItsIdWhileItsSessionTokenIsRenewed(): void {
		$first = $this->login('session0');
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session1'));
		$this->tokenProvider->renewSessionToken($this->sessionId('session1'), $this->sessionId('session2'));

		$this->assertSame([$first->getId()], array_keys($this->tokenProvider->getLoginsOfUser(self::UID)));

		$this->tokenProvider->invalidateTokenById(self::UID, $first->getId());

		$this->assertFalse($this->hasToken('session2'));
	}

	public function testOnlyOneRequestRenewsASessionToken(): void {
		$this->login('session0');
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session1'));

		$this->expectException(InvalidTokenException::class);
		$this->tokenProvider->renewSessionToken($this->sessionId('session0'), $this->sessionId('session2'));
	}

	public function testWipeMarksTheWholeLogin(): void {
		$first = $this->login('session0');
		$sibling = $this->addSessionToken($first, 'session1');
		$this->tokenProvider->generateToken($this->sessionId('appPassword'), self::UID, self::UID, 'password', 'Desktop client', IToken::PERMANENT_TOKEN);

		$this->tokenProvider->markForWipe($sibling);

		$tokens = $this->tokensOfLogin($first->getId());
		$this->assertCount(2, $tokens);
		foreach ($tokens as $token) {
			$this->assertSame(IToken::WIPE_TOKEN, $token->getType());
		}
		$this->assertSame(IToken::PERMANENT_TOKEN, $this->tokenProvider->getToken($this->sessionId('appPassword'))->getType());
	}

	private function login(string $session): IToken {
		return $this->tokenProvider->generateToken($this->sessionId($session), self::UID, self::UID, 'password', 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
	}

	/**
	 * Another session token in the login of $loginToken
	 */
	private function addSessionToken(IToken $loginToken, string $session): IToken {
		$token = $this->login($session);
		$this->assertInstanceOf(PublicKeyToken::class, $token);
		$token->setLoginId($this->tokenProvider->getLoginId($loginToken));
		return Server::get(PublicKeyTokenMapper::class)->update($token);
	}

	/**
	 * @return list<PublicKeyToken>
	 */
	private function tokensOfLogin(int $loginId): array {
		return Server::get(PublicKeyTokenMapper::class)->getTokensOfLogin(self::UID, $loginId);
	}

	private function hasToken(string $session): bool {
		try {
			$this->tokenProvider->getToken($this->sessionId($session));
			return true;
		} catch (InvalidTokenException) {
			return false;
		}
	}

	/**
	 * A session id that passes the minimum token length
	 */
	private function sessionId(string $name): string {
		return str_pad($this->sessionPrefix . $name, 32, 'x');
	}

	private function deleteTokens(): void {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->delete('authtoken')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter(self::UID)))
			->executeStatement();
	}
}
