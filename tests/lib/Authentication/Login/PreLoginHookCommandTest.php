<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\PreLoginHookCommand;
use OC\User\Manager;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

class PreLoginHookCommandTest extends ALoginCommandTest {
	/** @var IUserManager|MockObject */
	private $userManager;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(Manager::class);

		$this->cmd = new PreLoginHookCommand(
			$this->userManager
		);
	}

	public function testProcess(): void {
		$data = $this->getBasicLoginData();
		$this->userManager->expects($this->once())
			->method('emit')
			->with(
				'\OC\User',
				'preLogin',
				[
					$this->username,
					$this->password,
				]
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
