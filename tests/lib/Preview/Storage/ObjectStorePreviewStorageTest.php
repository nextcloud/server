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
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectStorePreviewStorageTest extends TestCase {
	private PrimaryObjectStoreConfig&MockObject $objectStoreConfig;
	private IConfig&MockObject $config;
	private PreviewMapper&MockObject $previewMapper;
	private ObjectStorePreviewStorage $storage;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->objectStoreConfig = $this->createMock(PrimaryObjectStoreConfig::class);
		$this->config = $this->createMock(IConfig::class);
		$this->previewMapper = $this->createMock(PreviewMapper::class);

		$this->config->method('getSystemValueBool')
			->with('objectstore.multibucket.preview-distribution')
			->willReturn(false);

		$this->storage = new ObjectStorePreviewStorage(
			$this->objectStoreConfig,
			$this->config,
			$this->previewMapper,
		);
	}

	/**
	 * Object-store existence checks are a network round-trip per preview, so
	 * the DB row is treated as authoritative until a cheaper bulk check exists.
	 */
	public function testPreviewExistsAlwaysReturnsTrueWithoutQueryingObjectStore(): void {
		$preview = $this->makePreview(locationId: 'loc-1');

		$this->objectStoreConfig->expects($this->never())->method('getObjectStoreConfiguration');
		$this->objectStoreConfig->expects($this->never())->method('buildObjectStore');

		$this->assertTrue($this->storage->previewExists($preview));
	}

	public function testPreviewExistsWhenLocationIdIsNull(): void {
		$preview = $this->makePreview(locationId: null);

		$this->objectStoreConfig->expects($this->never())->method('getObjectStoreConfiguration');
		$this->objectStoreConfig->expects($this->never())->method('buildObjectStore');

		$this->assertTrue($this->storage->previewExists($preview));
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
}
