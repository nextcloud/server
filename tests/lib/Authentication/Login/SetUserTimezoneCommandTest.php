<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\SetUserTimezoneCommand;
use OCP\IConfig;
use OCP\ISession;
use PHPUnit\Framework\MockObject\MockObject;

class SetUserTimezoneCommandTest extends ALoginCommandTest {
	/** @var IConfig|MockObject */
	private $config;

	/** @var ISession|MockObject */
	private $session;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(ISession::class);

		$this->cmd = new SetUserTimezoneCommand(
			$this->config,
			$this->session
		);
	}

	public function testProcessNoTimezoneSet(): void {
		$data = $this->getLoggedInLoginData();
		$this->config->expects($this->never())
			->method('setUserValue');
		$this->session->expects($this->never())
			->method('set');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcess(): void {
		$data = $this->getLoggedInLoginDataWithTimezone();
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($this->username);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->username,
				'core',
				'timezone',
				$this->timezone
			);
		$this->session->expects($this->once())
			->method('set')
			->with(
				'timezone',
				$this->timeZoneOffset
			);

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}
}
