<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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

	public function testGenerateToken() {
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

	public function testGenerateConflictingToken() {
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

	public function testGenerateTokenTooLongName() {
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

	public function tokenData(): array {
		return [
			[new PublicKeyToken()],
			[$this->createMock(IToken::class)],
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
	public function testUpdateToken(IToken $token) {
		$this->setNoCall($token);
		$this->setCall($token, 'updateToken');
		$this->setException($token);

		$this->manager->updateToken($token);
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testUpdateTokenActivity(IToken $token) {
		$this->setNoCall($token);
		$this->setCall($token, 'updateTokenActivity');
		$this->setException($token);

		$this->manager->updateTokenActivity($token);
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testGetPassword(IToken $token) {
		$this->setNoCall($token);
		$this->setCall($token, 'getPassword', 'password');
		$this->setException($token);

		$result = $this->manager->getPassword($token, 'tokenId', 'password');

		$this->assertSame('password', $result);
	}

	/**
	 * @dataProvider tokenData
	 */
	public function testSetPassword(IToken $token) {
		$this->setNoCall($token);
		$this->setCall($token, 'setPassword');
		$this->setException($token);

		$this->manager->setPassword($token, 'tokenId', 'password');
	}

	public function testInvalidateTokens() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateToken')
			->with('token');

		$this->manager->invalidateToken('token');
	}

	public function testInvalidateTokenById() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with('uid', 42);

		$this->manager->invalidateTokenById('uid', 42);
	}

	public function testInvalidateOldTokens() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('invalidateOldTokens');

		$this->manager->invalidateOldTokens();
	}

	public function testGetTokenByUser() {
		$t1 = new PublicKeyToken();
		$t2 = new PublicKeyToken();

		$this->publicKeyTokenProvider
			->method('getTokenByUser')
			->willReturn([$t1, $t2]);

		$result = $this->manager->getTokenByUser('uid');

		$this->assertEquals([$t1, $t2], $result);
	}

	public function testRenewSessionTokenPublicKey() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with('oldId', 'newId');

		$this->manager->renewSessionToken('oldId', 'newId');
	}

	public function testRenewSessionInvalid() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('renewSessionToken')
			->with('oldId', 'newId')
			->willThrowException(new InvalidTokenException());

		$this->expectException(InvalidTokenException::class);
		$this->manager->renewSessionToken('oldId', 'newId');
	}

	public function testGetTokenByIdPublicKey() {
		$token = $this->createMock(IToken::class);

		$this->publicKeyTokenProvider->expects($this->once())
			->method('getTokenById')
			->with(42)
			->willReturn($token);

		$this->assertSame($token, $this->manager->getTokenById(42));
	}

	public function testGetTokenByIdInvalid() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('getTokenById')
			->with(42)
			->willThrowException(new InvalidTokenException());

		$this->expectException(InvalidTokenException::class);
		$this->manager->getTokenById(42);
	}

	public function testGetTokenPublicKey() {
		$token = new PublicKeyToken();

		$this->publicKeyTokenProvider
			->method('getToken')
			->with('tokenId')
			->willReturn($token);

		$this->assertSame($token, $this->manager->getToken('tokenId'));
	}

	public function testGetTokenInvalid() {
		$this->publicKeyTokenProvider
			->method('getToken')
			->with('tokenId')
			->willThrowException(new InvalidTokenException());

		$this->expectException(InvalidTokenException::class);
		$this->manager->getToken('tokenId');
	}

	public function testRotateInvalid() {
		$this->expectException(InvalidTokenException::class);
		$this->manager->rotate($this->createMock(IToken::class), 'oldId', 'newId');
	}

	public function testRotatePublicKey() {
		$token = new PublicKeyToken();

		$this->publicKeyTokenProvider
			->method('rotate')
			->with($token, 'oldId', 'newId')
			->willReturn($token);

		$this->assertSame($token, $this->manager->rotate($token, 'oldId', 'newId'));
	}

	public function testMarkPasswordInvalidPublicKey() {
		$token = $this->createMock(PublicKeyToken::class);

		$this->publicKeyTokenProvider->expects($this->once())
			->method('markPasswordInvalid')
			->with($token, 'tokenId');

		$this->manager->markPasswordInvalid($token, 'tokenId');
	}

	public function testMarkPasswordInvalidInvalidToken() {
		$this->expectException(InvalidTokenException::class);

		$this->manager->markPasswordInvalid($this->createMock(IToken::class), 'tokenId');
	}

	public function testUpdatePasswords() {
		$this->publicKeyTokenProvider->expects($this->once())
			->method('updatePasswords')
			->with('uid', 'pass');

		$this->manager->updatePasswords('uid', 'pass');
	}
}
