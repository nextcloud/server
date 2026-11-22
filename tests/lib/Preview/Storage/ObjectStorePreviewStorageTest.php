<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview\Storage;

use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OC\Preview\Storage\ObjectStorePreviewStorage;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectStorePreviewStorageTest extends TestCase {
	private PrimaryObjectStoreConfig&MockObject $objectStoreConfig;
	private IConfig&MockObject $config;
	private PreviewMapper&MockObject $previewMapper;

	protected function setUp(): void {
		parent::setUp();
		$this->objectStoreConfig = $this->createMock(PrimaryObjectStoreConfig::class);
		$this->config = $this->createMock(IConfig::class);
		$this->previewMapper = $this->createMock(PreviewMapper::class);
		$this->objectStoreConfig->method('resolveAlias')->with('root')->willReturn('default');
	}

	private function createStorage(bool $multibucket, bool $distribution): ObjectStorePreviewStorage {
		$this->objectStoreConfig->method('getObjectStoreConfiguration')->with('root')->willReturn([
			'class' => IObjectStore::class,
			'arguments' => ['bucket' => 'nc', 'multibucket' => $multibucket],
		]);
		$this->config->method('getSystemValueBool')
			->with('objectstore.multibucket.preview-distribution')
			->willReturn($distribution);
		return new ObjectStorePreviewStorage($this->objectStoreConfig, $this->config, $this->previewMapper);
	}

	private static function createPreview(int $fileId): Preview {
		$preview = new Preview();
		$preview->setFileId($fileId);
		return $preview;
	}

	public function testMigratePreviewsLooksUpTheLocationOncePerBucket(): void {
		$storage = $this->createStorage(multibucket: false, distribution: false);
		$previews = [self::createPreview(1), self::createPreview(2), self::createPreview(3)];

		$this->previewMapper->expects($this->once())
			->method('getLocationId')
			->with('nc', 'default')
			->willReturn('42');
		$this->previewMapper->expects($this->never())->method('update');
		$this->previewMapper->expects($this->never())->method('insert');

		$storage->migratePreviews($previews);

		foreach ($previews as $preview) {
			$this->assertSame('42', $preview->getLocationId());
			$this->assertSame('nc', $preview->getBucketName());
			$this->assertSame('default', $preview->getObjectStoreName());
		}
	}

	public function testMigratePreviewsWithMultibucketDistribution(): void {
		$storage = $this->createStorage(multibucket: true, distribution: true);
		// md5('1') and md5('2') start with 'c', md5('3') with 'e'
		$previews = [self::createPreview(1), self::createPreview(2), self::createPreview(3)];

		$this->previewMapper->expects($this->exactly(2))
			->method('getLocationId')
			->willReturnMap([
				['nc-preview-204', 'default', '1'],
				['nc-preview-238', 'default', '2'],
			]);

		$storage->migratePreviews($previews);

		$this->assertSame('1', $previews[0]->getLocationId());
		$this->assertSame('1', $previews[1]->getLocationId());
		$this->assertSame('2', $previews[2]->getLocationId());
		$this->assertSame('nc-preview-238', $previews[2]->getBucketName());
	}
}
