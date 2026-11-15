<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Tests;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCA\Encryption\Services\PassphraseService;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class PassphraseServiceTest extends TestCase {

	protected Util&MockObject $util;
	protected Crypt&MockObject $crypt;
	protected Session&MockObject $session;
	protected Recovery&MockObject $recovery;
	protected KeyManager&MockObject $keyManager;
	protected IUserManager&MockObject $userManager;
	protected IUserSession&MockObject $userSession;
	protected IEventDispatcher&MockObject $eventDispatcher;

	protected PassphraseService $instance;

	public function setUp(): void {
		parent::setUp();

		$this->util = $this->createMock(Util::class);
		$this->crypt = $this->createMock(Crypt::class);
		$this->session = $this->createMock(Session::class);
		$this->recovery = $this->createMock(Recovery::class);
		$this->keyManager = $this->createMock(KeyManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->instance = new PassphraseService(
			$this->util,
			$this->crypt,
			$this->session,
			$this->recovery,
			$this->keyManager,
			$this->createMock(LoggerInterface::class),
			$this->userManager,
			$this->userSession,
			$this->eventDispatcher,
		);
	}

	public function testSetProcessingReset(): void {
		$this->instance->setProcessingReset('userId');
		$this->assertEquals(['userId' => true], $this->invokePrivate($this->instance, 'passwordResetUsers'));
	}

	public function testUnsetProcessingReset(): void {
		$this->instance->setProcessingReset('userId');
		$this->assertEquals(['userId' => true], $this->invokePrivate($this->instance, 'passwordResetUsers'));
		$this->instance->setProcessingReset('userId', false);
		$this->assertEquals([], $this->invokePrivate($this->instance, 'passwordResetUsers'));
	}

	/**
	 * Check that the passphrase setting skips if a reset is processed
	 */
	public function testSetPassphraseResetUserMode(): void {
		$this->session->expects(self::never())
			->method('getPrivateKey');
		$this->keyManager->expects(self::never())
			->method('setPrivateKey');

		$this->instance->setProcessingReset('userId');
		$this->assertTrue($this->instance->setPassphraseForUser('userId', 'password'));
	}

	public function testSetPassphrase_currentUser() {
		$instance = $this->getMockBuilder(PassphraseService::class)
			->onlyMethods(['initMountPoints'])
			->setConstructorArgs([
				$this->util,
				$this->crypt,
				$this->session,
				$this->recovery,
				$this->keyManager,
				$this->createMock(LoggerInterface::class),
				$this->userManager,
				$this->userSession,
				$this->eventDispatcher,
			])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testUser');
		$this->userSession->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$this->userManager->expects(self::atLeastOnce())
			->method('get')
			->with('testUser')
			->willReturn($user);
		$this->session->expects(self::any())
			->method('getPrivateKey')
			->willReturn('private-key');
		$this->crypt->expects(self::any())
			->method('encryptPrivateKey')
			->with('private-key')
			->willReturn('encrypted-key');
		$this->crypt->expects(self::any())
			->method('generateHeader')
			->willReturn('crypt-header: ');

		$this->keyManager->expects(self::atLeastOnce())
			->method('setPrivateKey')
			->with('testUser', 'crypt-header: encrypted-key');

		$this->assertTrue($instance->setPassphraseForUser('testUser', 'password'));
	}

	public function testSetPassphrase_currentUserFails() {
		$instance = $this->getMockBuilder(PassphraseService::class)
			->onlyMethods(['initMountPoints'])
			->setConstructorArgs([
				$this->util,
				$this->crypt,
				$this->session,
				$this->recovery,
				$this->keyManager,
				$this->createMock(LoggerInterface::class),
				$this->userManager,
				$this->userSession,
				$this->eventDispatcher,
			])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testUser');
		$this->userManager->expects(self::atLeastOnce())
			->method('get')
			->with('testUser')
			->willReturn($user);
		$this->userSession->expects(self::atLeastOnce())
			->method('getUser')
			->willReturn($user);
		$this->session->expects(self::any())
			->method('getPrivateKey')
			->willReturn('private-key');
		$this->crypt->expects(self::any())
			->method('encryptPrivateKey')
			->with('private-key')
			->willReturn(false);

		$this->keyManager->expects(self::never())
			->method('setPrivateKey');

		$this->assertFalse($instance->setPassphraseForUser('testUser', 'password'));
	}

	public function testSetPassphrase_currentUserNotExists() {
		$instance = $this->getMockBuilder(PassphraseService::class)
			->onlyMethods(['initMountPoints'])
			->setConstructorArgs([
				$this->util,
				$this->crypt,
				$this->session,
				$this->recovery,
				$this->keyManager,
				$this->createMock(LoggerInterface::class),
				$this->userManager,
				$this->userSession,
				$this->eventDispatcher,
			])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testUser');
		$this->userManager->expects(self::atLeastOnce())
			->method('get')
			->with('testUser')
			->willReturn(null);
		$this->userSession->expects(self::never())
			->method('getUser');
		$this->keyManager->expects(self::never())
			->method('setPrivateKey');

		$this->assertFalse($instance->setPassphraseForUser('testUser', 'password'));
	}

	public function testSetPassphrase_otherUserRegeneratesKeys(): void {
		$instance = $this->getMockBuilder(PassphraseService::class)
			->onlyMethods(['initMountPoints'])
			->setConstructorArgs([
				$this->util,
				$this->crypt,
				$this->session,
				$this->recovery,
				$this->keyManager,
				$this->createMock(LoggerInterface::class),
				$this->userManager,
				$this->userSession,
				$this->eventDispatcher,
			])
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testUser');
		$admin = $this->createMock(IUser::class);
		$admin->method('getUID')->willReturn('admin');
		$this->userManager->method('get')
			->with('testUser')
			->willReturn($user);
		$this->userSession->method('getUser')
			->willReturn($admin);
		$this->keyManager->method('getRecoveryKeyId')->willReturn('recovery');
		$this->keyManager->method('getSystemPrivateKey')->willReturn('recovery-key');
		$this->crypt->method('decryptPrivateKey')->willReturn('decrypted-recovery-key');
		$this->recovery->method('isRecoveryEnabledForUser')->with('testUser')->willReturn(true);
		$this->crypt->method('createKeyPair')->willReturn(['publicKey' => 'public', 'privateKey' => 'private']);
		$this->crypt->method('encryptPrivateKey')->willReturn('encrypted-key');
		$this->crypt->method('generateHeader')->willReturn('crypt-header: ');
		$this->keyManager->expects(self::once())
			->method('setPrivateKey')
			->with('testUser', 'crypt-header: encrypted-key');
		$this->recovery->expects(self::once())
			->method('recoverUsersFiles')
			->with('recovery-password', 'testUser');

		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'encryption.user_keys.regenerated'
				&& $event->getParameters() === ['userId' => 'testUser']
				&& $event->getLogMessage() === 'Encryption keys of user "%s" were regenerated'));

		$this->assertTrue($instance->setPassphraseForUser('testUser', 'password', 'recovery-password'));
	}
}
