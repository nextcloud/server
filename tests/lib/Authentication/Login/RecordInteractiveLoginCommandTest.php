<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\RecordInteractiveLoginCommand;
use OC\User\LastInteractiveLogin;
use PHPUnit\Framework\MockObject\MockObject;

class RecordInteractiveLoginCommandTest extends ALoginTestCommand {
	/** @var LastInteractiveLogin|MockObject */
	private $lastInteractiveLogin;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->lastInteractiveLogin = $this->createMock(LastInteractiveLogin::class);

		$this->cmd = new RecordInteractiveLoginCommand(
			$this->lastInteractiveLogin
		);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->lastInteractiveLogin->expects($this->once())
			->method('record')
			->with($this->user);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
