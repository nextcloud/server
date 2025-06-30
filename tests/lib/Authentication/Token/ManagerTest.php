<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IToken;
use OC\Authentication\Token\Manager;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\PublicKeyTokenProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var PublicKeyTokenProvider|MockObject */
	private $publicKeyTokenProvider;
	/** @var Manager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->publicKeyTokenProvider = $this->createMock(PublicKeyTokenProvider::class);
		$this->manager = new Manager(
			$this->publicKeyTokenProvider
		);
	}

	public function testGenerateToken(): void {
		$token = new PublicKeyToken();

		$this->publicKeyTokenProvider->expects($this->once())
			->method('generateToken')
			->with(
				'token',
				'uid',
				'loginName',
				'password',
				'name',
				IToken::TEMPORARY_TOKEN,
				IToken::REMEMBER
			)->willReturn($token);

		$actual = $this->manager->generateToken(
			'token',
			'uid',
			'loginName',
			'password',
			'name',
			IToken::TEMPORARY_TOKEN,
			IToken::REMEMBER
		);

		$this->assertSame($token, $actual);
	}

	public function testGenerateConflictingToken(): void {
		/** @var MockObject|UniqueConstraintViolationException $exception */
		$exception = $this->createMock(UniqueConstraintViolationException::class);

		$token = new PublicKeyToken();
		$token->setUid('uid');

		$this->publicKeyTokenProvider->expects($this->once())
			->method('generateToken')
			->with(
				'token',
				'uid',
				'loginName',
				'password',
				'name',
				IToken::TEMPORARY_TOKEN,
				IToken::REMEMBER
			)->willThrowException($exception);
		$this->publicKeyTokenProvider->expects($this->once())
			->method('getToken')
			->with('token')
			->willReturn($token);

		$actual = $this->manager->generateToken(
			'token',
			'uid',
			'loginName',
			'password',
			'name',
			IToken::TEMPORARY_TOKEN,
			IToken::REMEMBER
		);

		$this->assertSame($token, $actual);
	}

	public function testGenerateTokenTooLongName(): void {
		$token = $this->createMock(IToken::class);
		$token->method('getName')
			->willReturn(str_repeat('a', 120) . '…');


		$this->publicKeyTokenProvider->expects($this->once())
			->method('generateToken')
			->with(
				'token',
				'uid',
				'loginName',
				'password',
				str_repeat('a', 120) . '…',
				IToken::TEMPORARY_TOKEN,
				IToken::REMEMBER
			)->willReturn($token);

		$actual = $this->manager->generateToken(
			'token',
			'uid',
			'loginName',
			'password',
			str_repeat('a', 200),
			IToken::TEMPORARY_TOKEN,
			IToken::REMEMBER
		);

		$this->assertSame(121, mb_strlen($actual->getName()));
	}

	public static function tokenData(): array {
		return [
			[new PublicKeyToken()],
			[IToken::class],
		];
	}

	protected function setNoCall(IToken $token) {
		if (!($token instanceof PublicKeyToken)) {
			$this->publicKeyTokenProvider->expects($this->never())
				->method($this->anything());
		}
	}

	protected function setCall(IToken $token, string $function, $return = null) {
		if ($token instanceof PublicKeyToken) {
			$this->publicKeyTokenProvider->expects($this->once())
				->method($function)
				->with($token)
				->willReturn($return);
		}
	}

	protected function setException(IToken $token) {
		if (!($token instanceof PublicKeyToken)) {
			$this->expectException(InvalidTokenException::class);
		}
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testUpdateToken(IToken|string $token): void {
		if (is_string($token)) {
			$token = $this->createMock($token);
		}

		$this->setNoCall($token);
		$this->setCall($token, 'updateToken');
		$this->setException($token);

		$this->manager->updateToken($token);
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testUpdateTokenActivity(IToken|string $token): void {
		if (is_string($token)) {
			$token = $this->createMock($token);
		}

		$this->setNoCall($token);
		$this->setCall($token, 'updateTokenActivity');
		$this->setException($token);

		$this->manager->updateTokenActivity($token);
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testGetPassword(IToken|string $token): void {
		if (is_string($token)) {
			$token = $this->createMock($token);
		}

		$this->setNoCall($token);
		$this->setCall($token, 'getPassword', 'password');
		$this->setException($token);

		$result = $this->manager->getPassword($token, 'tokenId', 'password');

		$this->assertSame('password', $result);
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testSetPassword(IToken|string $token): void {
		if (is_string($token)) {
			$token = $this->createMock($token);
		}

		$this->setNoCall($token);
		$this->setCall($token, 'setPassword');
		$this->setException($token);

		$this->manager->setPassword($token, 'tokenId', 'password');
	}

	public function testInvalidateTokens(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateToken')
			->with('token');

		$this->manager->invalidateToken('token');
	}

	public function testInvalidateTokenById(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with('uid', 42);

		$this->manager->invalidateTokenById('uid', 42);
	}

	public function testInvalidateOldTokens(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateOldTokens');

		$this->manager->invalidateOldTokens();
	}

	public function testInvalidateLastUsedBefore(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateLastUsedBefore')
			->with('user', 946684800);

		$this->manager->invalidateLastUsedBefore('user', 946684800);
	}

	public function testGetTokenByUser(): void {
		$t1 = new PublicKeyToken();
		$t2 = new PublicKeyToken();

		$this->publicKeyTokenProvider
			->method('getTokenByUser')
			->willReturn([$t1, $t2]);

		$result = $this->manager->getTokenByUser('uid');

		$this->assertEquals([$t1, $t2], $result);
	}

	public function testRenewSessionTokenPublicKey(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with('oldId', 'newId');

		$this->manager->renewSessionToken('oldId', 'newId');
	}

	public function testRenewSessionInvalid(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with('oldId', 'newId')
			->willThrowException(new InvalidTokenException());

		$this->expectException(InvalidTokenException::class);
		$this->manager->renewSessionToken('oldId', 'newId');
	}

	public function testGetTokenByIdPublicKey(): void {
		$token = $this->createMock(IToken::class);

		$this->publicKeyTokenProvider->expects($this->once())
			->method('getTokenById')
			->with(42)
			->willReturn($token);

		$this->assertSame($token, $this->manager->getTokenById(42));
	}

	public function testGetTokenByIdInvalid(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('getTokenById')
			->with(42)
			->willThrowException(new InvalidTokenException());

		$this->expectException(InvalidTokenException::class);
		$this->manager->getTokenById(42);
	}

	public function testGetTokenPublicKey(): void {
		$token = new PublicKeyToken();

		$this->publicKeyTokenProvider
			->method('getToken')
			->with('tokenId')
			->willReturn($token);

		$this->assertSame($token, $this->manager->getToken('tokenId'));
	}

	public function testGetTokenInvalid(): void {
		$this->publicKeyTokenProvider
			->method('getToken')
			->with('tokenId')
			->willThrowException(new InvalidTokenException());

		$this->expectException(InvalidTokenException::class);
		$this->manager->getToken('tokenId');
	}

	public function testRotateInvalid(): void {
		$this->expectException(InvalidTokenException::class);
		$this->manager->rotate($this->createMock(IToken::class), 'oldId', 'newId');
	}

	public function testRotatePublicKey(): void {
		$token = new PublicKeyToken();

		$this->publicKeyTokenProvider
			->method('rotate')
			->with($token, 'oldId', 'newId')
			->willReturn($token);

		$this->assertSame($token, $this->manager->rotate($token, 'oldId', 'newId'));
	}

	public function testMarkPasswordInvalidPublicKey(): void {
		$token = $this->createMock(PublicKeyToken::class);

		$this->publicKeyTokenProvider->expects($this->once())
			->method('markPasswordInvalid')
			->with($token, 'tokenId');

		$this->manager->markPasswordInvalid($token, 'tokenId');
	}

	public function testMarkPasswordInvalidInvalidToken(): void {
		$this->expectException(InvalidTokenException::class);

		$this->manager->markPasswordInvalid($this->createMock(IToken::class), 'tokenId');
	}

	public function testUpdatePasswords(): void {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('updatePasswords')
			->with('uid', 'pass');

		$this->manager->updatePasswords('uid', 'pass');
	}

	public function testInvalidateTokensOfUserNoClientName(): void {
		$t1 = new PublicKeyToken();
		$t2 = new PublicKeyToken();
		$t1->setId(123);
		$t2->setId(456);

		$this->publicKeyTokenProvider
			->expects($this->once())
			->method('getTokenByUser')
			->with('theUser')
			->willReturn([$t1, $t2]);

		$calls = [
			['theUser', 123],
			['theUser', 456],
		];
		$this->publicKeyTokenProvider
			->expects($this->exactly(2))
			->method('invalidateTokenById')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->manager->invalidateTokensOfUser('theUser', null);
	}

	public function testInvalidateTokensOfUserClientNameGiven(): void {
		$t1 = new PublicKeyToken();
		$t2 = new PublicKeyToken();
		$t3 = new PublicKeyToken();
		$t1->setId(123);
		$t1->setName('Firefox session');
		$t2->setId(456);
		$t2->setName('My Client Name');
		$t3->setId(789);
		$t3->setName('mobile client');

		$this->publicKeyTokenProvider
			->expects($this->once())
			->method('getTokenByUser')
			->with('theUser')
			->willReturn([$t1, $t2, $t3]);
		$this->publicKeyTokenProvider
			->expects($this->once())
			->method('invalidateTokenById')
			->with('theUser', 456);
		$this->manager->invalidateTokensOfUser('theUser', 'My Client Name');
	}
}
