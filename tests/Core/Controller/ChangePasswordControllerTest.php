<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Authentication\Token\IProvider;
use OC\User\Session;
use OCA\Settings\Controller\ChangePasswordController;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Token\IToken;
use OCP\HintException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ChangePasswordControllerTest extends \Test\TestCase {
	/** @var string */
	private $userId = 'currentUser';
	/** @var string */
	private $loginName = 'ua1337';
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var Session|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l;
	/** @var ChangePasswordController */
	private $controller;
	private IProvider&MockObject $tokenProvider;
	private ISession&MockObject $session;
	private LoggerInterface&MockObject $logger;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(\OC\User\Manager::class);
		$this->userSession = $this->createMock(Session::class);
		$this->groupManager = $this->createMock(\OC\Group\Manager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')->willReturnArgument(0);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->session = $this->createMock(ISession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject $request */
		$request = $this->createMock(IRequest::class);

		$this->controller = new ChangePasswordController(
			'core',
			$request,
			$this->userId,
			$this->userManager,
			$this->userSession,
			$this->groupManager,
			$this->appManager,
			$this->l,
			$this->tokenProvider,
			$this->session,
			$this->logger,
		);
	}

	public function testChangePersonalPasswordWrongPassword(): void {
		$this->userSession->expects($this->once())
			->method('getLoginName')
			->willReturn($this->loginName);

		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->loginName, 'old')
			->willReturn(false);

		$expects = new JSONResponse([
			'status' => 'error',
			'data' => [
				'message' => 'Wrong password',
			],
		]);
		$expects->throttle();

		$actual = $this->controller->changePersonalPassword('old', 'new');
		$this->assertEquals($expects, $actual);
	}

	public function testChangePersonalPasswordCommonPassword(): void {
		$this->userSession->expects($this->once())
			->method('getLoginName')
			->willReturn($this->loginName);

		$user = $this->getMockBuilder(IUser::class)->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->loginName, 'old')
			->willReturn($user);

		$user->expects($this->once())
			->method('setPassword')
			->with('new')
			->willThrowException(new HintException('Common password'));

		$expects = new JSONResponse([
			'status' => 'error',
			'data' => [
				'message' => 'Common password',
			],
		]);

		$actual = $this->controller->changePersonalPassword('old', 'new');
		$this->assertEquals($expects, $actual);
	}

	public function testChangePersonalPasswordNoNewPassword(): void {
		$this->userSession->expects($this->once())
			->method('getLoginName')
			->willReturn($this->loginName);

		$user = $this->getMockBuilder(IUser::class)->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->loginName, 'old')
			->willReturn($user);

		$expects = [
			'status' => 'error',
			'data' => [
				'message' => 'Unable to change personal password',
			],
		];

		$res = $this->controller->changePersonalPassword('old');

		$this->assertEquals($expects, $res->getData());
	}

	public function testChangePersonalPasswordCantSetPassword(): void {
		$this->userSession->expects($this->once())
			->method('getLoginName')
			->willReturn($this->loginName);

		$user = $this->getMockBuilder(IUser::class)->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->loginName, 'old')
			->willReturn($user);

		$user->expects($this->once())
			->method('setPassword')
			->with('new')
			->willReturn(false);

		$expects = new JSONResponse([
			'status' => 'error',
			'data' => [
				'message' => 'Unable to change personal password',
			],
		]);

		$actual = $this->controller->changePersonalPassword('old', 'new');
		$this->assertEquals($expects, $actual);
	}

	public function testChangePersonalPassword(): void {
		$this->userSession->expects($this->once())
			->method('getLoginName')
			->willReturn($this->loginName);

		$user = $this->getMockBuilder(IUser::class)->getMock();
		$this->userManager->expects($this->once())
			->method('checkPassword')
			->with($this->loginName, 'old')
			->willReturn($user);

		$user->expects($this->once())
			->method('setPassword')
			->with('new')
			->willReturn(true);

		$this->userSession->expects($this->once())
			->method('updateSessionTokenPassword')
			->with('new');

		$expects = new JSONResponse([
			'status' => 'success',
			'data' => [
				'message' => 'Saved',
				'revokedTokenIds' => [],
			],
		]);

		$actual = $this->controller->changePersonalPassword('old', 'new');
		$this->assertEquals($expects, $actual);
	}

	private function arrangeSuccessfulChange(): void {
		$this->userSession->method('getLoginName')->willReturn($this->loginName);
		$user = $this->createMock(IUser::class);
		$this->userManager->method('checkPassword')
			->with($this->loginName, 'old')
			->willReturn($user);
		$user->method('setPassword')->with('new')->willReturn(true);
	}

	private function mockCurrentSessionToken(int $id): void {
		$token = $this->createMock(IToken::class);
		$token->method('getId')->willReturn($id);
		$this->session->method('getId')->willReturn('sessionid');
		$this->tokenProvider->method('getToken')->with('sessionid')->willReturn($token);
	}

	public function testChangePersonalPasswordKeepsOtherSessionsByDefault(): void {
		$this->arrangeSuccessfulChange();

		$this->tokenProvider->expects($this->never())
			->method('invalidateTokensOfUserExcept');

		$data = $this->controller->changePersonalPassword('old', 'new')->getData();
		$this->assertSame('success', $data['status']);
		$this->assertSame([], $data['data']['revokedTokenIds']);
	}

	public function testChangePersonalPasswordRevokesOtherSessionsWhenAsked(): void {
		$this->arrangeSuccessfulChange();
		$this->mockCurrentSessionToken(10);

		$this->tokenProvider->expects($this->once())
			->method('invalidateTokensOfUserExcept')
			->with($this->userId, 10)
			->willReturn([11, 12]);

		$data = $this->controller->changePersonalPassword('old', 'new', true)->getData();
		$this->assertSame('success', $data['status']);
		$this->assertSame([11, 12], $data['data']['revokedTokenIds']);
	}

	public function testChangePersonalPasswordSkipsTheRevokeWithoutAUsableSessionToken(): void {
		$this->arrangeSuccessfulChange();
		$this->session->method('getId')->willReturn('sessionid');
		$this->tokenProvider->method('getToken')
			->willThrowException(new InvalidTokenException('gone'));

		// Nothing is revoked rather than signing the user out of their own request.
		$this->tokenProvider->expects($this->never())
			->method('invalidateTokensOfUserExcept');
		$this->logger->expects($this->once())->method('warning');

		$data = $this->controller->changePersonalPassword('old', 'new', true)->getData();
		$this->assertSame('success', $data['status']);
	}

	public function testChangePersonalPasswordDoesNotRevokeWhenTheChangeFails(): void {
		$this->userSession->method('getLoginName')->willReturn($this->loginName);
		$user = $this->createMock(IUser::class);
		$this->userManager->method('checkPassword')->willReturn($user);
		$user->method('setPassword')->willReturn(false);

		$this->tokenProvider->expects($this->never())
			->method('invalidateTokensOfUserExcept');

		$data = $this->controller->changePersonalPassword('old', 'new', true)->getData();
		$this->assertSame('error', $data['status']);
	}
}
