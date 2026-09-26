<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\UpdateLastPasswordConfirmCommand;
use OCP\ISession;

class UpdateLastPasswordConfirmCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(UpdateLastPasswordConfirmCommand::class);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1234);
		$this->mocks[ISession::class]->expects($this->once())
			->method('set')
			->with(
				'last-password-confirm',
				1234
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
