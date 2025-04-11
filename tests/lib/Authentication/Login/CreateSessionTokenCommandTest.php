<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\CreateSessionTokenCommand;
use OC\Authentication\Token\IToken;
use OC\User\Session;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class CreateSessionTokenCommandTest extends ALoginCommandTest {
	/** @var IConfig|MockObject */
	private $config;

	/** @var Session|MockObject */
	private $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(Session::class);

		$this->cmd = new CreateSessionTokenCommand(
			$this->config,
			$this->userSession
		);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->once())
			->method('getSystemValueInt')
			->with(
				'remember_login_cookie_lifetime',
				60 * 60 * 24 * 15
			)
			->willReturn(100);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn($this->username);
		$this->userSession->expects($this->once())
			->method('createSessionToken')
			->with(
				$this->request,
				$this->username,
				$this->username,
				$this->password,
				IToken::REMEMBER
			);
		$this->userSession->expects($this->once())
			->method('updateTokens')
			->with(
				$this->username,
				$this->password
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessDoNotRemember(): void {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->once())
			->method('getSystemValueInt')
			->with(
				'remember_login_cookie_lifetime',
				60 * 60 * 24 * 15
			)
			->willReturn(0);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn($this->username);
		$this->userSession->expects($this->once())
			->method('createSessionToken')
			->with(
				$this->request,
				$this->username,
				$this->username,
				$this->password,
				IToken::DO_NOT_REMEMBER
			);
		$this->userSession->expects($this->once())
			->method('updateTokens')
			->with(
				$this->username,
				$this->password
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertFalse($data->isRememberLogin());
	}
}
