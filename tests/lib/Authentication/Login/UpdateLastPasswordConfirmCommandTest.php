<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\UpdateLastPasswordConfirmCommand;
use OCP\ISession;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateLastPasswordConfirmCommandTest extends ALoginCommandTest {
	/** @var ISession|MockObject */
	private $session;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);

		$this->cmd = new UpdateLastPasswordConfirmCommand(
			$this->session
		);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1234);
		$this->session->expects($this->once())
			->method('set')
			->with(
				'last-password-confirm',
				1234
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
