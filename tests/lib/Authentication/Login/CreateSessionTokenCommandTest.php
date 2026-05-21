<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Login;

use OC\Authentication\Login\CreateSessionTokenCommand;
use OC\Authentication\Token\IToken;
use OC\User\Session;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class CreateSessionTokenCommandTest extends ALoginCommandTest {
	private IConfig&MockObject $config;
	private Session&MockObject $userSession;
	private IURLGenerator&MockObject $urlGenerator;
	private ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(Session::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->cmd = new CreateSessionTokenCommand(
			$this->config,
			$this->userSession,
			$this->urlGenerator,
			$this->timeFactory,
		);
	}

	public function testProcess(): void {
		// Just return the route name as path to not return an empty string
		$this->urlGenerator->expects(self::once())
			->method('linkToRoute')
			->willReturnArgument(0);
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
				IToken::REMEMBER,
				null
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
		// Just return the route name as path to not return an empty string
		$this->urlGenerator->expects(self::once())
			->method('linkToRoute')
			->willReturnArgument(0);
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
				IToken::DO_NOT_REMEMBER,
				null
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

	public function testLoginFlowEphemeral(): void {
		$this->redirectUrl = 'EPHEMERAL_ROUTE';
		$this->urlGenerator->expects(self::once())
			->method('linkToRoute')
			->willReturn($this->redirectUrl);
		$this->timeFactory->expects(self::once())
			->method('getTime')
			->willReturn(1000);

		$data = $this->getLoggedInLoginDataWithRedirectUrl();
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
				IToken::REMEMBER,
				1000 + 5 * 60
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
}
