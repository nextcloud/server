<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\UidLoginCommand;
use OC\User\Manager;
use PHPUnit\Framework\MockObject\MockObject;

class UidLoginCommandTest extends ALoginCommandTest {
	/** @var Manager|MockObject */
	private $userManager;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(Manager::class);

		$this->cmd = new UidLoginCommand(
			$this->userManager
		);
	}

	public function testProcessFailingLogin(): void {
		$data = $this->getBasicLoginData();
		$this->userManager->expects($this->once())
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
		$this->userManager->expects($this->once())
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
