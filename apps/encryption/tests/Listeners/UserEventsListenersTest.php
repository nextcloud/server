<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Tests\Listeners;

use OC\Core\Events\BeforePasswordResetEvent;
use OC\Core\Events\PasswordResetEvent;
use OC\Files\SetupManager;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Listeners\UserEventsListener;
use OCA\Encryption\Services\PassphraseService;
use OCA\Encryption\Session;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lockdown\ILockdownManager;
use OCP\User\Events\BeforePasswordUpdatedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedOutEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class UserEventsListenersTest extends TestCase {

	protected Util&MockObject $util;
	protected Setup&MockObject $userSetup;
	protected Session&MockObject $session;
	protected KeyManager&MockObject $keyManager;
	protected IUserManager&MockObject $userManager;
	protected IUserSession&MockObject $userSession;
	protected SetupManager&MockObject $setupManager;
	protected ILockdownManager&MockObject $lockdownManager;
	protected PassphraseService&MockObject $passphraseService;

	protected UserEventsListener $instance;

	public function setUp(): void {
		parent::setUp();

		$this->util = $this->createMock(Util::class);
		$this->userSetup = $this->createMock(Setup::class);
		$this->session = $this->createMock(Session::class);
		$this->keyManager = $this->createMock(KeyManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->setupManager = $this->createMock(SetupManager::class);
		$this->lockdownManager = $this->createMock(ILockdownManager::class);
		$this->passphraseService = $this->createMock(PassphraseService::class);

		$this->instance = new UserEventsListener(
			$this->util,
			$this->userSetup,
			$this->session,
			$this->keyManager,
			$this->userManager,
			$this->userSession,
			$this->setupManager,
			$this->passphraseService,
			$this->lockdownManager,
		);
	}

	public function testLogin(): void {
		$this->lockdownManager->expects(self::once())
			->method('canAccessFilesystem')
			->willReturn(true);
		$this->userSetup->expects(self::once())
			->method('setupUser')
			->willReturn(true);

		$this->keyManager->expects(self::once())
			->method('init')
			->with('testUser', 'password');

		$this->util->method('isMasterKeyEnabled')->willReturn(false);

		$user = $this->createMock(IUser::class);
		$user->expects(self::any())
			->method('getUID')
			->willReturn('testUser');
		$event = $this->createMock(UserLoggedInEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');

		$this->instance->handle($event);
	}

	public function testLoginMasterKey(): void {
		$this->lockdownManager->expects(self::once())
			->method('canAccessFilesystem')
			->willReturn(true);
		$this->util->method('isMasterKeyEnabled')->willReturn(true);

		$this->userSetup->expects(self::never())
			->method('setupUser');

		$this->keyManager->expects(self::once())
			->method('init')
			->with('testUser', 'password');

		$user = $this->createMock(IUser::class);
		$user->expects(self::any())
			->method('getUID')
			->willReturn('testUser');

		$event = $this->createMock(UserLoggedInEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');

		$this->instance->handle($event);
	}

	public function testLoginNoFilesystemAccess(): void {
		$this->lockdownManager->expects(self::once())
			->method('canAccessFilesystem')
			->willReturn(false);

		$this->userSetup->expects(self::never())
			->method('setupUser');

		$this->setupManager->expects(self::never())
			->method('setupForUser');

		$this->keyManager->expects(self::never())
			->method('init');

		$user = $this->createMock(IUser::class);
		$user->expects(self::any())
			->method('getUID')
			->willReturn('testUser');

		$event = $this->createMock(UserLoggedInEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');

		$this->instance->handle($event);
	}

	public function testLogout(): void {
		$this->session->expects(self::once())
			->method('clear');

		$event = $this->createMock(UserLoggedOutEvent::class);
		$this->instance->handle($event);
	}

	public function testUserCreated(): void {
		$this->userSetup->expects(self::once())
			->method('setupUser')
			->with('testUser', 'password');

		$event = $this->createMock(UserCreatedEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUid')
			->willReturn('testUser');
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');

		$this->instance->handle($event);
	}

	public function testUserDeleted(): void {
		$this->keyManager->expects(self::once())
			->method('deletePublicKey')
			->with('testUser');

		$event = $this->createMock(UserDeletedEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUid')
			->willReturn('testUser');
		$this->instance->handle($event);
	}

	public function testBeforePasswordUpdated(): void {
		$this->passphraseService->expects(self::never())
			->method('setPassphraseForUser');

		$user = $this->createMock(IUser::class);
		$user->expects(self::atLeastOnce())
			->method('canChangePassword')
			->willReturn(true);

		$event = $this->createMock(BeforePasswordUpdatedEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');
		$this->instance->handle($event);
	}

	public function testBeforePasswordUpdated_CannotChangePassword(): void {
		$this->passphraseService->expects(self::once())
			->method('setPassphraseForUser')
			->with('testUser', 'password');

		$user = $this->createMock(IUser::class);
		$user->expects(self::atLeastOnce())
			->method('getUID')
			->willReturn('testUser');
		$user->expects(self::atLeastOnce())
			->method('canChangePassword')
			->willReturn(false);

		$event = $this->createMock(BeforePasswordUpdatedEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');
		$this->instance->handle($event);
	}

	public function testPasswordUpdated(): void {
		$this->passphraseService->expects(self::once())
			->method('setPassphraseForUser')
			->with('testUser', 'password');

		$event = $this->createMock(PasswordUpdatedEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUid')
			->willReturn('testUser');
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');

		$this->instance->handle($event);
	}

	public function testBeforePasswordReset(): void {
		$this->passphraseService->expects(self::once())
			->method('setProcessingReset')
			->with('testUser');

		$event = $this->createMock(BeforePasswordResetEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUid')
			->willReturn('testUser');
		$this->instance->handle($event);
	}

	public function testPasswordReset(): void {
		// backup required
		$this->keyManager->expects(self::once())
			->method('backupUserKeys')
			->with('passwordReset', 'testUser');
		// delete old keys
		$this->keyManager->expects(self::once())
			->method('deleteUserKeys')
			->with('testUser');
		// create new keys
		$this->userSetup->expects(self::once())
			->method('setupUser')
			->with('testUser', 'password');
		// reset ends
		$this->passphraseService->expects(self::once())
			->method('setProcessingReset')
			->with('testUser', false);

		$event = $this->createMock(PasswordResetEvent::class);
		$event->expects(self::atLeastOnce())
			->method('getUid')
			->willReturn('testUser');
		$event->expects(self::atLeastOnce())
			->method('getPassword')
			->willReturn('password');
		$this->instance->handle($event);
	}

}
