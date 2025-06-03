<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests;

use OC\Files\FileInfo;
use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\Exceptions\PrivateKeyMissingException;
use OCA\Encryption\Exceptions\PublicKeyMissingException;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\Encryption\Keys\IStorage;
use OCP\Files\Cache\ICache;
use OCP\Files\Storage\IStorage as FilesIStorage;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class KeyManagerTest extends TestCase {

	protected KeyManager $instance;

	protected string $userId;
	protected string $systemKeyId;
	protected IStorage&MockObject $keyStorageMock;
	protected Crypt&MockObject $cryptMock;
	protected IUserSession&MockObject $userMock;
	protected Session&MockObject $sessionMock;
	protected LoggerInterface&MockObject $logMock;
	protected Util&MockObject $utilMock;
	protected IConfig&MockObject $configMock;
	protected ILockingProvider&MockObject $lockingProviderMock;

	protected function setUp(): void {
		parent::setUp();
		$this->userId = 'user1';
		$this->systemKeyId = 'systemKeyId';
		$this->keyStorageMock = $this->createMock(IStorage::class);
		$this->cryptMock = $this->getMockBuilder(Crypt::class)
			->disableOriginalConstructor()
			->getMock();
		$this->configMock = $this->createMock(IConfig::class);
		$this->configMock->expects($this->any())
			->method('getAppValue')
			->willReturn($this->systemKeyId);
		$this->userMock = $this->createMock(IUserSession::class);
		$this->sessionMock = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()
			->getMock();
		$this->logMock = $this->createMock(LoggerInterface::class);
		$this->utilMock = $this->getMockBuilder(Util::class)
			->disableOriginalConstructor()
			->getMock();
		$this->lockingProviderMock = $this->createMock(ILockingProvider::class);

		$this->instance = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock,
			$this->lockingProviderMock
		);
	}

	public function testDeleteShareKey(): void {
		$this->keyStorageMock->expects($this->any())
			->method('deleteFileKey')
			->with($this->equalTo('/path'), $this->equalTo('keyId.shareKey'))
			->willReturn(true);

		$this->assertTrue(
			$this->instance->deleteShareKey('/path', 'keyId')
		);
	}

	public function testGetPrivateKey(): void {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('privateKey'))
			->willReturn('privateKey');


		$this->assertSame('privateKey',
			$this->instance->getPrivateKey($this->userId)
		);
	}

	public function testGetPublicKey(): void {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('publicKey'))
			->willReturn('publicKey');


		$this->assertSame('publicKey',
			$this->instance->getPublicKey($this->userId)
		);
	}

	public function testRecoveryKeyExists(): void {
		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->with($this->equalTo($this->systemKeyId . '.publicKey'))
			->willReturn('recoveryKey');


		$this->assertTrue($this->instance->recoveryKeyExists());
	}

	public function testCheckRecoveryKeyPassword(): void {
		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->with($this->equalTo($this->systemKeyId . '.privateKey'))
			->willReturn('recoveryKey');
		$this->cryptMock->expects($this->any())
			->method('decryptPrivateKey')
			->with($this->equalTo('recoveryKey'), $this->equalTo('pass'))
			->willReturn('decryptedRecoveryKey');

		$this->assertTrue($this->instance->checkRecoveryPassword('pass'));
	}

	public function testSetPublicKey(): void {
		$this->keyStorageMock->expects($this->any())
			->method('setUserKey')
			->with(
				$this->equalTo($this->userId),
				$this->equalTo('publicKey'),
				$this->equalTo('key'))
			->willReturn(true);


		$this->assertTrue(
			$this->instance->setPublicKey($this->userId, 'key')
		);
	}

	public function testSetPrivateKey(): void {
		$this->keyStorageMock->expects($this->any())
			->method('setUserKey')
			->with(
				$this->equalTo($this->userId),
				$this->equalTo('privateKey'),
				$this->equalTo('key'))
			->willReturn(true);


		$this->assertTrue(
			$this->instance->setPrivateKey($this->userId, 'key')
		);
	}

	/**
	 * @dataProvider dataTestUserHasKeys
	 */
	public function testUserHasKeys($key, $expected): void {
		$this->keyStorageMock->expects($this->exactly(2))
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->anything())
			->willReturn($key);


		$this->assertSame($expected,
			$this->instance->userHasKeys($this->userId)
		);
	}

	public static function dataTestUserHasKeys(): array {
		return [
			['key', true],
			['', false]
		];
	}


	public function testUserHasKeysMissingPrivateKey(): void {
		$this->expectException(PrivateKeyMissingException::class);

		$this->keyStorageMock->expects($this->exactly(2))
			->method('getUserKey')
			->willReturnCallback(function ($uid, $keyID, $encryptionModuleId) {
				if ($keyID === 'privateKey') {
					return '';
				}
				return 'key';
			});

		$this->instance->userHasKeys($this->userId);
	}


	public function testUserHasKeysMissingPublicKey(): void {
		$this->expectException(PublicKeyMissingException::class);

		$this->keyStorageMock->expects($this->exactly(2))
			->method('getUserKey')
			->willReturnCallback(function ($uid, $keyID, $encryptionModuleId) {
				if ($keyID === 'publicKey') {
					return '';
				}
				return 'key';
			});

		$this->instance->userHasKeys($this->userId);
	}

	/**
	 * @dataProvider dataTestInit
	 *
	 * @param bool $useMasterKey
	 */
	public function testInit($useMasterKey): void {
		/** @var KeyManager&MockObject $instance */
		$instance = $this->getMockBuilder(KeyManager::class)
			->setConstructorArgs(
				[
					$this->keyStorageMock,
					$this->cryptMock,
					$this->configMock,
					$this->userMock,
					$this->sessionMock,
					$this->logMock,
					$this->utilMock,
					$this->lockingProviderMock
				]
			)->onlyMethods(['getMasterKeyId', 'getMasterKeyPassword', 'getSystemPrivateKey', 'getPrivateKey'])
			->getMock();

		$this->utilMock->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn($useMasterKey);

		$sessionSetStatusCalls = [];
		$this->sessionMock->expects($this->exactly(2))
			->method('setStatus')
			->willReturnCallback(function (string $status) use (&$sessionSetStatusCalls) {
				$sessionSetStatusCalls[] = $status;
			});

		$instance->expects($this->any())->method('getMasterKeyId')->willReturn('masterKeyId');
		$instance->expects($this->any())->method('getMasterKeyPassword')->willReturn('masterKeyPassword');
		$instance->expects($this->any())->method('getSystemPrivateKey')->with('masterKeyId')->willReturn('privateMasterKey');
		$instance->expects($this->any())->method('getPrivateKey')->with($this->userId)->willReturn('privateUserKey');

		if ($useMasterKey) {
			$this->cryptMock->expects($this->once())->method('decryptPrivateKey')
				->with('privateMasterKey', 'masterKeyPassword', 'masterKeyId')
				->willReturn('key');
		} else {
			$this->cryptMock->expects($this->once())->method('decryptPrivateKey')
				->with('privateUserKey', 'pass', $this->userId)
				->willReturn('key');
		}

		$this->sessionMock->expects($this->once())->method('setPrivateKey')
			->with('key');

		$this->assertTrue($instance->init($this->userId, 'pass'));
		self::assertEquals([
			Session::INIT_EXECUTED,
			Session::INIT_SUCCESSFUL,
		], $sessionSetStatusCalls);
	}

	public static function dataTestInit(): array {
		return [
			[true],
			[false]
		];
	}


	public function testSetRecoveryKey(): void {
		$this->keyStorageMock->expects($this->exactly(2))
			->method('setSystemUserKey')
			->willReturn(true);
		$this->cryptMock->expects($this->any())
			->method('encryptPrivateKey')
			->with($this->equalTo('privateKey'), $this->equalTo('pass'))
			->willReturn('decryptedPrivateKey');


		$this->assertTrue(
			$this->instance->setRecoveryKey('pass',
				['publicKey' => 'publicKey', 'privateKey' => 'privateKey'])
		);
	}

	public function testSetSystemPrivateKey(): void {
		$this->keyStorageMock->expects($this->exactly(1))
			->method('setSystemUserKey')
			->with($this->equalTo('keyId.privateKey'), $this->equalTo('key'))
			->willReturn(true);


		$this->assertTrue(
			$this->instance->setSystemPrivateKey('keyId', 'key')
		);
	}

	public function testGetSystemPrivateKey(): void {
		$this->keyStorageMock->expects($this->exactly(1))
			->method('getSystemUserKey')
			->with($this->equalTo('keyId.privateKey'))
			->willReturn('systemPrivateKey');


		$this->assertSame('systemPrivateKey',
			$this->instance->getSystemPrivateKey('keyId')
		);
	}

	public function testGetEncryptedFileKey(): void {
		$this->keyStorageMock->expects($this->once())
			->method('getFileKey')
			->with('/', 'fileKey')
			->willReturn(true);

		$this->assertTrue($this->instance->getEncryptedFileKey('/'));
	}

	public static function dataTestGetFileKey(): array {
		return [
			['user1', false, 'privateKey', 'legacyKey', 'multiKeyDecryptResult'],
			['user1', false, 'privateKey', '', 'multiKeyDecryptResult'],
			['user1', false, false, 'legacyKey', ''],
			['user1', false, false, '', ''],
			['user1', true, 'privateKey', 'legacyKey', 'multiKeyDecryptResult'],
			['user1', true, 'privateKey', '', 'multiKeyDecryptResult'],
			['user1', true, false, 'legacyKey', ''],
			['user1', true, false, '', ''],
			[null, false, 'privateKey', 'legacyKey', 'multiKeyDecryptResult'],
			[null, false, 'privateKey', '', 'multiKeyDecryptResult'],
			[null, false, false, 'legacyKey', ''],
			[null, false, false, '', ''],
			[null, true, 'privateKey', 'legacyKey', 'multiKeyDecryptResult'],
			[null, true, 'privateKey', '', 'multiKeyDecryptResult'],
			[null, true, false, 'legacyKey', ''],
			[null, true, false, '', ''],
		];
	}

	/**
	 * @dataProvider dataTestGetFileKey
	 *
	 * @param $uid
	 * @param $isMasterKeyEnabled
	 * @param $privateKey
	 * @param $expected
	 */
	public function testGetFileKey($uid, $isMasterKeyEnabled, $privateKey, $encryptedFileKey, $expected): void {
		$path = '/foo.txt';

		if ($isMasterKeyEnabled) {
			$expectedUid = 'masterKeyId';
			$this->configMock->expects($this->any())->method('getSystemValue')->with('secret')
				->willReturn('password');
		} elseif (!$uid) {
			$expectedUid = 'systemKeyId';
		} else {
			$expectedUid = $uid;
		}

		$this->invokePrivate($this->instance, 'masterKeyId', ['masterKeyId']);

		$this->keyStorageMock->expects($this->exactly(2))
			->method('getFileKey')
			->willReturnMap([
				[$path, 'fileKey', 'OC_DEFAULT_MODULE', $encryptedFileKey],
				[$path, $expectedUid . '.shareKey', 'OC_DEFAULT_MODULE', 'fileKey'],
			]);

		$this->utilMock->expects($this->any())->method('isMasterKeyEnabled')
			->willReturn($isMasterKeyEnabled);

		if (is_null($uid)) {
			$this->keyStorageMock->expects($this->once())
				->method('getSystemUserKey')
				->willReturn(true);
			$this->cryptMock->expects($this->once())
				->method('decryptPrivateKey')
				->willReturn($privateKey);
		} else {
			$this->keyStorageMock->expects($this->never())
				->method('getSystemUserKey');
			$this->sessionMock->expects($this->once())->method('getPrivateKey')->willReturn($privateKey);
		}

		if (!empty($encryptedFileKey)) {
			$this->cryptMock->expects($this->never())
				->method('multiKeyDecrypt');
			if ($privateKey) {
				$this->cryptMock->expects($this->once())
					->method('multiKeyDecryptLegacy')
					->willReturn('multiKeyDecryptResult');
			} else {
				$this->cryptMock->expects($this->never())
					->method('multiKeyDecryptLegacy');
			}
		} else {
			$this->cryptMock->expects($this->never())
				->method('multiKeyDecryptLegacy');
			if ($privateKey) {
				$this->cryptMock->expects($this->once())
					->method('multiKeyDecrypt')
					->willReturn('multiKeyDecryptResult');
			} else {
				$this->cryptMock->expects($this->never())
					->method('multiKeyDecrypt');
			}
		}

		$this->assertSame($expected,
			$this->instance->getFileKey($path, $uid, null)
		);
	}

	public function testDeletePrivateKey(): void {
		$this->keyStorageMock->expects($this->once())
			->method('deleteUserKey')
			->with('user1', 'privateKey')
			->willReturn(true);

		$this->assertTrue(self::invokePrivate($this->instance,
			'deletePrivateKey',
			[$this->userId]));
	}

	public function testDeleteAllFileKeys(): void {
		$this->keyStorageMock->expects($this->once())
			->method('deleteAllFileKeys')
			->willReturn(true);

		$this->assertTrue($this->instance->deleteAllFileKeys('/'));
	}

	/**
	 * test add public share key and or recovery key to the list of public keys
	 *
	 * @dataProvider dataTestAddSystemKeys
	 *
	 * @param array $accessList
	 * @param array $publicKeys
	 * @param string $uid
	 * @param array $expectedKeys
	 */
	public function testAddSystemKeys($accessList, $publicKeys, $uid, $expectedKeys): void {
		$publicShareKeyId = 'publicShareKey';
		$recoveryKeyId = 'recoveryKey';

		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->willReturnCallback(function ($keyId, $encryptionModuleId) {
				return $keyId;
			});

		$this->utilMock->expects($this->any())
			->method('isRecoveryEnabledForUser')
			->willReturnCallback(function ($uid) {
				if ($uid === 'user1') {
					return true;
				}
				return false;
			});

		// set key IDs
		self::invokePrivate($this->instance, 'publicShareKeyId', [$publicShareKeyId]);
		self::invokePrivate($this->instance, 'recoveryKeyId', [$recoveryKeyId]);

		$result = $this->instance->addSystemKeys($accessList, $publicKeys, $uid);

		foreach ($expectedKeys as $expected) {
			$this->assertArrayHasKey($expected, $result);
		}

		$this->assertSameSize($expectedKeys, $result);
	}

	/**
	 * data provider for testAddSystemKeys()
	 *
	 * @return array
	 */
	public static function dataTestAddSystemKeys(): array {
		return [
			[['public' => true],[], 'user1', ['publicShareKey', 'recoveryKey']],
			[['public' => false], [], 'user1', ['recoveryKey']],
			[['public' => true],[], 'user2', ['publicShareKey']],
			[['public' => false], [], 'user2', []],
		];
	}

	public function testGetMasterKeyId(): void {
		$this->assertSame('systemKeyId', $this->instance->getMasterKeyId());
	}

	public function testGetPublicMasterKey(): void {
		$this->keyStorageMock->expects($this->once())->method('getSystemUserKey')
			->with('systemKeyId.publicKey', Encryption::ID)
			->willReturn(true);

		$this->assertTrue(
			$this->instance->getPublicMasterKey()
		);
	}

	public function testGetMasterKeyPassword(): void {
		$this->configMock->expects($this->once())->method('getSystemValue')->with('secret')
			->willReturn('password');

		$this->assertSame('password',
			$this->invokePrivate($this->instance, 'getMasterKeyPassword', [])
		);
	}


	public function testGetMasterKeyPasswordException(): void {
		$this->expectException(\Exception::class);

		$this->configMock->expects($this->once())->method('getSystemValue')->with('secret')
			->willReturn('');

		$this->invokePrivate($this->instance, 'getMasterKeyPassword', []);
	}

	/**
	 * @dataProvider dataTestValidateMasterKey
	 *
	 * @param $masterKey
	 */
	public function testValidateMasterKey($masterKey): void {
		/** @var KeyManager&MockObject $instance */
		$instance = $this->getMockBuilder(KeyManager::class)
			->setConstructorArgs(
				[
					$this->keyStorageMock,
					$this->cryptMock,
					$this->configMock,
					$this->userMock,
					$this->sessionMock,
					$this->logMock,
					$this->utilMock,
					$this->lockingProviderMock
				]
			)->onlyMethods(['getPublicMasterKey', 'setSystemPrivateKey', 'getMasterKeyPassword'])
			->getMock();

		$this->utilMock->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$instance->expects($this->once())->method('getPublicMasterKey')
			->willReturn($masterKey);

		$instance->expects($this->any())->method('getMasterKeyPassword')->willReturn('masterKeyPassword');
		$this->cryptMock->expects($this->any())->method('generateHeader')->willReturn('header');

		if (empty($masterKey)) {
			$this->cryptMock->expects($this->once())->method('createKeyPair')
				->willReturn(['publicKey' => 'public', 'privateKey' => 'private']);
			$this->keyStorageMock->expects($this->once())->method('setSystemUserKey')
				->with('systemKeyId.publicKey', 'public', Encryption::ID);
			$this->cryptMock->expects($this->once())->method('encryptPrivateKey')
				->with('private', 'masterKeyPassword', 'systemKeyId')
				->willReturn('EncryptedKey');
			$this->lockingProviderMock->expects($this->once())
				->method('acquireLock');
			$instance->expects($this->once())->method('setSystemPrivateKey')
				->with('systemKeyId', 'headerEncryptedKey');
		} else {
			$this->cryptMock->expects($this->never())->method('createKeyPair');
			$this->keyStorageMock->expects($this->never())->method('setSystemUserKey');
			$this->cryptMock->expects($this->never())->method('encryptPrivateKey');
			$instance->expects($this->never())->method('setSystemPrivateKey');
		}

		$instance->validateMasterKey();
	}

	public function testValidateMasterKeyLocked(): void {
		/** @var KeyManager&MockObject $instance */
		$instance = $this->getMockBuilder(KeyManager::class)
			->setConstructorArgs([
				$this->keyStorageMock,
				$this->cryptMock,
				$this->configMock,
				$this->userMock,
				$this->sessionMock,
				$this->logMock,
				$this->utilMock,
				$this->lockingProviderMock
			])
			->onlyMethods(['getPublicMasterKey', 'getPrivateMasterKey', 'setSystemPrivateKey', 'getMasterKeyPassword'])
			->getMock();

		$this->utilMock->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$instance->expects($this->once())->method('getPublicMasterKey')
			->willReturn('');
		$instance->expects($this->once())->method('getPrivateMasterKey')
			->willReturn('');

		$instance->expects($this->any())->method('getMasterKeyPassword')->willReturn('masterKeyPassword');
		$this->cryptMock->expects($this->any())->method('generateHeader')->willReturn('header');

		$this->lockingProviderMock->expects($this->once())
			->method('acquireLock')
			->willThrowException(new LockedException('encryption-generateMasterKey'));

		$this->expectException(LockedException::class);
		$instance->validateMasterKey();
	}

	public static function dataTestValidateMasterKey(): array {
		return [
			['masterKey'],
			['']
		];
	}

	public function testGetVersionWithoutFileInfo(): void {
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$view->expects($this->once())
			->method('getFileInfo')
			->with('/admin/files/myfile.txt')
			->willReturn(false);

		/** @var View $view */
		$this->assertSame(0, $this->instance->getVersion('/admin/files/myfile.txt', $view));
	}

	public function testGetVersionWithFileInfo(): void {
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$fileInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();
		$fileInfo->expects($this->once())
			->method('getEncryptedVersion')
			->willReturn(1337);
		$view->expects($this->once())
			->method('getFileInfo')
			->with('/admin/files/myfile.txt')
			->willReturn($fileInfo);

		/** @var View $view */
		$this->assertSame(1337, $this->instance->getVersion('/admin/files/myfile.txt', $view));
	}

	public function testSetVersionWithFileInfo(): void {
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$cache = $this->getMockBuilder(ICache::class)
			->disableOriginalConstructor()->getMock();
		$cache->expects($this->once())
			->method('update')
			->with(123, ['encrypted' => 5, 'encryptedVersion' => 5]);
		$storage = $this->getMockBuilder(FilesIStorage::class)
			->disableOriginalConstructor()->getMock();
		$storage->expects($this->once())
			->method('getCache')
			->willReturn($cache);
		$fileInfo = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()->getMock();
		$fileInfo->expects($this->once())
			->method('getStorage')
			->willReturn($storage);
		$fileInfo->expects($this->once())
			->method('getId')
			->willReturn(123);
		$view->expects($this->once())
			->method('getFileInfo')
			->with('/admin/files/myfile.txt')
			->willReturn($fileInfo);

		/** @var View $view */
		$this->instance->setVersion('/admin/files/myfile.txt', 5, $view);
	}

	public function testSetVersionWithoutFileInfo(): void {
		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$view->expects($this->once())
			->method('getFileInfo')
			->with('/admin/files/myfile.txt')
			->willReturn(false);

		/** @var View $view */
		$this->instance->setVersion('/admin/files/myfile.txt', 5, $view);
	}

	public function testBackupUserKeys(): void {
		$this->keyStorageMock->expects($this->once())->method('backupUserKeys')
			->with('OC_DEFAULT_MODULE', 'test', 'user1');
		$this->instance->backupUserKeys('test', 'user1');
	}
}
