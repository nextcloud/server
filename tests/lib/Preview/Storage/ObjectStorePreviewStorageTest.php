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

	/**
	 * Rows without a location cannot resolve a bucket. Treating them as missing
	 * would drop dummy and pre-migration previews on every request.
	 */
	public function testPreviewExistsWhenLocationIdIsNull(): void {
		$preview = $this->makePreview(locationId: null);

		$this->objectStoreConfig->expects($this->never())->method('getObjectStoreConfiguration');
		$this->objectStoreConfig->expects($this->never())->method('buildObjectStore');

		$this->assertTrue($this->createStorageForExistenceCheck()->previewExists($preview));
	}

	public function testPreviewExistsWhenObjectIsPresent(): void {
		$preview = $this->makePreview(locationId: 'loc-1');
		$store = $this->mockObjectStoreForPreview($preview);

		$store->expects($this->once())
			->method('objectExists')
			->with('urn:oid:preview:' . $preview->getId())
			->willReturn(true);

		$this->assertTrue($this->createStorageForExistenceCheck()->previewExists($preview));
	}

	public function testPreviewExistsWhenObjectIsMissing(): void {
		$preview = $this->makePreview(locationId: 'loc-1');
		$store = $this->mockObjectStoreForPreview($preview);

		$store->expects($this->once())
			->method('objectExists')
			->with('urn:oid:preview:' . $preview->getId())
			->willReturn(false);

		$this->assertFalse($this->createStorageForExistenceCheck()->previewExists($preview));
	}

	private function makePreview(?string $locationId): Preview {
		$preview = new Preview();
		$preview->id = 'preview-id-1';
		$preview->setFileId(42);
		$preview->setWidth(1024);
		$preview->setHeight(768);
		$preview->setMax(true);
		$preview->setCropped(false);
		$preview->setMimetype('image/jpeg');
		$preview->setObjectStoreName('preview');
		$preview->setBucketName('preview-bucket');
		if ($locationId !== null) {
			$preview->setLocationId($locationId);
		}
		return $preview;
	}

	private function mockObjectStoreForPreview(Preview $preview): IObjectStore&MockObject {
		$store = $this->createMock(IObjectStore::class);
		$this->objectStoreConfig->method('getObjectStoreConfiguration')
			->with($preview->getObjectStoreName())
			->willReturn([
				'class' => IObjectStore::class,
				'arguments' => [
					'multibucket' => false,
					'bucket' => 'preview-bucket',
					'objectPrefix' => 'urn:oid:',
				],
			]);
		$this->objectStoreConfig->method('buildObjectStore')
			->willReturn($store);
		return $store;
	}

	private function createStorageForExistenceCheck(): ObjectStorePreviewStorage {
		return new ObjectStorePreviewStorage($this->objectStoreConfig, $this->config, $this->previewMapper);
	}
}
