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
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
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

		$this->instance = new PassphraseService(
			$this->util,
			$this->crypt,
			$this->session,
			$this->recovery,
			$this->keyManager,
			$this->createMock(LoggerInterface::class),
			$this->userManager,
			$this->userSession,
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
		$instance = $this->instance;

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
			->with('testUser', 'crypt-header: encrypted-key')
			->willReturn(true);

		$this->assertTrue($instance->setPassphraseForUser('testUser', 'password'));
	}

	public function testSetPassphrase_currentUserFails() {
		$instance = $this->instance;

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
		$instance = $this->instance;

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

	private function createOtherUserService(bool $hasKeys, bool $hasFiles, bool $recoveryEnabled): PassphraseService {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')->with('testUser')->willReturn($user);
		$this->userSession->method('getUser')->willReturn(null);
		$this->keyManager->method('userHasKeys')->with('testUser')->willReturn($hasKeys);
		$this->util->method('userHasFiles')->with('testUser')->willReturn($hasFiles);
		$this->recovery->method('isRecoveryEnabledForUser')
			->with('testUser')->willReturn($recoveryEnabled);

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
			])
			->getMock();
		$instance->expects(self::once())->method('initMountPoints')->with($user);
		return $instance;
	}

	public function testSetPassphraseForUserReturnsFalseWhenCurrentUserPrivateKeyWriteFails(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testUser');
		$this->userManager->method('get')->willReturn($user);
		$this->userSession->method('getUser')->willReturn($user);
		$this->session->method('getPrivateKey')->willReturn('private-key');
		$this->crypt->method('encryptPrivateKey')->willReturn('encrypted-key');
		$this->crypt->method('generateHeader')->willReturn('header');
		$this->keyManager->expects(self::once())->method('setPrivateKey')
			->with('testUser', 'headerencrypted-key')->willReturn(false);

		$this->assertFalse($this->instance->setPassphraseForUser('testUser', 'new-password'));
	}

	public function testSetPassphraseForUserSkipsRecoveryKeyDecryptionWhenRotationIsNotNeeded(): void {
		$instance = $this->otherUserService(true, true, false);
		$this->keyManager->expects(self::never())->method('getSystemPrivateKey');
		$this->crypt->expects(self::never())->method('decryptPrivateKey');
		$this->crypt->expects(self::never())->method('createKeyPair');

		$this->assertFalse($instance->setPassphraseForUser('testUser', 'new-password', 'irrelevant-password'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'recoveryDecryptionFailures')]
	public function testSetPassphraseForUserRejectsFailedRecoveryDecryptionBeforeWritingKeys(bool $throws): void {
		$instance = $this->otherUserService(true, true, true);
		$this->keyManager->method('getRecoveryKeyId')->willReturn('recovery');
		$this->keyManager->method('getSystemPrivateKey')->willReturn('encrypted-recovery-key');
		if ($throws) {
			$this->crypt->method('decryptPrivateKey')
				->willThrowException(new \RuntimeException('decryption failed'));
		} else {
			$this->crypt->method('decryptPrivateKey')->willReturn(false);
		}
		$this->crypt->expects(self::never())->method('createKeyPair');
		$this->keyManager->expects(self::never())->method('setPublicKey');
		$this->keyManager->expects(self::never())->method('setPrivateKey');
		$this->recovery->expects(self::never())->method('recoverUsersFiles');

		$this->expectException(GenericEncryptionException::class);
		$instance->setPassphraseForUser('testUser', 'new-password', 'bad-recovery-password');
	}

	public static function recoveryDecryptionFailures(): array {
		return ['returns false' => [false], 'throws' => [true]];
	}

	public function testSetPassphraseForUserRecoversFilesWhenKeyWritesSucceed(): void {
		$instance = $this->otherUserService(true, true, true);
		$this->keyManager->method('getRecoveryKeyId')->willReturn('recovery');
		$this->keyManager->method('getSystemPrivateKey')->willReturn('encrypted-recovery-key');
		$this->crypt->expects(self::once())->method('decryptPrivateKey')
			->with('encrypted-recovery-key', 'recovery-password')
			->willReturn('recovery-private-key');
		$this->crypt->method('createKeyPair')->willReturn([
			'publicKey' => 'public-key',
			'privateKey' => 'private-key',
		]);
		$this->crypt->method('encryptPrivateKey')->willReturn('encrypted-private-key');
		$this->crypt->method('generateHeader')->willReturn('header');
		$this->keyManager->expects(self::once())->method('setPublicKey')->willReturn(true);
		$this->keyManager->expects(self::once())->method('setPrivateKey')->willReturn(true);
		$this->recovery->expects(self::once())->method('recoverUsersFiles')
			->with('recovery-password', 'testUser');

		$this->assertTrue($instance->setPassphraseForUser(
			'testUser', 'new-password', 'recovery-password'
		));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'failedKeyWrites')]
	public function testSetPassphraseForUserReturnsFalseWhenKeyWriteFails(bool $publicWriteSucceeds): void {
		$instance = $this->otherUserService(false, false, false);
		$this->crypt->expects(self::never())->method('decryptPrivateKey');
		$this->crypt->method('createKeyPair')->willReturn([
			'publicKey' => 'public-key',
			'privateKey' => 'private-key',
		]);
		$this->crypt->method('encryptPrivateKey')->willReturn('encrypted-private-key');
		$this->crypt->method('generateHeader')->willReturn('header');
		$this->keyManager->expects(self::once())->method('setPublicKey')
			->willReturn($publicWriteSucceeds);
		$this->keyManager->expects($publicWriteSucceeds ? self::once() : self::never())
			->method('setPrivateKey')
			->willReturn(false);
		$this->recovery->expects(self::never())->method('recoverUsersFiles');

		$this->assertFalse($instance->setPassphraseForUser('testUser', 'new-password'));
	}

	public static function failedKeyWrites(): array {
		return [
			'public write fails' => [false],
			'private write fails after public write' => [true],
		];
	}
}
