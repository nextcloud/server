<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\LoginCredentials;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\LoginCredentials\Credentials;
use OC\Authentication\LoginCredentials\Store;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use function json_encode;

class StoreTest extends TestCase {
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;

	/** @var IProvider|\PHPUnit\Framework\MockObject\MockObject */
	private $tokenProvider;

	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** @var ICrypto|\PHPUnit\Framework\MockObject\MockObject */
	private $crypto;

	/** @var Store */
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->crypto = $this->createMock(ICrypto::class);

		$this->store = new Store($this->session, $this->logger, $this->crypto, $this->tokenProvider);
	}

	public function testAuthenticate(): void {
		$params = [
			'run' => true,
			'uid' => 'user123',
			'loginName' => 'userlogin',
			'password' => '123456',
		];

		$this->session->expects($this->once())
			->method('set')
			->with($this->equalTo('login_credentials'), $this->equalTo(json_encode($params)));
		$this->crypto->expects($this->once())
			->method('encrypt')
			->willReturn('123456');

		$this->store->authenticate($params);
	}

	public function testSetSession(): void {
		$session = $this->createMock(ISession::class);

		$this->store->setSession($session);
		$this->addToAssertionCount(1);
	}

	public function testGetLoginCredentialsNoTokenProvider(): void {
		$this->store = new Store($this->session, $this->logger, $this->crypto, null);

		$this->expectException(CredentialsUnavailableException::class);

		$this->store->getLoginCredentials();
	}

	public function testGetLoginCredentials(): void {
		$uid = 'uid';
		$user = 'user123';
		$password = 'passme';
		$token = $this->createMock(IToken::class);
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willReturn($token);
		$token->expects($this->once())
			->method('getUID')
			->willReturn($uid);
		$token->expects($this->once())
			->method('getLoginName')
			->willReturn($user);
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, 'sess2233')
			->willReturn($password);
		$expected = new Credentials($uid, $user, $password);

		$creds = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $creds);
	}

	public function testGetLoginCredentialsSessionNotAvailable(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willThrowException(new SessionNotAvailableException());
		$this->expectException(CredentialsUnavailableException::class);

		$this->store->getLoginCredentials();
	}

	public function testGetLoginCredentialsInvalidTokenWithValidSessionCredentials(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode([
				'uid' => 'user123',
				'loginName' => 'userlogin',
				'password' => null,
			]));
		$expected = new Credentials('user123', 'userlogin', null);

		$creds = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $creds);
	}

	public function testGetLoginCredentialsPasswordlessTokenWithValidSessionCredentials(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$token = $this->createMock(IToken::class);
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willReturn($token);
		$token->expects($this->once())
			->method('getUID')
			->willReturn('user123');
		$token->expects($this->once())
			->method('getLoginName')
			->willReturn('userlogin');
		$this->tokenProvider->expects($this->once())
			->method('getPassword')
			->with($token, 'sessid')
			->willThrowException(new PasswordlessTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode([
				'uid' => 'user123',
				'loginName' => 'userlogin',
				'password' => null,
			]));
		$expected = new Credentials('user123', 'userlogin', null);

		$creds = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $creds);
	}

	public function testGetLoginCredentialsMissingSessionCredentials(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(false);

		$this->expectException(CredentialsUnavailableException::class);
		$this->expectExceptionMessage('No valid login credentials in session');

		$this->store->getLoginCredentials();
	}

	public function testGetLoginCredentialsSessionCredentialsMissingFields(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode([
				'uid' => 'user123',
			]));

		$this->expectException(CredentialsUnavailableException::class);
		$this->expectExceptionMessage('Session credentials missing required fields');

		$this->store->getLoginCredentials();
	}

	public function testGetLoginCredentialsSessionCredentialsDecrypt(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode([
				'uid' => 'user123',
				'loginName' => 'userlogin',
				'password' => 'encrypted',
			]));
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willReturn('decrypted');
		$expected = new Credentials('user123', 'userlogin', 'decrypted');

		$creds = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $creds);
	}

	public function testGetLoginCredentialsSessionCredentialsDecryptException(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode([
				'uid' => 'user123',
				'loginName' => 'userlogin',
				'password' => 'encrypted',
			]));
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willThrowException(new \Exception());
		$expected = new Credentials('user123', 'userlogin', 'encrypted');

		$creds = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $creds);
	}

	public function testGetLoginCredentialsSessionCredentialsPasswordNull(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode([
				'uid' => 'user123',
				'loginName' => 'userlogin',
				'password' => null,
			]));
		$expected = new Credentials('user123', 'userlogin', null);

		$creds = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $creds);
	}

	public function testGetLoginCredentialsInvalidJson(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn('{not valid json');

		$this->expectException(CredentialsUnavailableException::class);
		$this->expectExceptionMessage('Session credentials could not be decoded');

		$this->store->getLoginCredentials();
	}

	public function testGetLoginCredentialsNonArrayDecodedCredentials(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sessid');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sessid')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with('login_credentials')
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with('login_credentials')
			->willReturn(json_encode('just a string'));

		$this->expectException(CredentialsUnavailableException::class);
		$this->expectExceptionMessage('Session credentials could not be decoded');

		$this->store->getLoginCredentials();
	}
}
