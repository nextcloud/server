<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\ClearLostPasswordTokensCommand;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ClearLostPasswordTokensCommandTest extends ALoginCommandTest {
	/** @var IConfig|MockObject */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);

		$this->cmd = new ClearLostPasswordTokensCommand(
			$this->config
		);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($this->username);
		$this->config->expects($this->once())
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
