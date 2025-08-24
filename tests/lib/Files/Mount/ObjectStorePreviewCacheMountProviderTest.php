<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Mount;

use OC\Files\Mount\ObjectStorePreviewCacheMountProvider;
use OC\Files\ObjectStore\S3;
use OC\Files\Storage\StorageFactory;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @group DB
 *
 * The DB permission is needed for the fake root storage initialization
 */
class ObjectStorePreviewCacheMountProviderTest extends \Test\TestCase {
	/** @var ObjectStorePreviewCacheMountProvider */
	protected $provider;

	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IStorageFactory|MockObject */
	protected $loader;


	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = $this->createMock(IConfig::class);
		$this->loader = $this->createMock(StorageFactory::class);

		$this->provider = new ObjectStorePreviewCacheMountProvider($this->logger, $this->config);
	}

	public function testNoMultibucketObjectStorage(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('objectstore_multibucket')
			->willReturn(null);

		$this->assertEquals([], $this->provider->getRootMounts($this->loader));
	}

	public function testMultibucketObjectStorage(): void {
		$objectstoreConfig = [
			'class' => S3::class,
			'arguments' => [
				'bucket' => 'abc',
				'num_buckets' => 64,
				'key' => 'KEY',
				'secret' => 'SECRET',
				'hostname' => 'IP',
				'port' => 'PORT',
				'use_ssl' => false,
				'use_path_style' => true,
			],
		];
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($config) use ($objectstoreConfig) {
				if ($config === 'objectstore_multibucket') {
					return $objectstoreConfig;
				} elseif ($config === 'objectstore.multibucket.preview-distribution') {
					return true;
				}
				return null;
			});
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('instanceid')
			->willReturn('INSTANCEID');

		$mounts = $this->provider->getRootMounts($this->loader);

		// 256 mounts for the subfolders and 1 for the fake root
		$this->assertCount(257, $mounts);

		// do some sanity checks if they have correct mount point paths
		$this->assertEquals('/appdata_INSTANCEID/preview/0/0/', $mounts[0]->getMountPoint());
		$this->assertEquals('/appdata_INSTANCEID/preview/2/5/', $mounts[37]->getMountPoint());
		// also test the path of the fake bucket
		$this->assertEquals('/appdata_INSTANCEID/preview/old-multibucket/', $mounts[256]->getMountPoint());
	}
}
