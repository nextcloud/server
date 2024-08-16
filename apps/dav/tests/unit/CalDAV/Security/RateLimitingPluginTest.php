<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Security;

use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Security\RateLimitingPlugin;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Test\TestCase;

class RateLimitingPluginTest extends TestCase {

	private Limiter|MockObject $limiter;
	private CalDavBackend|MockObject $caldavBackend;
	private IUserManager|MockObject $userManager;
	private LoggerInterface|MockObject $logger;
	private IAppConfig|MockObject $config;
	private string $userId = 'user123';
	private RateLimitingPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->limiter = $this->createMock(Limiter::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->caldavBackend = $this->createMock(CalDavBackend::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->plugin = new RateLimitingPlugin(
			$this->limiter,
			$this->userManager,
			$this->caldavBackend,
			$this->logger,
			$this->config,
			$this->userId,
		);
	}

	public function testNoUserObject(): void {
		$this->limiter->expects(self::never())
			->method('registerUserRequest');

		$this->plugin->beforeBind('calendars/foo/cal');
	}

	public function testUnrelated(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects(self::once())
			->method('get')
			->with($this->userId)
			->willReturn($user);
		$this->limiter->expects(self::never())
			->method('registerUserRequest');

		$this->plugin->beforeBind('foo/bar');
	}

	public function testRegisterCalendarCreation(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects(self::once())
			->method('get')
			->with($this->userId)
			->willReturn($user);
		$this->config
			->method('getValueInt')
			->with('dav')
			->willReturnArgument(2);
		$this->limiter->expects(self::once())
			->method('registerUserRequest')
			->with(
				'caldav-create-calendar',
				10,
				3600,
				$user,
			);

		$this->plugin->beforeBind('calendars/foo/cal');
	}

	public function testCalendarCreationRateLimitExceeded(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects(self::once())
			->method('get')
			->with($this->userId)
			->willReturn($user);
		$this->config
			->method('getValueInt')
			->with('dav')
			->willReturnArgument(2);
		$this->limiter->expects(self::once())
			->method('registerUserRequest')
			->with(
				'caldav-create-calendar',
				10,
				3600,
				$user,
			)
			->willThrowException(new RateLimitExceededException());
		$this->expectException(TooManyRequests::class);

		$this->plugin->beforeBind('calendars/foo/cal');
	}

	public function testCalendarLimitReached(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects(self::once())
			->method('get')
			->with($this->userId)
			->willReturn($user);
		$user->method('getUID')->willReturn('user123');
		$this->config
			->method('getValueInt')
			->with('dav')
			->willReturnArgument(2);
		$this->limiter->expects(self::once())
			->method('registerUserRequest')
			->with(
				'caldav-create-calendar',
				10,
				3600,
				$user,
			);
		$this->caldavBackend->expects(self::once())
			->method('getCalendarsForUserCount')
			->with('principals/users/user123')
			->willReturn(27);
		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUserCount')
			->with('principals/users/user123')
			->willReturn(3);
		$this->expectException(Forbidden::class);

		$this->plugin->beforeBind('calendars/foo/cal');
	}

	public function testNoCalendarsSubscriptsLimit(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects(self::once())
			->method('get')
			->with($this->userId)
			->willReturn($user);
		$user->method('getUID')->willReturn('user123');
		$this->config
			->method('getValueInt')
			->with('dav')
			->willReturnCallback(function ($app, $key, $default) {
				switch ($key) {
					case 'maximumCalendarsSubscriptions':
						return -1;
					default:
						return $default;
				}
			});
		$this->limiter->expects(self::once())
			->method('registerUserRequest')
			->with(
				'caldav-create-calendar',
				10,
				3600,
				$user,
			);
		$this->caldavBackend->expects(self::never())
			->method('getCalendarsForUserCount')
			->with('principals/users/user123')
			->willReturn(27);
		$this->caldavBackend->expects(self::never())
			->method('getSubscriptionsForUserCount')
			->with('principals/users/user123')
			->willReturn(3);

		$this->plugin->beforeBind('calendars/foo/cal');
	}

}
