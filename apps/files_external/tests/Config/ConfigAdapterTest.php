<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Config;

use OC\Files\Storage\StorageFactory;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Backend\SMB;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\ICachedMountFileInfo;
use OCP\Files\Config\MountProviderArgs;
use OCP\IUser;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Test\TestCase;

#[Group(name: 'DB')]
class ConfigAdapterTest extends TestCase {
	private ConfigAdapter $adapter;
	private BackendService&MockObject $backendService;
	private IUser&MockObject $user;
	private UserStoragesService $userStoragesService;
	private UserGlobalStoragesService $userGlobalStoragesService;
	private array $storageIds = [];

	protected function makeStorageConfig(array $data): StorageConfig {
		$storage = new StorageConfig();
		if (isset($data['id'])) {
			$storage->setId($data['id']);
		}
		$storage->setMountPoint($data['mountPoint']);
		$data['backend'] = $this->backendService->getBackend($data['backendIdentifier']);
		if (!isset($data['backend'])) {
			throw new \Exception('oops, no backend');
		}
		$data['authMechanism'] = $this->backendService->getAuthMechanism($data['authMechanismIdentifier']);
		if (!isset($data['authMechanism'])) {
			throw new \Exception('oops, no auth mechanism');
		}
		$storage->setId(StorageConfig::MOUNT_TYPE_PERSONAL);
		$storage->setApplicableUsers([$this->user->getUID()]);
		$storage->setBackend($data['backend']);
		$storage->setAuthMechanism($data['authMechanism']);
		$storage->setBackendOptions($data['backendOptions']);
		$storage->setPriority($data['priority']);
		if (isset($data['mountOptions'])) {
			$storage->setMountOptions($data['mountOptions']);
		}
		return $storage;
	}

	protected function getBackendMock($class = SMB::class, $storageClass = \OCA\Files_External\Lib\Storage\SMB::class) {
		$backend = $this->createMock(Backend::class);
		$backend->method('getStorageClass')
			->willReturn($storageClass);
		$backend->method('getIdentifier')
			->willReturn('identifier:' . $class);
		return $backend;
	}

	protected function getAuthMechMock($scheme = 'null', $class = NullMechanism::class) {
		$authMech = $this->createMock(AuthMechanism::class);
		$authMech->method('getScheme')
			->willReturn($scheme);
		$authMech->method('getIdentifier')
			->willReturn('identifier:' . $class);

		return $authMech;
	}

	public function setUp(): void {
		// prepare BackendService mock
		$this->backendService = $this->createMock(BackendService::class);

		$authMechanisms = [
			'identifier:\Auth\Mechanism' => $this->getAuthMechMock('null', '\Auth\Mechanism'),
			'identifier:\Other\Auth\Mechanism' => $this->getAuthMechMock('null', '\Other\Auth\Mechanism'),
			'identifier:\OCA\Files_External\Lib\Auth\NullMechanism' => $this->getAuthMechMock(),
		];
		$this->backendService->method('getAuthMechanism')
			->willReturnCallback(function ($class) use ($authMechanisms) {
				if (isset($authMechanisms[$class])) {
					return $authMechanisms[$class];
				}
				return null;
			});
		$this->backendService->method('getAuthMechanismsByScheme')
			->willReturnCallback(function ($schemes) use ($authMechanisms) {
				return array_filter($authMechanisms, function ($authMech) use ($schemes) {
					return in_array($authMech->getScheme(), $schemes, true);
				});
			});

		$backends = [
			'identifier:\OCA\Files_External\Lib\Backend\DAV' => $this->getBackendMock('\OCA\Files_External\Lib\Backend\DAV', '\OC\Files\Storage\DAV'),
			'identifier:\OCA\Files_External\Lib\Backend\SMB' => $this->getBackendMock('\OCA\Files_External\Lib\Backend\SMB', '\OCA\Files_External\Lib\Storage\SMB'),
		];
		$this->backendService->method('getBackend')
			->willReturnCallback(function ($backendClass) use ($backends) {
				if (isset($backends[$backendClass])) {
					return $backends[$backendClass];
				}
				return null;
			});
		$this->backendService->method('getAuthMechanisms')
			->willReturn($authMechanisms);
		$this->backendService->method('getBackends')
			->willReturn($backends);

		$this->userStoragesService = Server::get(UserStoragesService::class);
		$this->userGlobalStoragesService = Server::get(UserGlobalStoragesService::class);
		$this->adapter = new ConfigAdapter($this->userStoragesService, $this->userGlobalStoragesService, $this->createMock(ClockInterface::class));

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('user1');

		$this->userStoragesService->setUser($this->user);

		$storageConfig = $this->makeStorageConfig([
			'mountPoint' => '/mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'priority' => 15,
			'mountOptions' => [
				'preview' => false,
			]
		]);
		$this->storageIds[] = $this->userStoragesService->addStorage($storageConfig)->getId();

		$storageConfig = $this->makeStorageConfig([
			'mountPoint' => '/subfolder/mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'priority' => 15,
			'mountOptions' => [
				'preview' => false,
			]
		]);
		$this->storageIds[] = $this->userStoragesService->addStorage($storageConfig)->getId();

		$storageConfig = $this->makeStorageConfig([
			'mountPoint' => '/subfolder/subfolder/mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'priority' => 15,
			'mountOptions' => [
				'preview' => false,
			]
		]);
		$this->storageIds[] = $this->userStoragesService->addStorage($storageConfig)->getId();
	}

	public function tearDown(): void {
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('user1');
		$this->userStoragesService->setUser($this->user);
		foreach ($this->storageIds as $storageId) {
			$this->userStoragesService->removeStorage($storageId);
		}
	}

	public static function pathsProvider(): \Generator {
		yield ['/user1/files/subfolder', 2];
		yield ['/user1/files/subfolder/subfolder', 1];
		yield ['/user1/files/nothing', 0];
		yield ['/user1/files/mountpoint', 0]; // we only want the children
	}

	#[DataProvider(methodName: 'pathsProvider')]
	public function testPartialMountpointWithChildren(string $path, int $count): void {
		$mountFileInfo = $this->createMock(ICachedMountFileInfo::class);
		$mountFileInfo->method('getUser')->willReturn($this->user);
		$cacheEntry = $this->createMock(ICacheEntry::class);

		$result = $this->adapter->getMountsForPath($path, true, [
			new MountProviderArgs($mountFileInfo, $cacheEntry),
		], $this->createMock(StorageFactory::class));

		$this->assertCount($count, $result);
	}

	public function testPartialMountpointExact(): void {
		$mountFileInfo = $this->createMock(ICachedMountFileInfo::class);
		$mountFileInfo->method('getUser')->willReturn($this->user);
		$mountFileInfo->method('getMountPoint')->willReturn('/user1/files/subfolder/subfolder/');
		$cacheEntry = $this->createMock(ICacheEntry::class);

		$result = $this->adapter->getMountsForPath('/user1/files/subfolder/subfolder', true, [
			new MountProviderArgs($mountFileInfo, $cacheEntry),
		], $this->createMock(StorageFactory::class));

		$this->assertCount(1, $result);
	}
}
