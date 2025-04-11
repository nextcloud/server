<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\CompleteLoginCommand;
use OC\User\Session;
use PHPUnit\Framework\MockObject\MockObject;

class CompleteLoginCommandTest extends ALoginCommandTest {
	/** @var Session|MockObject */
	private $session;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(Session::class);

		$this->cmd = new CompleteLoginCommand(
			$this->session
		);
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginData();
		$this->session->expects($this->once())
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
