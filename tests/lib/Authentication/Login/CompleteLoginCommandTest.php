<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\CompleteLoginCommand;
use OC\User\Session;

class CompleteLoginCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(CompleteLoginCommand::class);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->mocks[Session::class]->expects($this->once())
			->method('completeLogin')
			->with(
				$this->user,
				$this->equalTo(
					[
						'loginName' => $this->username,
						'password' => $this->password,
					]
				)
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
