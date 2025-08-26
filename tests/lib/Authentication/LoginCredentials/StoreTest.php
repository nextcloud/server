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

	public function testGetLoginCredentialsInvalidToken(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willThrowException(new InvalidTokenException());
		$this->expectException(CredentialsUnavailableException::class);

		$this->store->getLoginCredentials();
	}

	public function testGetLoginCredentialsPartialCredentialsAndSessionName(): void {
		$uid = 'id987';
		$user = 'user987';
		$password = '7389374';

		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with($this->equalTo('login_credentials'))
			->willReturn(true);
		$this->crypto->expects($this->once())
			->method('decrypt')
			->willReturn($password);
		$this->session->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				[
					'login_credentials',
					json_encode([
						'uid' => $uid,
						'password' => $password,
					])
				],
				[
					'loginname',
					$user,
				],
			]);
		$expected = new Credentials($uid, $user, $password);

		$actual = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $actual);
	}

	public function testGetLoginCredentialsPartialCredentials(): void {
		$uid = 'id987';
		$password = '7389374';

		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with($this->equalTo('login_credentials'))
			->willReturn(true);
		$this->crypto->expects($this->once())
			->method('decrypt')
			->willReturn($password);
		$this->session->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				[
					'login_credentials',
					json_encode([
						'uid' => $uid,
						'password' => $password,
					])
				],
				[
					'loginname',
					null,
				],
			]);
		$expected = new Credentials($uid, $uid, $password);

		$actual = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $actual);
	}

	public function testGetLoginCredentialsInvalidTokenLoginCredentials(): void {
		$uid = 'id987';
		$user = 'user987';
		$password = '7389374';

		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willThrowException(new InvalidTokenException());
		$this->session->expects($this->once())
			->method('exists')
			->with($this->equalTo('login_credentials'))
			->willReturn(true);
		$this->crypto->expects($this->once())
			->method('decrypt')
			->willReturn($password);
		$this->session->expects($this->once())
			->method('get')
			->with($this->equalTo('login_credentials'))
			->willReturn('{"run":true,"uid":"id987","loginName":"user987","password":"7389374"}');
		$expected = new Credentials($uid, $user, $password);

		$actual = $this->store->getLoginCredentials();

		$this->assertEquals($expected, $actual);
	}

	public function testGetLoginCredentialsPasswordlessToken(): void {
		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willThrowException(new PasswordlessTokenException());
		$this->expectException(CredentialsUnavailableException::class);

		$this->store->getLoginCredentials();
	}

	public function testAuthenticatePasswordlessToken(): void {
		$user = 'user987';
		$password = null;

		$params = [
			'run' => true,
			'loginName' => $user,
			'uid' => $user,
			'password' => $password,
		];

		$this->session->expects($this->once())
			->method('set')
			->with($this->equalTo('login_credentials'), $this->equalTo(json_encode($params)));


		$this->session->expects($this->once())
			->method('getId')
			->willReturn('sess2233');
		$this->tokenProvider->expects($this->once())
			->method('getToken')
			->with('sess2233')
			->willThrowException(new PasswordlessTokenException());

		$this->session->expects($this->once())
			->method('exists')
			->with($this->equalTo('login_credentials'))
			->willReturn(true);
		$this->session->expects($this->once())
			->method('get')
			->with($this->equalTo('login_credentials'))
			->willReturn(json_encode($params));

		$this->store->authenticate($params);
		$actual = $this->store->getLoginCredentials();

		$expected = new Credentials($user, $user, $password);
		$this->assertEquals($expected, $actual);
	}
}
