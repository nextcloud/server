<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\UidLoginCommand;
use OC\User\Manager;

class UidLoginCommandTest extends ALoginTestCommand {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->cmd = $this->createInstanceWithMocks(UidLoginCommand::class);
	}

	public function testProcessFailingLogin(): void {
		$data = $this->getBasicLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('checkPasswordNoLogging')
			->with(
				$this->username,
				$this->password
			)
			->willReturn(false);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertFalse($data->getUser());
	}

	public function testProcess(): void {
		$data = $this->getBasicLoginData();
		$this->mocks[Manager::class]->expects($this->once())
			->method('checkPasswordNoLogging')
			->with(
				$this->username,
				$this->password
			)
			->willReturn($this->user);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals($this->user, $data->getUser());
	}
}
