<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CardDAV\Security;

use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Security\CardDavRateLimitingPlugin;
use OCA\DAV\Connector\Sabre\Exception\TooManyRequests;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Test\TestCase;

class CardDavRateLimitingPluginTest extends TestCase {

	private Limiter&MockObject $limiter;
	private CardDavBackend&MockObject $cardDavBackend;
	private IUserManager&MockObject $userManager;
	private LoggerInterface&MockObject $logger;
	private IAppConfig&MockObject $config;
	private string $userId = 'user123';
	private CardDavRateLimitingPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->limiter = $this->createMock(Limiter::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->cardDavBackend = $this->createMock(CardDavBackend::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->plugin = new CardDavRateLimitingPlugin(
			$this->limiter,
			$this->userManager,
			$this->cardDavBackend,
			$this->logger,
			$this->config,
			$this->userId,
		);
	}

	public function testNoUserObject(): void {
		$this->limiter->expects(self::never())
			->method('registerUserRequest');

		$this->plugin->beforeBind('addressbooks/users/foo/addressbookname');
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

	public function testRegisterAddressBookrCreation(): void {
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
				'carddav-create-address-book',
				10,
				3600,
				$user,
			);

		$this->plugin->beforeBind('addressbooks/users/foo/addressbookname');
	}

	public function testAddressBookCreationRateLimitExceeded(): void {
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
				'carddav-create-address-book',
				10,
				3600,
				$user,
			)
			->willThrowException(new RateLimitExceededException());
		$this->expectException(TooManyRequests::class);

		$this->plugin->beforeBind('addressbooks/users/foo/addressbookname');
	}

	public function testAddressBookLimitReached(): void {
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
				'carddav-create-address-book',
				10,
				3600,
				$user,
			);
		$this->cardDavBackend->expects(self::once())
			->method('getAddressBooksForUserCount')
			->with('principals/users/user123')
			->willReturn(11);
		$this->expectException(Forbidden::class);

		$this->plugin->beforeBind('addressbooks/users/foo/addressbookname');
	}

}
