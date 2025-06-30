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
use OCP\Authentication\Token\IToken;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use OCP\Security\IHasher;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PublicKeyTokenProviderTest extends TestCase {
	/** @var PublicKeyTokenProvider|\PHPUnit\Framework\MockObject\MockObject */
	private $tokenProvider;
	/** @var PublicKeyTokenMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $mapper;
	/** @var IHasher|\PHPUnit\Framework\MockObject\MockObject */
	private $hasher;
	/** @var ICrypto */
	private $crypto;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IDBConnection|MockObject */
	private IDBConnection $db;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cacheFactory;
	/** @var int */
	private $time;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(PublicKeyTokenMapper::class);
		$this->hasher = Server::get(IHasher::class);
		$this->crypto = Server::get(ICrypto::class);
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

		$this->tokenProvider = new PublicKeyTokenProvider(
			$this->mapper,
			$this->crypto,
			$this->config,
			$this->db,
			$this->logger,
			$this->timeFactory,
			$this->hasher,
			$this->cacheFactory,
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
		$calls = [
			[hash('sha512', 'token7' . '1f4h9s')],
			[hash('sha512', 'token7')]
		];

		$this->mapper->expects($this->exactly(2))
			->method('invalidate')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->tokenProvider->invalidateToken('token7');
	}

	public function testInvalidateTokenById(): void {
		$id = 123;

		$this->mapper->expects($this->once())
			->method('getTokenById')
			->with($id);

		$this->tokenProvider->invalidateTokenById('uid', $id);
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
		$type = IToken::PERMANENT_TOKEN;

		$oldToken = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->mapper
			->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'oldIdtokentokentokentoken' . '1f4h9s'))
			->willReturn($oldToken);
		$this->mapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (PublicKeyToken $token) use ($user, $uid, $name) {
				return $token->getUID() === $uid &&
					$token->getLoginName() === $user &&
					$token->getName() === $name &&
					$token->getType() === IToken::DO_NOT_REMEMBER &&
					$token->getLastActivity() === $this->time &&
					$token->getPassword() === null;
			}));
		$this->mapper
			->expects($this->once())
			->method('delete')
			->with($this->callback(function ($token) use ($oldToken) {
				return $token === $oldToken;
			}));

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testRenewSessionTokenWithPassword(): void {
		$token = 'oldIdtokentokentokentoken';
		$uid = 'user';
		$user = 'User';
		$password = 'password';
		$name = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
		$type = IToken::PERMANENT_TOKEN;

		$this->config->method('getSystemValueBool')
			->willReturnMap([
				['auth.storeCryptedPassword', true, true],
			]);
		$oldToken = $this->tokenProvider->generateToken($token, $uid, $user, $password, $name, $type, IToken::DO_NOT_REMEMBER);

		$this->mapper
			->expects($this->once())
			->method('getToken')
			->with(hash('sha512', 'oldIdtokentokentokentoken' . '1f4h9s'))
			->willReturn($oldToken);
		$this->mapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (PublicKeyToken $token) use ($user, $uid, $name): bool {
				return $token->getUID() === $uid &&
					$token->getLoginName() === $user &&
					$token->getName() === $name &&
					$token->getType() === IToken::DO_NOT_REMEMBER &&
					$token->getLastActivity() === $this->time &&
					$token->getPassword() !== null &&
					$this->tokenProvider->getPassword($token, 'newIdtokentokentokentoken') === 'password';
			}));
		$this->mapper
			->expects($this->once())
			->method('delete')
			->with($this->callback(function ($token) use ($oldToken): bool {
				return $token === $oldToken;
			}));

		$this->tokenProvider->renewSessionToken('oldIdtokentokentokentoken', 'newIdtokentokentokentoken');
	}

	public function testGetToken(): void {
		$token = new PublicKeyToken();

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
}
