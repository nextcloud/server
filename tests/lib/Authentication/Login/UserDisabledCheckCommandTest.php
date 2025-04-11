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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UserDisabledCheckCommandTest extends ALoginCommandTest {
	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->cmd = new UserDisabledCheckCommand(
			$this->userManager,
			$this->logger
		);
	}

	public function testProcessNonExistingUser(): void {
		$data = $this->getBasicLoginData();
		$this->userManager->expects($this->once())
			->method('get')
			->with($this->username)
			->willReturn(null);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessDisabledUser(): void {
		$data = $this->getBasicLoginData();
		$this->userManager->expects($this->once())
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
		$this->userManager->expects($this->once())
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
