<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class VerificationTokenTest extends TestCase {
	/** @var VerificationToken */
	protected $token;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var ISecureRandom|MockObject */
	protected $secureRandom;
	/** @var ICrypto|MockObject */
	protected $crypto;
	/** @var ITimeFactory|MockObject */
	protected $timeFactory;
	/** @var IJobList|MockObject */
	protected $jobList;

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

	public function testTokenUserUnknown(): void {
		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::USER_UNKNOWN);
		$this->token->check('encryptedToken', null, 'fingerprintToken', 'foobar');
	}

	public function testTokenUserUnknown2(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->atLeastOnce())
			->method('isEnabled')
			->willReturn(false);

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::USER_UNKNOWN);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenNotFound(): void {
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

	public function testTokenDecryptionError(): void {
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
			->method('getSystemValueString')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willThrowException(new \Exception('decryption failed'));

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_DECRYPTION_ERROR);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenInvalidFormat(): void {
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
			->method('getSystemValueString')
			->with('secret')
			->willReturn('357111317');

		$this->crypto->method('decrypt')
			->with('encryptedToken', 'foobar' . '357111317')
			->willReturn('decrypted^nonsense');

		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionCode(InvalidTokenException::TOKEN_INVALID_FORMAT);
		$this->token->check('encryptedToken', $user, 'fingerprintToken', 'foobar');
	}

	public function testTokenExpired(): void {
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
			->method('getSystemValueString')
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

	public function testTokenExpiredByLogin(): void {
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
			->method('getSystemValueString')
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

	public function testTokenMismatch(): void {
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
			->method('getSystemValueString')
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

	public function testTokenSuccess(): void {
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
			->method('getSystemValueString')
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

	public function testCreate(): void {
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
