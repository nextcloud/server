<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\LoggedInCheckCommand;
use OC\Core\Controller\LoginController;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class LoggedInCheckCommandTest extends ALoginCommandTest {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventDispatcher|MockObject */
	private $dispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);

		$this->cmd = new LoggedInCheckCommand(
			$this->logger,
			$this->dispatcher
		);
	}

	public function testProcessSuccessfulLogin(): void {
		$data = $this->getLoggedInLoginData();

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessFailedLogin(): void {
		$data = $this->getFailedLoginData();
		$this->logger->expects($this->once())
			->method('warning');

		$result = $this->cmd->process($data);

		$this->assertFalse($result->isSuccess());
		$this->assertSame(LoginController::LOGIN_MSG_INVALIDPASSWORD, $result->getErrorMessage());
	}
}
