<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\UserDisabledCheckCommand;
use OC\Core\Controller\LoginController;
use OCP\IUserManager;

class UserDisabledCheckCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(UserDisabledCheckCommand::class);
	}

	public function testProcessNonExistingUser(): void {
		$data = $this->getBasicLoginData();
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with($this->username)
			->willReturn(null);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessDisabledUser(): void {
		$data = $this->getBasicLoginData();
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with($this->username)
			->willReturn($this->user);
		$this->user->expects($this->once())
			->method('isEnabled')
			->willReturn(false);

		$result = $this->cmd->process($data);

		$this->assertFalse($result->isSuccess());
		$this->assertSame(LoginController::LOGIN_MSG_USERDISABLED, $result->getErrorMessage());
	}

	public function testProcess(): void {
		$data = $this->getBasicLoginData();
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with($this->username)
			->willReturn($this->user);
		$this->user->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
