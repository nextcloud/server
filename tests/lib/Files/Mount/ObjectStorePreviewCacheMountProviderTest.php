<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files\Mount;

use OC\Files\Mount\ObjectStorePreviewCacheMountProvider;
use OC\Files\ObjectStore\S3;
use OC\Files\Storage\StorageFactory;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group DB
 *
 * The DB permission is needed for the fake root storage initialization
 */
class ObjectStorePreviewCacheMountProviderTest extends \Test\TestCase {

	/** @var ObjectStorePreviewCacheMountProvider */
	protected $provider;

	/** @var ILogger|MockObject */
	protected $logger;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IStorageFactory|MockObject */
	protected $loader;


	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->loader = $this->createMock(StorageFactory::class);

		$this->provider = new ObjectStorePreviewCacheMountProvider($this->logger, $this->config);
	}

	public function testNoMultibucketObjectStorage() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('objectstore_multibucket')
			->willReturn(null);

		$this->assertEquals([], $this->provider->getRootMounts($this->loader));
	}

	public function testMultibucketObjectStorage() {
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
