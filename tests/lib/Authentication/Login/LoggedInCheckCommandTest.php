<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\LoggedInCheckCommand;
use OC\Core\Controller\LoginController;
use Psr\Log\LoggerInterface;

class LoggedInCheckCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(LoggedInCheckCommand::class);
	}

	public function testProcessSuccessfulLogin(): void {
		$data = $this->getLoggedInLoginData();

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessFailedLogin(): void {
		$data = $this->getFailedLoginData();
		$this->mocks[LoggerInterface::class]->expects($this->once())
			->method('warning');

		$result = $this->cmd->process($data);

		$this->assertFalse($result->isSuccess());
		$this->assertSame(LoginController::LOGIN_MSG_INVALIDPASSWORD, $result->getErrorMessage());
	}
}
