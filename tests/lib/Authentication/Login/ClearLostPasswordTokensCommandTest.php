<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\ClearLostPasswordTokensCommand;
use OCP\IConfig;

class ClearLostPasswordTokensCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(ClearLostPasswordTokensCommand::class);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($this->username);
		$this->mocks[IConfig::class]->expects($this->once())
			->method('deleteUserValue')
			->with(
				$this->username,
				'core',
				'lostpassword'
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
