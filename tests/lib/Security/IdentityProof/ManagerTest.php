<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\IdentityProof;

use OC\Files\AppData\AppData;
use OC\Files\AppData\Factory;
use OC\Security\IdentityProof\Key;
use OC\Security\IdentityProof\KeyType;
use OC\Security\IdentityProof\Manager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Security\IdentityProof\Algorithm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	private Factory&MockObject $factory;
	private IAppData&MockObject $appData;
	private ICrypto&MockObject $crypto;
	private Manager&MockObject $manager;
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private ICacheFactory&MockObject $cacheFactory;
	private ICache&MockObject $cache;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		/** @var Factory|\PHPUnit\Framework\MockObject\MockObject $factory */
		$this->factory = $this->createMock(Factory::class);
		$this->appData = $this->createMock(AppData::class);
		$this->config = $this->createMock(IConfig::class);
		$this->factory->expects($this->any())
			->method('get')
			->with('identityproof')
			->willReturn($this->appData);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);

		$this->cacheFactory->expects($this->any())
			->method('createDistributed')
			->willReturn($this->cache);

		$this->crypto = $this->createMock(ICrypto::class);
		$this->manager = $this->getManager(['generateKeyPair']);
	}

	/**
	 * create manager object
	 *
	 * @param array $setMethods
	 * @return Manager|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getManager($setMethods = []) {
		if (empty($setMethods)) {
			return new Manager(
				$this->factory,
				$this->crypto,
				$this->config,
				$this->logger,
				$this->cacheFactory,
			);
		} else {
			return $this->getMockBuilder(Manager::class)
				->setConstructorArgs([
					$this->factory,
					$this->crypto,
					$this->config,
					$this->logger,
					$this->cacheFactory,
				])
				->onlyMethods($setMethods)
				->getMock();
		}
	}

	public function testGetKeyWithExistingKey(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$folder = $this->createMock(ISimpleFolder::class);
		$privateFile = $this->createMock(ISimpleFile::class);
		$privateFile
			->expects($this->once())
			->method('getContent')
			->willReturn('EncryptedPrivateKey');
		$publicFile = $this->createMock(ISimpleFile::class);
		$publicFile
			->expects($this->once())
			->method('getContent')
			->willReturn('MyPublicKey');
		$this->crypto
			->expects($this->once())
			->method('decrypt')
			->with('EncryptedPrivateKey')
			->willReturn('MyPrivateKey');
		$folder
			->expects($this->exactly(2))
			->method('getFile')
			->willReturnMap([
				['private', $privateFile],
				['public', $publicFile],
			]);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('user-MyUid')
			->willReturn($folder);
		$this->cache
			->expects($this->exactly(2))
			->method('get')
			->willReturn(null);

		$expected = new Key('MyPublicKey', 'MyPrivateKey', Algorithm::Rsa2048);
		$this->assertEquals($expected, $this->manager->getKey($user));
	}

	public function testGetKeyWithExistingKeyCached(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$this->crypto
			->expects($this->once())
			->method('decrypt')
			->with('EncryptedPrivateKey')
			->willReturn('MyPrivateKey');
		$this->cache
			->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				['user-MyUid-public', 'MyPublicKey'],
				['user-MyUid-private', 'EncryptedPrivateKey'],
			]);

		$expected = new Key('MyPublicKey', 'MyPrivateKey', Algorithm::Rsa2048);
		$this->assertEquals($expected, $this->manager->getKey($user));
	}

	public function testSetPublicKey(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->exactly(1))
			->method('getUID')
			->willReturn('MyUid');
		$publicFile = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$folder
			->expects($this->once())
			->method('newFile')
			->willReturnMap([
				['public', 'MyNewPublicKey', $publicFile],
			]);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('user-MyUid')
			->willReturn($folder);
		$this->cache
			->expects($this->once())
			->method('set')
			->with('user-MyUid-public', 'MyNewPublicKey');

		$this->manager->setPublicKey($user, 'MyNewPublicKey');
	}

	public function testGetKeyWithNotExistingKey(): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$this->manager
			->expects($this->once())
			->method('generateKeyPair')
			->with(Algorithm::Rsa2048)
			->willReturn(new Key('MyNewPublicKey', 'MyNewPrivateKey', Algorithm::Rsa2048));
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('newFolder')
			->with('user-MyUid')
			->willReturn($folder);
		$this->crypto
			->expects($this->once())
			->method('encrypt')
			->with('MyNewPrivateKey')
			->willReturn('MyNewEncryptedPrivateKey');
		$privateFile = $this->createMock(ISimpleFile::class);
		$privateFile
			->expects($this->once())
			->method('putContent')
			->with('MyNewEncryptedPrivateKey');
		$publicFile = $this->createMock(ISimpleFile::class);
		$publicFile
			->expects($this->once())
			->method('putContent')
			->with('MyNewPublicKey');
		$folder
			->expects($this->exactly(2))
			->method('newFile')
			->willReturnMap([
				['private', null, $privateFile],
				['public', null, $publicFile],
			]);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('user-MyUid')
			->willThrowException(new NotFoundException());

		$expected = new Key('MyNewPublicKey', 'MyNewPrivateKey', Algorithm::Rsa2048);
		$this->assertEquals($expected, $this->manager->getKey($user));
	}

	public function testGetUserKeyCreatesFolderWhenNewFolderFails(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('MyUid');
		$key = new Key('MyNewPublicKey', 'MyNewPrivateKey', Algorithm::Es384);
		$this->manager
			->expects($this->once())
			->method('generateKeyPair')
			->with(Algorithm::Es384)
			->willReturn($key);
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->method('newFile')->willReturn($this->createMock(ISimpleFile::class));
		$this->appData
			->expects($this->once())
			->method('newFolder')
			->with('secp384r1-user-MyUid')
			->willThrowException(new \Exception());
		$this->appData
			->expects($this->exactly(2))
			->method('getFolder')
			->with('secp384r1-user-MyUid')
			->willReturnOnConsecutiveCalls(
				$this->throwException(new NotFoundException()),
				$folder,
			);

		$this->assertSame($key, $this->manager->getUserKey($user, Algorithm::Es384));
	}

	public static function dataGenerateKeyId(): array {
		return [
			'legacy rsa user' => [KeyType::User, Algorithm::Rsa2048, 'uid', '', 'user-uid'],
			'legacy ec system' => [KeyType::System, Algorithm::Es256, 'instance', '', 'system-instance'],
			'legacy rsa app' => [KeyType::App, Algorithm::Rsa2048, 'core', 'name', 'app-core-name'],
			'legacy ec app' => [KeyType::App, Algorithm::Es256, 'core', 'name', 'app-core-name'],
			'es384 user' => [KeyType::User, Algorithm::Es384, 'uid', '', 'secp384r1-user-uid'],
			'es384 app' => [KeyType::App, Algorithm::Es384, 'core', 'name', 'secp384r1-app-core-name'],
			'es384 app without name' => [KeyType::App, Algorithm::Es384, 'core', '', 'secp384r1-app-core'],
		];
	}

	#[DataProvider('dataGenerateKeyId')]
	public function testGenerateKeyId(KeyType $type, Algorithm $algorithm, string $id, string $name, string $expected): void {
		$this->assertSame($expected, self::invokePrivate($this->getManager(), 'generateKeyId', [$type, $algorithm, $id, $name]));
	}

	public static function dataGenerateKeyPair(): array {
		return [
			[Algorithm::Rsa2048, OPENSSL_KEYTYPE_RSA, 2048, null],
			[Algorithm::Es256, OPENSSL_KEYTYPE_EC, 256, 'prime256v1'],
			[Algorithm::Es384, OPENSSL_KEYTYPE_EC, 384, 'secp384r1'],
		];
	}

	#[DataProvider('dataGenerateKeyPair')]
	public function testGenerateKeyPair(Algorithm $algorithm, int $type, int $bits, ?string $curve): void {
		$manager = $this->getManager();
		$data = 'MyTestData';

		/** @var Key $key */
		$key = self::invokePrivate($manager, 'generateKeyPair', [$algorithm]);
		$this->assertSame($algorithm, $key->getAlgorithm());

		openssl_sign($data, $signature, $key->getPrivate(), OPENSSL_ALGO_SHA256);
		$this->assertSame(1, openssl_verify($data, $signature, $key->getPublic(), OPENSSL_ALGO_SHA256));

		$details = openssl_pkey_get_details(openssl_pkey_get_public($key->getPublic()));
		$this->assertSame($type, $details['type']);
		$this->assertSame($bits, $details['bits']);
		$this->assertSame($curve, $details['ec']['curve_name'] ?? null);
	}

	public function testGetSystemKey(): void {
		$manager = $this->getManager(['retrieveKey']);

		$key = $this->createMock(Key::class);

		$this->config->expects($this->once())->method('getSystemValue')
			->with('instanceid', null)->willReturn('instanceId');

		$manager->expects($this->once())->method('retrieveKey')
			->with(KeyType::System, Algorithm::Rsa2048, 'instanceId')
			->willReturn($key);

		$this->assertSame($key, $manager->getSystemKey());
	}

	public function testGetServerKey(): void {
		$manager = $this->getManager(['retrieveKey']);

		$key = $this->createMock(Key::class);

		$this->config->expects($this->once())->method('getSystemValue')
			->with('instanceid', null)->willReturn('instanceId');

		$manager->expects($this->once())->method('retrieveKey')
			->with(KeyType::System, Algorithm::Es384, 'instanceId')
			->willReturn($key);

		$this->assertSame($key, $manager->getServerKey(Algorithm::Es384));
	}

	public function testGetSystemKeyFailure(): void {
		$this->expectException(\RuntimeException::class);

		$manager = $this->getManager(['retrieveKey']);

		$this->config->expects($this->once())->method('getSystemValue')
			->with('instanceid', null)->willReturn(null);
		$manager->expects($this->never())->method('retrieveKey');

		$manager->getSystemKey();
	}

	public function testGetAppKey(): void {
		$manager = $this->getManager(['retrieveKey']);

		$key = $this->createMock(Key::class);

		$manager->expects($this->once())->method('retrieveKey')
			->with(KeyType::App, Algorithm::Es384, 'core', 'name')
			->willReturn($key);

		$this->assertSame($key, $manager->getAppKey('core', Algorithm::Es384, 'name'));
	}

	public function testGetAppKeyCached(): void {
		$this->crypto->method('decrypt')->with('EncryptedPrivateKey')->willReturn('MyPrivateKey');
		$this->cache
			->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				['secp384r1-app-core-name-public', 'MyPublicKey'],
				['secp384r1-app-core-name-private', 'EncryptedPrivateKey'],
			]);

		$expected = new Key('MyPublicKey', 'MyPrivateKey', Algorithm::Es384);
		$this->assertEquals($expected, $this->manager->getAppKey('core', Algorithm::Es384, 'name'));
	}

	public static function dataHasAppKey(): array {
		return [
			[true, true, true],
			[true, false, false],
			[false, true, false],
		];
	}

	#[DataProvider('dataHasAppKey')]
	public function testHasAppKey(bool $public, bool $private, bool $expected): void {
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->method('fileExists')->willReturnMap([
			['public', $public],
			['private', $private],
		]);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('app-core-name')
			->willReturn($folder);

		$this->assertSame($expected, $this->manager->hasAppKey('core', 'name'));
	}

	public function testHasAppKeyMissingFolder(): void {
		$this->appData->method('getFolder')->willThrowException(new NotFoundException());
		$this->assertFalse($this->manager->hasAppKey('core', 'name'));
	}

	public function testDeleteAppKey(): void {
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->expects($this->once())->method('delete');
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('app-core-name')
			->willReturn($folder);

		$this->assertTrue($this->manager->deleteAppKey('core', 'name'));
	}

	public function testDeleteAppKeyMissingFolder(): void {
		$this->appData->method('getFolder')->willThrowException(new NotFoundException());
		$this->assertFalse($this->manager->deleteAppKey('core', 'name'));
	}
}
