<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\FinishRememberedLoginCommand;
use OC\User\Session;
use OCP\IConfig;

class FinishRememberedLoginCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(FinishRememberedLoginCommand::class);
	}

	public function testProcessNotRememberedLogin(): void {
		$data = $this->getLoggedInLoginData();
		$data->setRememberLogin(false);
		$this->mocks[Session::class]->expects($this->never())
			->method('createRememberMeToken');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[IConfig::class]->expects($this->once())
			->method('getSystemValueBool')
			->with('auto_logout', false)
			->willReturn(false);
		$this->mocks[Session::class]->expects($this->once())
			->method('createRememberMeToken')
			->with($this->user);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessNotRemeberedLoginWithAutologout(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[IConfig::class]->expects($this->once())
			->method('getSystemValueBool')
			->with('auto_logout', false)
			->willReturn(true);
		$this->mocks[Session::class]->expects($this->never())
			->method('createRememberMeToken');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
