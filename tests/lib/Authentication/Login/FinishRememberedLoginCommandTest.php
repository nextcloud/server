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
use PHPUnit\Framework\MockObject\MockObject;

class FinishRememberedLoginCommandTest extends ALoginCommandTest {
	/** @var Session|MockObject */
	private $userSession;
	/** @var IConfig|MockObject */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->config = $this->createMock(IConfig::class);

		$this->cmd = new FinishRememberedLoginCommand(
			$this->userSession,
			$this->config
		);
	}

	public function testProcessNotRememberedLogin(): void {
		$data = $this->getLoggedInLoginData();
		$data->setRememberLogin(false);
		$this->userSession->expects($this->never())
			->method('createRememberMeToken');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('auto_logout', false)
			->willReturn(false);
		$this->userSession->expects($this->once())
			->method('createRememberMeToken')
			->with($this->user);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessNotRemeberedLoginWithAutologout(): void {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('auto_logout', false)
			->willReturn(true);
		$this->userSession->expects($this->never())
			->method('createRememberMeToken');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
