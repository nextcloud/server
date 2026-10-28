<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\PublicKeyTokenMapper;
use OC\Authentication\Token\PublicKeyTokenProvider;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Events\TokenInvalidatedEvent;
use OCP\Authentication\Exceptions\InvalidTokenException as OCPInvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use OCP\Security\IHasher;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PublicKeyTokenProviderTest extends TestCase {
	private PublicKeyTokenProvider $tokenProvider;

	private PublicKeyTokenMapper&MockObject $mapper;
	private IConfig&MockObject $config;
	private IDBConnection&MockObject $db;
	private LoggerInterface&MockObject $logger;
	private ITimeFactory&MockObject $timeFactory;
	private ICacheFactory&MockObject $cacheFactory;

	private int $time;
	private IHasher $hasher;
	private ICrypto $crypto;
	private IEventDispatcher $eventDispatcher;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(PublicKeyTokenMapper::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->willReturnMap([
				['openssl', [], []],
			]);
		$this->config->method('getSystemValueString')
			->willReturnMap([
				['secret', '', '1f4h9s'],
			]);
		$this->db = $this->createMock(IDBConnection::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->time = 1313131;
		$this->timeFactory->method('getTime')
			->willReturn($this->time);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);

		$this->hasher = Server::get(IHasher::class);
		$this->crypto = Server::get(ICrypto::class);
		$this->eventDispatcher = Server::get(IEventDispatcher::class);

		$this->tokenProvider = new PublicKeyTokenProvider(
			$this->mapper,
			$this->crypto,
			$this->config,
			$this->db,
			$this->logger,
			$this->timeFactory,
			$this->hasher,
			$this->cacheFactory,
			$this->eventDispatcher,
		);
	}

	public function testGenerateToken(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;

		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->assertInstanceOf(PublicKeyToken::class, $actual);
		$this->assertSame($uid, $actual->getUID());
		$this->assertSame($user, $actual->getLoginName());
		$this->assertSame($name, $actual->getName());
		$this->assertSame(IToken::DO_NOT_REMEMBER, $actual->getRemember());
		$this->assertSame($password, $this->tokenProvider->getPassword($actual, $token));
	}

	public function testGenerateTokenNoPassword(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, false],
			]);
		$this->expectException(PasswordlessTokenException::class);

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->assertInstanceOf(PublicKeyToken::class, $actual);
		$this->assertSame($uid, $actual->getUID());
		$this->assertSame($user, $actual->getLoginName());
		$this->assertSame($name, $actual->getName());
		$this->assertSame(IToken::DO_NOT_REMEMBER, $actual->getRemember());
		$this->tokenProvider->getPassword($actual, $token);
	}

	public function testGenerateTokenLongPassword(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = '';
		for ($i = 0; $i < 500; $i++) {
			$password .= 'e';
		}
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$this->expectException(\RuntimeException::class);

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);
	}

	public function testGenerateTokenInvalidName(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12'
			. 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12'
			. 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12'
			. 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->assertInstanceOf(PublicKeyToken::class, $actual);
		$this->assertSame($uid, $actual->getUID());
		$this->assertSame($user, $actual->getLoginName());
		$this->assertSame('User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12User-Agent: Mozill…', $actual->getName());
		$this->assertSame(IToken::DO_NOT_REMEMBER, $actual->getRemember());
		$this->assertSame($password, $this->tokenProvider->getPassword($actual, $token));
	}

	public function testUpdateToken(): void {
		$tk = new PublicKeyToken();
		$this->mapper->expects($this->once())
			->method('updateActivity')
			->with($tk, $this->time);
		$tk->setLastActivity($this->time - 200);
		$tk->setToken('token');
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);

		$this->tokenProvider->updateTokenActivity($tk);

		$this->assertEquals($this->time, $tk->getLastActivity());
	}

	public function testUpdateTokenDebounce(): void {
		$tk = new PublicKeyToken();
		$this->config->method('getSystemValueInt')
			->willReturnCallback(function ($value, $default) {
				return $default;
			});
		$tk->setLastActivity($this->time - 30);

		$this->mapper->expects($this->never())
			->method('updateActivity')
			->with($tk, $this->time);

		$this->tokenProvider->updateTokenActivity($tk);
	}

	public function testGetTokenByUser(): void {
		$this->mapper->expects($this->once())
			->method('getTokenByUser')
			->with('uid')
			->willReturn(['token']);

		$this->assertEquals(['token'], $this->tokenProvider->getTokenByUser('uid'));
	}

	public function testGetPassword(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->assertSame($password, $this->tokenProvider->getPassword($actual, $token));
	}

	public function testGetPasswordPasswordLessToken(): void {
		$this->expectException(PasswordlessTokenException::class);

		$token = 'token1234';
		$tk = new PublicKeyToken();
		$tk->setPassword(null);

		$this->tokenProvider->getPassword($tk, $token);
	}

	public function testGetPasswordInvalidToken(): void {
		$this->expectException(InvalidTokenException::class);

		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;

		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->tokenProvider->getPassword($actual, 'wrongtoken');
	}

	public function testSetPassword(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->mapper->method('getTokenByUser')
			->with('user')
			->willReturn([$actual]);

		$newpass = 'newpass';
		$this->mapper->expects($this->once())
			->method('update')
			->with($this->callback(function ($token) use ($newpass) {
				return $newpass === $this->tokenProvider->getPassword($token, 'tokentokentokentokentoken');
			}));

		$this->tokenProvider->setPassword($actual, $token, $newpass);

		$this->assertSame($newpass, $this->tokenProvider->getPassword($actual, 'tokentokentokentokentoken'));
	}

	public function testSetPasswordInvalidToken(): void {
		$this->expectException(InvalidTokenException::class);

		$token = $this->createMock(IToken::class);
		$tokenId = 'token123';
		$password = '123456';

		$this->tokenProvider->setPassword($token, $tokenId, $password);
	}

	public function testInvalidateToken(): void {
		$token = $this->loginToken(42, null, hash('sha512', 'token7' . '1f4h9s'));
		$sibling = $this->loginToken(43, 42);
		$this->mapper->method('getToken')
			->with(hash('sha512', 'token7' . '1f4h9s'))
			->willReturn($token);
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->with('user', 42, true)
			->willReturn([$token, $sibling]);
		$deleted = $this->recordDeletedTokens();
		// A token stored while the instance had no secret
		$this->mapper->expects($this->once())
			->method('invalidate')
			->with(hash('sha512', 'token7'));

		$this->tokenProvider->invalidateToken('token7');

		$this->assertSame([42, 43], $deleted->getArrayCopy());
	}

	public function testInvalidateTokenThatIsNotStored(): void {
		$this->mapper->method('getToken')
			->willThrowException(new DoesNotExistException('nope'));
		$this->mapper->expects($this->never())
			->method('getTokensOfLogin');
		$this->mapper->expects($this->once())
			->method('invalidate')
			->with(hash('sha512', 'token7'));

		$this->tokenProvider->invalidateToken('token7');
	}

	public function testInvalidateTokenById(): void {
		$this->mapper->method('getTokenById')
			->with(43)
			->willReturn($this->loginToken(43, 42));
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->with('uid', 42, true)
			->willReturn([$this->loginToken(42, null, 'hash42', 'uid'), $this->loginToken(43, 42, 'hash43', 'uid')]);
		$deleted = $this->recordDeletedTokens();
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->with($this->isInstanceOf(TokenInvalidatedEvent::class));
		$tokenProvider = new PublicKeyTokenProvider($this->mapper, $this->crypto, $this->config, $this->db, $this->logger, $this->timeFactory, $this->hasher, $this->cacheFactory, $eventDispatcher);

		$tokenProvider->invalidateTokenById('uid', 43);

		$this->assertSame([42, 43], $deleted->getArrayCopy());
	}

	public function testInvalidateTokenByIdOfALoginWhoseFirstTokenIsGone(): void {
		$this->mapper->method('getTokenById')
			->with(42)
			->willThrowException(new DoesNotExistException('nope'));
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->with('uid', 42, true)
			->willReturn([$this->loginToken(43, 42, 'hash43', 'uid')]);
		$deleted = $this->recordDeletedTokens();

		$this->tokenProvider->invalidateTokenById('uid', 42);

		$this->assertSame([43], $deleted->getArrayCopy());
	}

	public function testInvalidateTokenByIdOfAnInvalidatedLogin(): void {
		$this->mapper->method('getTokenById')
			->willThrowException(new DoesNotExistException('nope'));
		$this->mapper->method('getTokensOfLogin')
			->willReturn([]);
		$this->mapper->expects($this->never())
			->method('delete');

		$this->tokenProvider->invalidateTokenById('uid', 42);
	}

	public function testInvalidateTokenByIdAlsoInvalidatesTokensStoredWhileWaitingForTheLocks(): void {
		$this->mapper->method('getTokenById')
			->willReturn($this->loginToken(42, null, 'hash42', 'uid'));
		// A copy committed while the first scan waited for the lock of its source
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->willReturnOnConsecutiveCalls(
				[$this->loginToken(42, null, 'hash42', 'uid')],
				[$this->loginToken(42, null, 'hash42', 'uid'), $this->loginToken(44, 42, 'hash44', 'uid')],
			);
		$deleted = $this->recordDeletedTokens();

		$this->tokenProvider->invalidateTokenById('uid', 42);

		$this->assertSame([42, 44], $deleted->getArrayCopy());
	}

	public function testInvalidateOtherTokensOfLogin(): void {
		$current = $this->loginToken(43, 42, 'hash43', 'uid');
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->with('uid', 42, true)
			->willReturn([$this->loginToken(42, null, 'hash42', 'uid'), $current, $this->loginToken(44, 42, 'hash44', 'uid')]);
		$deleted = $this->recordDeletedTokens();
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->with($this->isInstanceOf(TokenInvalidatedEvent::class));
		$tokenProvider = new PublicKeyTokenProvider($this->mapper, $this->crypto, $this->config, $this->db, $this->logger, $this->timeFactory, $this->hasher, $this->cacheFactory, $eventDispatcher);

		$tokenProvider->invalidateOtherTokensOfLogin($current);

		$this->assertSame([42, 44], $deleted->getArrayCopy());
	}

	public function testGetLoginId(): void {
		$this->assertSame(42, $this->tokenProvider->getLoginId($this->loginToken(43, 42)));
		$this->assertSame(42, $this->tokenProvider->getLoginId($this->loginToken(42, null)));
	}

	public function testGetLoginIdOfAnotherTokenType(): void {
		$this->expectException(InvalidTokenException::class);

		$this->tokenProvider->getLoginId($this->createMock(IToken::class));
	}

	public function testRenewedAndCopiedTokensAreCachedOnlyOnceStored(): void {
		// The renewal or copy may be part of a transaction that is still rolled back
		$cache = $this->createMock(ICache::class);
		$cached = [];
		$cache->method('set')
			->willReturnCallback(function (string $key, mixed $value) use (&$cached): bool {
				$cached[$key] = $value;
				return true;
			});
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createInMemory')->willReturn($cache);
		$tokenProvider = new PublicKeyTokenProvider($this->mapper, $this->crypto, $this->config, $this->db, $this->logger, $this->timeFactory, $this->hasher, $cacheFactory, $this->eventDispatcher);
		$source = $tokenProvider->generateToken('sourceIdtokentokentokentoken', 'user', 'User', null, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $source);
		$source->setId(9);
		$this->mapper->method('getToken')->willReturn($source);
		$this->mapper->method('getTokenById')->willReturn($source);
		$this->mapper->method('deleteSessionToken')->willReturn(true);

		$tokenProvider->copySessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');
		$tokenProvider->renewSessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');

		$this->assertArrayNotHasKey(hash('sha512', 'newIdtokentokentokentoken' . '1f4h9s'), $cached);
	}

	public function testGetTokensOfLoginResolvesAnyTokenOfTheLogin(): void {
		$first = $this->loginToken(42, null);
		$first->setLastActivity(1000);
		$copy = $this->loginToken(43, 42);
		$copy->setLastActivity(1500);
		$this->mapper->method('getTokenById')
			->with(43)
			->willReturn($this->loginToken(43, 42));
		$this->mapper->expects($this->once())
			->method('getTokensOfLogin')
			->with('user', 42)
			->willReturn([$first, $copy]);

		$this->assertSame([$copy, $first], $this->tokenProvider->getTokensOfLogin('user', 43));
	}

	public function testGetTokensOfALoginWhoseFirstTokenIsGone(): void {
		$tokens = [$this->loginToken(43, 42)];
		$this->mapper->method('getTokenById')
			->with(42)
			->willThrowException(new DoesNotExistException('nope'));
		$this->mapper->expects($this->once())
			->method('getTokensOfLogin')
			->with('user', 42)
			->willReturn($tokens);

		$this->assertSame($tokens, $this->tokenProvider->getTokensOfLogin('user', 42));
	}

	public function testGetLoginsOfUser(): void {
		$first = $this->loginToken(42, null);
		$first->setLastActivity(1000);
		$copy = $this->loginToken(43, 42);
		$copy->setLastActivity(1500);
		$appPassword = $this->loginToken(50, null);
		$appPassword->setType(IToken::PERMANENT_TOKEN);
		$this->mapper->method('getTokenByUser')
			->with('user')
			->willReturn([$first, $appPassword, $copy]);

		// The tokens of each login come most recently used first
		$this->assertSame([42 => [$copy, $first], 50 => [$appPassword]], $this->tokenProvider->getLoginsOfUser('user'));
	}

	public function testMarkForWipe(): void {
		$first = $this->loginToken(42, null);
		$copy = $this->loginToken(43, 42);
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->with('user', 42, true)
			->willReturn([$first, $copy]);
		$updated = [];
		$this->mapper->expects($this->exactly(2))
			->method('update')
			->willReturnCallback(function (PublicKeyToken $token) use (&$updated): PublicKeyToken {
				$updated[$token->getId()] = $token->getType();
				return $token;
			});

		$this->tokenProvider->markForWipe($copy);

		$this->assertSame([42 => IToken::WIPE_TOKEN, 43 => IToken::WIPE_TOKEN], $updated);
	}

	public function testInvalidateOldTokens(): void {
		$defaultSessionLifetime = 60 * 60 * 24;
		$defaultRememberMeLifetime = 60 * 60 * 24 * 15;
		$wipeTokenLifetime = 60 * 60 * 24 * 60;
		$this->config->expects($this->exactly(4))
			->method('getSystemValueInt')
			->willReturnMap([
				['session_lifetime', $defaultSessionLifetime, 150],
				['remember_login_cookie_lifetime', $defaultRememberMeLifetime, 300],
				['token_auth_wipe_token_retention', $wipeTokenLifetime, 500],
				['token_auth_token_retention', 60 * 60 * 24 * 365, 800],
			]);

		$calls = [
			[$this->time - 150, IToken::TEMPORARY_TOKEN, IToken::DO_NOT_REMEMBER],
			[$this->time - 300, IToken::TEMPORARY_TOKEN, IToken::REMEMBER],
			[$this->time - 500, IToken::WIPE_TOKEN, null],
			[$this->time - 800, IToken::PERMANENT_TOKEN, null],
		];
		$this->mapper->expects($this->exactly(4))
			->method('invalidateOld')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->tokenProvider->invalidateOldTokens();
	}

	public function testInvalidateLastUsedBefore(): void {
		$this->mapper->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$this->tokenProvider->invalidateLastUsedBefore('user', 946684800);
	}

	public function testRenewSessionTokenWithoutPassword(): void {
		$token = 'oldIdtokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = null;
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::TEMPORARY_TOKEN;

		$oldToken = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $oldToken);
		$oldToken->setId(7);
		$this->mapper->method('getTokenById')->with(7, true)->willReturn($oldToken);

		$this->mapper
			->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'oldIdtokentokentokentoken' . '1f4h9s'))
			->willReturn($oldToken);
		$this->mapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (PublicKeyToken $token) use ($user, $uid, $name, $oldToken) {
				return $token->getUID() === $uid
					&& $token->getLoginName() === $user
					&& $token->getName() === $name
					&& $token->getType() === IToken::DO_NOT_REMEMBER
					&& $token->getLastActivity() === $this->time
					&& $token->getPassword() === null
					&& $token->getLoginId() === 7
					&& $token->getPublicKey() !== $oldToken->getPublicKey();
			}));
		$this->mapper
			->expects($this->once())
			->method('deleteSessionToken')
			->with(7)
			->willReturn(true);

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testRenewSessionTokenWithPassword(): void {
		$token = 'oldIdtokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'password';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::TEMPORARY_TOKEN;

		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$oldToken = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $oldToken);
		$oldToken->setId(7);
		$this->mapper->method('getTokenById')->with(7, true)->willReturn($oldToken);

		$this->mapper
			->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'oldIdtokentokentokentoken' . '1f4h9s'))
			->willReturn($oldToken);
		$this->mapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (PublicKeyToken $token) use ($user, $uid, $name, $oldToken): bool {
				return $token->getUID() === $uid
					&& $token->getLoginName() === $user
					&& $token->getName() === $name
					&& $token->getType() === IToken::DO_NOT_REMEMBER
					&& $token->getLastActivity() === $this->time
					&& $token->getPassword() !== null
					&& $this->tokenProvider->getPassword($token, 'newIdtokentokentokentoken') === 'password'
					&& $token->getPasswordHash() === $oldToken->getPasswordHash()
					&& $token->getLoginId() === 7;
			}));
		$this->mapper
			->expects($this->once())
			->method('deleteSessionToken')
			->with(7)
			->willReturn(true);

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testRenewSessionTokenThatWasRenewedOrInvalidatedMeanwhile(): void {
		$oldToken = $this->tokenProvider->generateToken('oldIdtokentokentokentoken', 'user', 'User', null, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $oldToken);
		$oldToken->setId(7);
		// The token was still in the cache or the database when this request looked it up, and without row locks it
		// was still there when this request read it again
		$this->mapper->method('getToken')->willReturn($oldToken);
		$this->mapper->method('getTokenById')->with(7, true)->willReturn($oldToken);
		$this->mapper->method('deleteSessionToken')->with(7)->willReturn(false);

		$this->mapper->expects($this->never())
			->method('insert');
		$this->expectException(InvalidTokenException::class);

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testRenewSessionTokenThatIsGoneOnceLocked(): void {
		$oldToken = $this->tokenProvider->generateToken('oldIdtokentokentokentoken', 'user', 'User', null, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $oldToken);
		$oldToken->setId(7);
		$this->mapper->method('getToken')->willReturn($oldToken);
		// A concurrent renewal or an invalidation deleted it while this request waited for the lock
		$this->mapper->method('getTokenById')->with(7, true)->willThrowException(new DoesNotExistException('nope'));

		$this->mapper->expects($this->never())
			->method('insert');
		$this->expectException(InvalidTokenException::class);

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public static function currentStateOfTheSourceData(): array {
		return [
			'renewal' => ['copy' => false],
			'copy' => ['copy' => true],
		];
	}

	#[DataProvider(methodName: 'currentStateOfTheSourceData')]
	public function testNewSessionTokenTakesTheCurrentStateOfTheSource(bool $copy): void {
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$cached = $this->tokenProvider->generateToken('sourceIdtokentokentokentoken', 'user', 'User', 'old password', 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $cached);
		$cached->setId(9);
		// Renamed, restricted and given a new password by other requests after this one read it from the cache
		$current = clone $cached;
		$current->setName('Laptop');
		$current->setScope([IToken::SCOPE_FILESYSTEM => false]);
		openssl_public_encrypt('new password', $encryptedPassword, $current->getPublicKey(), OPENSSL_PKCS1_OAEP_PADDING);
		$current->setPassword(base64_encode($encryptedPassword));
		$this->mapper->method('getToken')->willReturn($cached);
		$this->mapper->method('getTokenById')->with(9, true)->willReturn($current);
		$this->mapper->method('deleteSessionToken')->willReturn(true);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($this->callback(fn (PublicKeyToken $token): bool => $token->getName() === 'Laptop'
				&& $token->getScopeAsArray() === [IToken::SCOPE_FILESYSTEM => false]
				&& $this->tokenProvider->getPassword($token, 'newIdtokentokentokentoken') === 'new password'));

		if ($copy) {
			$this->tokenProvider->copySessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');
		} else {
			$this->tokenProvider->renewSessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');
		}
	}

	public function testUpdateLogin(): void {
		$first = $this->loginToken(42, null);
		$first->setName('Firefox');
		$copy = $this->loginToken(43, 42);
		$copy->setName('Firefox');
		$this->mapper->method('getTokenById')->with(43)->willReturn($copy);
		$this->mapper->expects($this->exactly(2))
			->method('getTokensOfLogin')
			->with('user', 42, true)
			->willReturn([$first, $copy]);
		$updated = [];
		$this->mapper->expects($this->exactly(2))
			->method('update')
			->willReturnCallback(function (PublicKeyToken $token) use (&$updated): PublicKeyToken {
				$updated[$token->getId()] = [$token->getName(), $token->getScopeAsArray()];
				return $token;
			});

		$this->tokenProvider->updateLogin('user', 43, 'Laptop', null);

		$this->assertSame([
			42 => ['Laptop', [IToken::SCOPE_FILESYSTEM => true]],
			43 => ['Laptop', [IToken::SCOPE_FILESYSTEM => true]],
		], $updated);
	}

	public function testRenewSessionTokenThatCannotBeDecrypted(): void {
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$oldToken = $this->tokenProvider->generateToken('oldIdtokentokentokentoken', 'user', 'User', 'password', 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER);
		$this->assertInstanceOf(PublicKeyToken::class, $oldToken);
		$oldToken->setId(7);
		$oldToken->setPrivateKey('not encrypted with this session id');
		$this->mapper->method('getToken')->willReturn($oldToken);
		$this->mapper->method('getTokenById')->with(7, true)->willReturn($oldToken);

		// The renewal fails as a whole, it does not invalidate the login on the side
		$this->mapper->expects($this->never())
			->method('getTokensOfLogin');
		$this->mapper->expects($this->never())
			->method('invalidate');
		$this->mapper->expects($this->never())
			->method('deleteSessionToken');
		$this->expectException(InvalidTokenException::class);

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testCopySessionTokenKeepsTheSourceToken(): void {
		$uid = 'user';
		$user = 'User';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';

		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$sourceToken = $this->tokenProvider->generateToken('sourceIdtokentokentokentoken', $uid, $user, 'password', $name, IToken::TEMPORARY_TOKEN, IToken::REMEMBER, null, 1792600000);
		$this->assertInstanceOf(PublicKeyToken::class, $sourceToken);
		$sourceToken->setId(9);
		$sourceToken->setLoginId(5);

		$this->mapper
			->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'sourceIdtokentokentokentoken' . '1f4h9s'))
			->willReturn($sourceToken);
		// Locks the source until the copy is stored
		$this->mapper
			->expects($this->once())
			->method('getTokenById')
			->with(9, true)
			->willReturn($sourceToken);
		$this->mapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (PublicKeyToken $token) use ($user, $uid, $name, $sourceToken): bool {
				return $token->getUID() === $uid
					&& $token->getLoginName() === $user
					&& $token->getName() === $name
					&& $token->getType() === IToken::TEMPORARY_TOKEN
					&& $token->getRemember() === IToken::REMEMBER
					&& $token->getExpires() === 1792600000
					&& $this->tokenProvider->getPassword($token, 'newIdtokentokentokentoken') === 'password'
					&& $token->getLoginId() === 5
					&& $token->getPublicKey() !== $sourceToken->getPublicKey();
			}));
		// The session of the source token keeps its token
		$this->mapper
			->expects($this->never())
			->method('delete');

		$this->tokenProvider->copySessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testCopySessionTokenKeepsTheScope(): void {
		$sourceToken = $this->tokenProvider->generateToken('sourceIdtokentokentokentoken', 'user', 'User', null, 'Firefox', IToken::TEMPORARY_TOKEN, IToken::REMEMBER, [IToken::SCOPE_FILESYSTEM => false]);
		$this->assertInstanceOf(PublicKeyToken::class, $sourceToken);
		$sourceToken->setId(9);
		$this->mapper->method('getToken')->willReturn($sourceToken);
		$this->mapper->method('getTokenById')->willReturn($sourceToken);

		$token = $this->tokenProvider->copySessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');

		$this->assertSame([IToken::SCOPE_FILESYSTEM => false], $token->getScopeAsArray());
	}

	public static function refusedCopySourceData(): array {
		return [
			'app password' => ['type' => IToken::PERMANENT_TOKEN, 'remember' => IToken::DO_NOT_REMEMBER, 'meanwhile' => null],
			'one-time token' => ['type' => IToken::ONETIME_TOKEN, 'remember' => IToken::DO_NOT_REMEMBER, 'meanwhile' => null],
			'session token without remember me' => ['type' => IToken::TEMPORARY_TOKEN, 'remember' => IToken::DO_NOT_REMEMBER, 'meanwhile' => null],
			'session token invalidated meanwhile' => ['type' => IToken::TEMPORARY_TOKEN, 'remember' => IToken::REMEMBER, 'meanwhile' => 'invalidated'],
			'session token marked for wipe meanwhile' => ['type' => IToken::TEMPORARY_TOKEN, 'remember' => IToken::REMEMBER, 'meanwhile' => 'wiped'],
		];
	}

	#[DataProvider(methodName: 'refusedCopySourceData')]
	public function testCopySessionTokenRefusesTheSource(int $type, int $remember, ?string $meanwhile): void {
		$sourceToken = $this->tokenProvider->generateToken('sourceIdtokentokentokentoken', 'user', 'User', null, 'Firefox', $type, $remember);
		$this->assertInstanceOf(PublicKeyToken::class, $sourceToken);
		$sourceToken->setId(9);
		// The token as this request found it, in the cache or the database
		$this->mapper->method('getToken')->willReturn(clone $sourceToken);
		if ($meanwhile === 'invalidated') {
			$this->mapper->method('getTokenById')->willThrowException(new DoesNotExistException('nope'));
		} else {
			if ($meanwhile === 'wiped') {
				$sourceToken->wipe();
			}
			$this->mapper->method('getTokenById')->with(9, true)->willReturn($sourceToken);
		}

		$this->mapper->expects($this->never())
			->method('insert');
		// Includes the exceptions for expired and wiped tokens
		$this->expectException(OCPInvalidTokenException::class);

		$this->tokenProvider->copySessionToken('sourceIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testGetToken(): void {
		$token = new PublicKeyToken();
		$token->setToken('token');
		$token->setType(IToken::TEMPORARY_TOKEN);

		$this->config->method('getSystemValue')
			->with('secret')
			->willReturn('mysecret');

		$this->mapper->method('getToken')
			->with(
				$this->callback(function (string $token) {
					return hash('sha512', 'unhashedTokentokentokentokentoken' . '1f4h9s') === $token;
				})
			)->willReturn($token);

		$this->assertSame($token, $this->tokenProvider->getToken('unhashedTokentokentokentokentoken'));
	}

	public function testGetInvalidToken(): void {
		$this->expectException(InvalidTokenException::class);

		$calls = [
			'unhashedTokentokentokentokentoken' . '1f4h9s',
			'unhashedTokentokentokentokentoken',
		];
		$this->mapper->expects($this->exactly(2))
			->method('getToken')
			->willReturnCallback(function (string $token) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals(hash('sha512', $expected), $token);
				throw new DoesNotExistException('nope');
			});

		$this->tokenProvider->getToken('unhashedTokentokentokentokentoken');
	}

	public function testGetExpiredToken(): void {
		$token = 'tokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'passme';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);
		$actual->setExpires(42);

		$this->mapper->method('getToken')
			->with(
				$this->callback(function (string $token) {
					return hash('sha512', 'tokentokentokentokentoken' . '1f4h9s') === $token;
				})
			)->willReturn($actual);

		try {
			$this->tokenProvider->getToken('tokentokentokentokentoken');
			$this->fail();
		} catch (ExpiredTokenException $e) {
			$this->assertSame($actual, $e->getToken());
		}
	}

	public function testGetTokenById(): void {
		$token = $this->createMock(PublicKeyToken::class);

		$this->mapper->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willReturn($token);

		$this->assertSame($token, $this->tokenProvider->getTokenById(42));
	}

	public function testGetInvalidTokenById(): void {
		$this->expectException(InvalidTokenException::class);

		$this->mapper->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willThrowException(new DoesNotExistException('nope'));

		$this->tokenProvider->getTokenById(42);
	}

	public function testGetExpiredTokenById(): void {
		$token = new PublicKeyToken();
		$token->setExpires(42);

		$this->mapper->expects($this->once())
			->method('getTokenById')
			->with($this->equalTo(42))
			->willReturn($token);

		try {
			$this->tokenProvider->getTokenById(42);
			$this->fail();
		} catch (ExpiredTokenException $e) {
			$this->assertSame($token, $e->getToken());
		}
	}

	public function testRotate(): void {
		$token = 'oldtokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'password';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;

		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$new = $this->tokenProvider->rotate($actual, 'oldtokentokentokentokentoken', 'newtokentokentokentokentoken');

		$this->assertSame('password', $this->tokenProvider->getPassword($new, 'newtokentokentokentokentoken'));
	}

	public function testRotateNoPassword(): void {
		$token = 'oldtokentokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = null;
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;

		$actual = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$oldPrivate = $actual->getPrivateKey();

		$new = $this->tokenProvider->rotate($actual, 'oldtokentokentokentokentoken', 'newtokentokentokentokentoken');

		$newPrivate = $new->getPrivateKey();

		$this->assertNotSame($newPrivate, $oldPrivate);
		$this->assertNull($new->getPassword());
	}

	public function testMarkPasswordInvalidInvalidToken(): void {
		$token = $this->createMock(IToken::class);

		$this->expectException(InvalidTokenException::class);

		$this->tokenProvider->markPasswordInvalid($token, 'tokenId');
	}

	public function testMarkPasswordInvalid(): void {
		$token = $this->createMock(PublicKeyToken::class);
		$token->expects($this->once())
			->method('getToken')
			->willReturn('token');

		$token->expects($this->once())
			->method('setPasswordInvalid')
			->with(true);
		$this->mapper->expects($this->once())
			->method('update')
			->with($token);

		$this->tokenProvider->markPasswordInvalid($token, 'tokenId');
	}

	public function testUpdatePasswords(): void {
		$uid = 'myUID';
		$token1 = $this->tokenProvider->generateToken(
			'foobetokentokentokentoken',
			$uid,
			$uid,
			'bar',
			'random1',
			IToken::PERMANENT_TOKEN,
			IToken::REMEMBER);
		$token2 = $this->tokenProvider->generateToken(
			'foobartokentokentokentoken',
			$uid,
			$uid,
			'bar',
			'random2',
			IToken::PERMANENT_TOKEN,
			IToken::REMEMBER);
		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);

		$this->mapper->method('hasExpiredTokens')
			->with($uid)
			->willReturn(true);
		$this->mapper->expects($this->once())
			->method('getTokenByUser')
			->with($uid)
			->willReturn([$token1, $token2]);
		$this->mapper->expects($this->exactly(2))
			->method('update')
			->with($this->callback(function (PublicKeyToken $t) use ($token1, $token2) {
				return $t === $token1 || $t === $token2;
			}));

		$this->tokenProvider->updatePasswords($uid, 'bar2');
	}

	/**
	 * A session token $id of $uid that belongs to the login $loginId, or starts its own login if null
	 */
	private function loginToken(int $id, ?int $loginId, string $hash = '', string $uid = 'user'): PublicKeyToken {
		$token = new PublicKeyToken();
		$token->setId($id);
		$token->setUid($uid);
		$token->setLoginId($loginId);
		$token->setToken($hash === '' ? 'hash' . $id : $hash);
		$token->setType(IToken::TEMPORARY_TOKEN);
		$token->setRemember(IToken::REMEMBER);
		return $token;
	}

	/**
	 * @return \ArrayObject<int, int> The ids of the tokens the mapper deletes, in order
	 */
	private function recordDeletedTokens(): \ArrayObject {
		$deleted = new \ArrayObject();
		$this->mapper->method('delete')
			->willReturnCallback(function (PublicKeyToken $token) use ($deleted): PublicKeyToken {
				$deleted[] = $token->getId();
				return $token;
			});
		return $deleted;
	}
}
