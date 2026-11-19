<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\DateTimeZone;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class DateTimeZoneTest extends TestCase {
	public function testGetTimeZoneFromUserSession(): void {
		$config = $this->createMock(IConfig::class);
		$session = $this->createMock(ISession::class);
		$logger = $this->createMock(LoggerInterface::class);
		$user = $this->createMock(IUser::class);
		$userSession = $this->createMock(IUserSession::class);

		$user->method('getUID')->willReturn('alice');
		$userSession->method('getUser')->willReturn($user);
		$config->method('getUserValue')
			->with('alice', 'core', 'timezone', '')
			->willReturn('Europe/Berlin');

		$dtz = new DateTimeZone($config, $session, $logger, $userSession);
		$this->assertSame('Europe/Berlin', $dtz->getTimeZone()->getName());
	}

	public function testGetTimeZoneFallbackToSessionUserId(): void {
		$config = $this->createMock(IConfig::class);
		$session = $this->createMock(ISession::class);
		$logger = $this->createMock(LoggerInterface::class);
		$userSession = $this->createMock(IUserSession::class);

		$userSession->method('getUser')->willReturn(null);
		$session->method('get')->with('user_id')->willReturn('bob');
		$config->method('getUserValue')
			->with('bob', 'core', 'timezone', '')
			->willReturn('America/New_York');

		$dtz = new DateTimeZone($config, $session, $logger, $userSession);
		$this->assertSame('America/New_York', $dtz->getTimeZone()->getName());
	}

	public function testGetTimeZoneExplicitUserIdOverridesUserSession(): void {
		$config = $this->createMock(IConfig::class);
		$session = $this->createMock(ISession::class);
		$logger = $this->createMock(LoggerInterface::class);
		$user = $this->createMock(IUser::class);
		$userSession = $this->createMock(IUserSession::class);

		$user->method('getUID')->willReturn('alice');
		$userSession->method('getUser')->willReturn($user);
		$config->method('getUserValue')
			->with('charlie', 'core', 'timezone', '')
			->willReturn('Asia/Tokyo');

		$dtz = new DateTimeZone($config, $session, $logger, $userSession);
		$this->assertSame('Asia/Tokyo', $dtz->getTimeZone(false, 'charlie')->getName());
	}
}
