<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Test\Security\VerificationToken;

use OC\Security\VerificationToken\VerificationToken;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Security\VerificationToken\InvalidTokenException;
use Test\TestCase;

class VerificationTokenTest extends TestCase {
	/** @var VerificationToken */
	protected $token;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var ISecureRandom|\PHPUnit\Framework\MockObject\MockObject */
	protected $secureRandom;
	/** @var ICrypto|\PHPUnit\Framework\MockObject\MockObject */
	protected $crypto;
	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->token = new VerificationToken(
			$this->config,
			$this->crypto,
			$this->timeFactory,
			$this->secureRandom,
			$this->jobList
		);
	}

	public function testTokenUserUnknown() {
		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::USER_UNKNOWN);
		$this->token->check('encryptedToken', null, 'fingerprintToken', 'foobar');
	}

	public function testTokenUserUnknown2() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(false);

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::USER_UNKNOWN);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenNotFound() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');

		// implicit: IConfig::getUserValue returns null by default

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_NOT_FOUND);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenDecryptionError() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('alice', 'core', 'fingerprintToken', null)
			->willReturn('encryptedToken');
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willThrowException(new \Exception('decryption failed'));

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_DECRYPTION_ERROR);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenInvalidFormat() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('alice', 'core', 'fingerprintToken', null)
			->willReturn('encryptedToken');
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willReturn('decrypted^nonsense');

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_INVALID_FORMAT);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenExpired() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');
		$user->expects($this->any())
			->method('getLastLogin')
			->willReturn(604803);

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('alice', 'core', 'fingerprintToken', null)
			->willReturn('encryptedToken');
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willReturn('604800:mY70K3n');

		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(604800 * 3);

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_EXPIRED);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenExpiredByLogin() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');
		$user->expects($this->any())
			->method('getLastLogin')
			->willReturn(604803);

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('alice', 'core', 'fingerprintToken', null)
			->willReturn('encryptedToken');
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willReturn('604800:mY70K3n');

		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(604801);

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_EXPIRED);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar', true);
	}

	public function testTokenMismatch() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');
		$user->expects($this->any())
			->method('getLastLogin')
			->willReturn(604703);

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('alice', 'core', 'fingerprintToken', null)
			->willReturn('encryptedToken');
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willReturn('604802:mY70K3n');

		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(604801);

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_MISMATCH);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenSuccess() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->atLeastOnce())
			->method('getUID')
			->willReturn('alice');
		$user->expects($this->any())
			->method('getLastLogin')
			->willReturn(604703);

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->with('alice', 'core', 'fingerprintToken', null)
			->willReturn('encryptedToken');
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willReturn('604802:barfoo');

		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(604801);

		$this->token->check('barfoo', $user, 'fingerprintToken', 'foobar');
	}

	public function testCreate() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$this->secureRandom->expects($this->atLeastOnce())
			->method('generate')
			->willReturn('barfoo');
		$this->crypto->expects($this->atLeastOnce())
			->method('encrypt')
			->willReturn('encryptedToken');
		$this->config->expects($this->atLeastOnce())
			->method('setUserValue')
			->with('alice', 'core', 'fingerprintToken', 'encryptedToken');

		$vToken = $this->token->create($user, 'fingerprintToken', 'foobar');
		$this->assertSame('barfoo', $vToken);
	}
}
