<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Security;

use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCA\DAV\DAV\Security\RateLimiting;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\RateLimiting\ILimiter;
use OCP\Security\RateLimiting\IRateLimitExceededException;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RateLimitingTest extends TestCase {
	private IUserSession $userSession;
	private IAppConfig&MockObject $config;
	private ILimiter&MockObject $limiter;
	private RateLimiting $rateLimiting;
	private string $userId = 'user123';

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->limiter = $this->createMock(ILimiter::class);

		$this->rateLimiting = new RateLimiting(
			$this->userSession,
			$this->config,
			$this->limiter,
		);
	}

	public function testNoUserObject(): void {
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn(null);
		$this->limiter->expects($this->never())
			->method('registerUserRequest');

		$this->rateLimiting->check();
	}

	public function testRegisterShareRequest(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->config->method('getValueInt')
			->willReturnCallback(static function (string $app, string $key, int $default): int {
				return match ($key) {
					'rateLimitShareAddressbookOrCalendar' => 7,
					'rateLimitPeriodShareAddressbookOrCalendar' => 600,
					default => $default,
				};
			});
		$this->limiter->expects($this->once())
			->method('registerUserRequest')
			->with(
				'share-addressbook-or-calendar',
				7,
				600,
				$user,
			);

		$this->rateLimiting->check();
	}

	public function testShareRequestRateLimitExceeded(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->config->method('getValueInt')
			->willReturnArgument(2);
		$this->limiter->expects($this->once())
			->method('registerUserRequest')
			->with(
				'share-addressbook-or-calendar',
				20,
				3600,
				$user,
			)
			->willThrowException($this->createMock(IRateLimitExceededException::class));

		$this->expectException(TooManyRequests::class);

		$this->rateLimiting->check();
	}
}
