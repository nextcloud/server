<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\Tests\unit\CalDAV\Security;

use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Security\RateLimitingPlugin;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IConfig;
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
	private IConfig|MockObject $config;
	private string $userId = 'user123';
	private RateLimitingPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->limiter = $this->createMock(Limiter::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->caldavBackend = $this->createMock(CalDavBackend::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = $this->createMock(IConfig::class);
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
			->method('getAppValue')
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
			->method('getAppValue')
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
			->method('getAppValue')
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
			->method('getAppValue')
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
