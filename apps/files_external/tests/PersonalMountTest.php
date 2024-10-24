<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests;

use OC\Files\Mount\Manager;
use OC\Files\SetupManagerFactory;
use OCA\Files_External\Lib\PersonalMount;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\UserStoragesService;
use Test\TestCase;

class PersonalMountTest extends TestCase {
	public function testFindByStorageId(): void {
		$storageConfig = $this->createMock(StorageConfig::class);
		/** @var UserStoragesService $storageService */
		$storageService = $this->getMockBuilder('\OCA\Files_External\Service\UserStoragesService')
			->disableOriginalConstructor()
			->getMock();

		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		$storage->expects($this->any())
			->method('getId')
			->willReturn('dummy');

		$mount = new PersonalMount($storageService, $storageConfig, 10, $storage, '/foo');

		$mountManager = new Manager($this->createMock(SetupManagerFactory::class));
		$mountManager->addMount($mount);

		$this->assertEquals([$mount], $mountManager->findByStorageId('dummy'));
	}
}
