<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Files\Mount;

use OC\Files\Mount\ObjectHomeMountProvider;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OCP\App\IAppManager;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;

class ObjectHomeMountProviderTest extends \Test\TestCase {
	/** @var ObjectHomeMountProvider */
	protected $provider;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;

	/** @var IStorageFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $loader;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->user = $this->createMock(IUser::class);
		$this->loader = $this->createMock(IStorageFactory::class);

		$objectStoreConfig = new PrimaryObjectStoreConfig($this->config, $this->createMock(IAppManager::class));
		$this->provider = new ObjectHomeMountProvider($objectStoreConfig);
	}

	public function testSingleBucket(): void {
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'objectstore') {
					return [
						'class' => 'Test\Files\Mount\FakeObjectStore',
						'arguments' => [
							'foo' => 'bar'
						],
					];
				} else {
					return $default;
				}
			});

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$arguments = $this->invokePrivate($mount, 'arguments');

		$objectStore = $arguments['objectstore'];
		$this->assertInstanceOf(FakeObjectStore::class, $objectStore);
		$this->assertEquals(['foo' => 'bar', 'multibucket' => false], $objectStore->getArguments());
	}

	public function testMultiBucket(): void {
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'objectstore_multibucket') {
					return [
						'class' => 'Test\Files\Mount\FakeObjectStore',
						'arguments' => [
							'foo' => 'bar'
						],
					];
				} else {
					return $default;
				}
			});

		$this->user->method('getUID')
			->willReturn('uid');
		$this->loader->expects($this->never())->method($this->anything());

		$this->config->method('getUserValue')
			->willReturn(null);

		$this->config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->equalTo('uid'),
				$this->equalTo('homeobjectstore'),
				$this->equalTo('bucket'),
				$this->equalTo('49'),
				$this->equalTo(null)
			);

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$arguments = $this->invokePrivate($mount, 'arguments');

		$objectStore = $arguments['objectstore'];
		$this->assertInstanceOf(FakeObjectStore::class, $objectStore);
		$this->assertEquals(['foo' => 'bar', 'bucket' => 49, 'multibucket' => true], $objectStore->getArguments());
	}

	public function testMultiBucketWithPrefix(): void {
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'objectstore_multibucket') {
					return [
						'class' => 'Test\Files\Mount\FakeObjectStore',
						'arguments' => [
							'foo' => 'bar',
							'bucket' => 'myBucketPrefix',
						],
					];
				} else {
					return $default;
				}
			});

		$this->user->method('getUID')
			->willReturn('uid');
		$this->loader->expects($this->never())->method($this->anything());

		$this->config
			->method('getUserValue')
			->willReturn(null);

		$this->config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->equalTo('uid'),
				$this->equalTo('homeobjectstore'),
				$this->equalTo('bucket'),
				$this->equalTo('myBucketPrefix49'),
				$this->equalTo(null)
			);

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$arguments = $this->invokePrivate($mount, 'arguments');

		$objectStore = $arguments['objectstore'];
		$this->assertInstanceOf(FakeObjectStore::class, $objectStore);
		$this->assertEquals(['foo' => 'bar', 'bucket' => 'myBucketPrefix49', 'multibucket' => true], $objectStore->getArguments());
	}

	public function testMultiBucketBucketAlreadySet(): void {
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'objectstore_multibucket') {
					return [
						'class' => 'Test\Files\Mount\FakeObjectStore',
						'arguments' => [
							'foo' => 'bar',
							'bucket' => 'myBucketPrefix',
						],
					];
				} else {
					return $default;
				}
			});

		$this->user->method('getUID')
			->willReturn('uid');
		$this->loader->expects($this->never())->method($this->anything());

		$this->config
			->method('getUserValue')
			->willReturnCallback(function ($uid, $app, $key, $default) {
				if ($uid === 'uid' && $app === 'homeobjectstore' && $key === 'bucket') {
					return 'awesomeBucket1';
				} else {
					return $default;
				}
			});

		$this->config->expects($this->never())
			->method('setUserValue');

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$arguments = $this->invokePrivate($mount, 'arguments');

		$objectStore = $arguments['objectstore'];
		$this->assertInstanceOf(FakeObjectStore::class, $objectStore);
		$this->assertEquals(['foo' => 'bar', 'bucket' => 'awesomeBucket1', 'multibucket' => true], $objectStore->getArguments());
	}

	public function testMultiBucketConfigFirst(): void {
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'objectstore_multibucket') {
					return [
						'class' => 'Test\Files\Mount\FakeObjectStore',
						'arguments' => [
							'foo' => 'bar',
							'bucket' => 'myBucketPrefix',
						],
					];
				} else {
					return $default;
				}
			});

		$this->user->method('getUID')
			->willReturn('uid');
		$this->loader->expects($this->never())->method($this->anything());

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$this->assertInstanceOf('OC\Files\Mount\MountPoint', $mount);
	}

	public function testMultiBucketConfigFirstFallBackSingle(): void {
		$this->config
			->method('getSystemValue')->willReturnMap([
				['objectstore_multibucket', null, null],
				['objectstore', null, [
					'class' => 'Test\Files\Mount\FakeObjectStore',
					'arguments' => [
						'foo' => 'bar',
						'bucket' => 'myBucketPrefix',
					],
				]],
			]);

		$this->user->method('getUID')
			->willReturn('uid');
		$this->loader->expects($this->never())->method($this->anything());

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$this->assertInstanceOf('OC\Files\Mount\MountPoint', $mount);
	}

	public function testNoObjectStore(): void {
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$mount = $this->provider->getHomeMountForUser($this->user, $this->loader);
		$this->assertNull($mount);
	}
}

class FakeObjectStore implements IObjectStore {
	public function __construct(
		private array $arguments,
	) {
	}

	public function getArguments() {
		return $this->arguments;
	}

	public function getStorageId() {
	}

	public function readObject($urn) {
	}

	public function writeObject($urn, $stream, ?string $mimetype = null) {
	}

	public function deleteObject($urn) {
	}

	public function objectExists($urn) {
	}

	public function copyObject($from, $to) {
	}
}
