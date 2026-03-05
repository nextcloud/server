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
use OC\Security\IdentityProof\Manager;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ICrypto;
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

		$expected = new Key('MyPublicKey', 'MyPrivateKey');
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

		$expected = new Key('MyPublicKey', 'MyPrivateKey');
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
			->willReturn(['MyNewPublicKey', 'MyNewPrivateKey']);
		$this->appData
			->expects($this->once())
			->method('newFolder')
			->with('user-MyUid');
		$folder = $this->createMock(ISimpleFolder::class);
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
			->expects($this->exactly(2))
			->method('getFolder')
			->with('user-MyUid')
			->willReturnOnConsecutiveCalls(
				$this->throwException(new \Exception()),
				$folder
			);


		$expected = new Key('MyNewPublicKey', 'MyNewPrivateKey');
		$this->assertEquals($expected, $this->manager->getKey($user));
	}

	public function testGenerateKeyPair(): void {
		$manager = $this->getManager();
		$data = 'MyTestData';

		[$resultPublicKey, $resultPrivateKey] = self::invokePrivate($manager, 'generateKeyPair');
		openssl_sign($data, $signature, $resultPrivateKey);
		$details = openssl_pkey_get_details(openssl_pkey_get_public($resultPublicKey));

		$this->assertSame(1, openssl_verify($data, $signature, $resultPublicKey));
		$this->assertSame(2048, $details['bits']);
	}

	public function testGetSystemKey(): void {
		$manager = $this->getManager(['retrieveKey']);

		/** @var Key|\PHPUnit\Framework\MockObject\MockObject $key */
		$key = $this->createMock(Key::class);

		$this->config->expects($this->once())->method('getSystemValue')
			->with('instanceid', null)->willReturn('instanceId');

		$manager->expects($this->once())->method('retrieveKey')->with('system-instanceId')
			->willReturn($key);

		$this->assertSame($key, $manager->getSystemKey());
	}



	public function testGetSystemKeyFailure(): void {
		$this->expectException(\RuntimeException::class);

		$manager = $this->getManager(['retrieveKey']);

		/** @var Key|\PHPUnit\Framework\MockObject\MockObject $key */
		$key = $this->createMock(Key::class);

		$this->config->expects($this->once())->method('getSystemValue')
			->with('instanceid', null)->willReturn(null);

		$manager->getSystemKey();
	}
}
