<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\User\Session;
use OCA\Settings\Controller\ChangePasswordController;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\HintException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;

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

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(\OC\User\Manager::class);
		$this->userSession = $this->createMock(Session::class);
		$this->groupManager = $this->createMock(\OC\Group\Manager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')->willReturnArgument(0);

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
			$this->l
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
			],
		]);

		$actual = $this->controller->changePersonalPassword('old', 'new');
		$this->assertEquals($expects, $actual);
	}
}
