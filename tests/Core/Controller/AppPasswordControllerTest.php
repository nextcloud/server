<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Core\Controller\AppPasswordController;
use OC\User\Session;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\Exceptions\PasswordUnavailableException;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AppPasswordControllerTest extends TestCase {
	/** @var ISession|MockObject */
	private $session;

	/** @var ISecureRandom|MockObject */
	private $random;

	/** @var IProvider|MockObject */
	private $tokenProvider;

	/** @var IStore|MockObject */
	private $credentialStore;

	/** @var IRequest|MockObject */
	private $request;

	/** @var IEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
	private $eventDispatcher;

	/** @var Session|MockObject */
	private $userSession;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IThrottler|MockObject */
	private $throttler;

	/** @var AppPasswordController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->credentialStore = $this->createMock(IStore::class);
		$this->request = $this->createMock(IRequest::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->userSession = $this->createMock(Session::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->throttler = $this->createMock(IThrottler::class);

		$this->controller = new AppPasswordController(
			'core',
			$this->request,
			$this->session,
			$this->random,
			$this->tokenProvider,
			$this->credentialStore,
			$this->eventDispatcher,
			$this->userSession,
			$this->userManager,
			$this->throttler
		);
	}

	public function testGetAppPasswordWithAppPassword(): void {
		$this->session->method('exists')
			->with('app_password')
			->willReturn(true);

		$this->expectException(OCSForbiddenException::class);

		$this->controller->getAppPassword();
	}

	public function testGetAppPasswordNoLoginCreds(): void {
		$this->session->method('exists')
			->with('app_password')
			->willReturn(false);
		$this->credentialStore->method('getLoginCredentials')
			->willThrowException(new CredentialsUnavailableException());

		$this->expectException(OCSForbiddenException::class);

		$this->controller->getAppPassword();
	}

	public function testGetAppPassword(): void {
		$credentials = $this->createMock(ICredentials::class);

		$this->session->method('exists')
			->with('app_password')
			->willReturn(false);
		$this->credentialStore->method('getLoginCredentials')
			->willReturn($credentials);
		$credentials->method('getUid')
			->willReturn('myUID');
		$credentials->method('getPassword')
			->willReturn('myPassword');
		$credentials->method('getLoginName')
			->willReturn('myLoginName');
		$this->request->method('getHeader')
			->with('user-agent')
			->willReturn('myUA');
		$this->random->method('generate')
			->with(
				72,
				ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS
			)->willReturn('myToken');

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with(
				'myToken',
				'myUID',
				'myLoginName',
				'myPassword',
				'myUA',
				IToken::PERMANENT_TOKEN,
				IToken::DO_NOT_REMEMBER
			);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped');

		$this->controller->getAppPassword();
	}

	public function testGetAppPasswordNoPassword(): void {
		$credentials = $this->createMock(ICredentials::class);

		$this->session->method('exists')
			->with('app_password')
			->willReturn(false);
		$this->credentialStore->method('getLoginCredentials')
			->willReturn($credentials);
		$credentials->method('getUid')
			->willReturn('myUID');
		$credentials->method('getPassword')
			->willThrowException(new PasswordUnavailableException());
		$credentials->method('getLoginName')
			->willReturn('myLoginName');
		$this->request->method('getHeader')
			->with('user-agent')
			->willReturn('myUA');
		$this->random->method('generate')
			->with(
				72,
				ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS
			)->willReturn('myToken');

		$this->tokenProvider->expects($this->once())
			->method('generateToken')
			->with(
				'myToken',
				'myUID',
				'myLoginName',
				null,
				'myUA',
				IToken::PERMANENT_TOKEN,
				IToken::DO_NOT_REMEMBER
			);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped');

		$this->controller->getAppPassword();
	}

	public function testDeleteAppPasswordNoAppPassword(): void {
		$this->session->method('exists')
			->with('app_password')
			->willReturn(false);

		$this->expectException(OCSForbiddenException::class);

		$this->controller->deleteAppPassword();
	}

	public function testDeleteAppPasswordFails(): void {
		$this->session->method('exists')
			->with('app_password')
			->willReturn(true);
		$this->session->method('get')
			->with('app_password')
			->willReturn('myAppPassword');

		$this->tokenProvider->method('getToken')
			->with('myAppPassword')
			->willThrowException(new InvalidTokenException());

		$this->expectException(OCSForbiddenException::class);

		$this->controller->deleteAppPassword();
	}

	public function testDeleteAppPasswordSuccess(): void {
		$this->session->method('exists')
			->with('app_password')
			->willReturn(true);
		$this->session->method('get')
			->with('app_password')
			->willReturn('myAppPassword');

		$token = $this->createMock(IToken::class);
		$this->tokenProvider->method('getToken')
			->with('myAppPassword')
			->willReturn($token);

		$token->method('getUID')
			->willReturn('myUID');
		$token->method('getId')
			->willReturn(42);

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokenById')
			->with(
				'myUID',
				42
			);

		$result = $this->controller->deleteAppPassword();

		$this->assertEquals(new DataResponse(), $result);
	}
}
