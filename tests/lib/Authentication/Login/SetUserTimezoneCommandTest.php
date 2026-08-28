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

class SetUserTimezoneCommandTest extends ALoginTestCommand {

	private IConfig&MockObject $config;

	private ISession&MockObject $session;

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

	/**
	 * Debian and Ubuntu ship the tz database's backward links in a separate
	 * tzdata-legacy package, so pick an alias this platform actually knows
	 * instead of hardcoding one.
	 */
	private static function findBackwardCompatibleTimezone(): ?string {
		$aliases = array_diff(
			\DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC),
			\DateTimeZone::listIdentifiers(),
		);
		return $aliases === [] ? null : reset($aliases);
	}

	public static function dataAcceptedTimezone(): array {
		return [
			'primary identifier' => ['Europe/Vienna'],
			'backward compatible alias' => [self::findBackwardCompatibleTimezone()],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataAcceptedTimezone')]
	public function testProcess(?string $timezone): void {
		if ($timezone === null) {
			$this->markTestSkipped('No backward compatible timezone aliases in this platform\'s tz database');
		}

		$data = $this->getLoggedInLoginDataWithTimezone($timezone);
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($this->username);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with(
				$this->username,
				'core',
				'timezone',
				''
			)
			->willReturn('');
		$this->config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->username,
				'core',
				'timezone',
				$timezone
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

	public function testProcessUnknownTimezone(): void {
		$data = $this->getLoggedInLoginDataWithTimezone('Mars/Olympus_Mons');
		$this->config->expects($this->never())
			->method('setUserValue');
		$this->session->expects($this->never())
			->method('set');

		$result = $this->cmd->process($data);

		$this->assertTrue($result->isSuccess());
	}

	public function testProcessAlreadySet(): void {
		$data = $this->getLoggedInLoginDataWithTimezone();
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn($this->username);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with(
				$this->username,
				'core',
				'timezone',
				'',
			)
			->willReturn('Europe/Berlin');
		$this->config->expects($this->never())
			->method('setUserValue');
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
